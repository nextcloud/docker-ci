#!/bin/sh

# verbose and exit on error
set -xe

# Load the string freeze helpers
. /stringFreeze.sh

# Print tooling information
php -v
tx -v
xgettext -V

# import GPG keys
gpg --import /gpg/nextcloud-bot.public.asc
gpg --allow-secret-key-import --import /gpg/nextcloud-bot.asc
gpg --list-keys

# fetch git repo
git clone git@github.com:nextcloud/server /app/default --depth 1

##################################
# Migrate the transifex config to the new client version
##################################
cd /app/default
tx migrate
git add --force .tx/config
rm .tx/config_*
git commit -am "fix(l10n): Update Transifex configuration" -s || true
git push

##################################
# Prepare sync setup
##################################
versions='master stable35 stable34 stable33'

# During a string freeze no new strings may enter the translation system.
# Everything in the server repository is shipped, so the branch with the new
# strings is skipped completely and the newest stable branch defines the
# resources and source strings instead.
STRING_FREEZE='false'
TEMPLATE_BRANCH='master'
if is_string_freeze "$versions"; then
  STRING_FREEZE='true'
  TEMPLATE_BRANCH=$(newest_stable_branch "$versions")
  echo "String freeze is active: not syncing new strings from master, using $TEMPLATE_BRANCH instead"
fi

mkdir stable-templates
mkdir -p translationfiles/templates/

##################################
# Clone backport branches
##################################
# Don't fail on checking out non existing branches
set +e
for version in $versions
do
  git clone git@github.com:nextcloud/server /app/$version --depth 1 --branch $version
done
set -e

##################################
# Build POT files for all versions
##################################
for version in $versions
do
  if [ ! -d /app/$version ]; then
    # skip if the branch doesn't exist
    continue
  fi

  if [ "$STRING_FREEZE" = 'true' ] && { [ "$version" = 'master' ] || [ "$version" = 'main' ]; }; then
    # during the string freeze the new strings of the development branch
    # must not be added to the translation system
    echo "Skipping templates of $version during the string freeze"
    continue
  fi

  cd /app/$version

  # build POT files
  /translationtool.phar create-pot-files

  cd translationfiles/templates/
  for file in $(ls)
  do
    mv $file /app/default/stable-templates/$version.$file
  done
  cd ../..
done

##################################
# Sync with transifex
##################################
cd /app/default

# merge POT files into one
HAS_TEMPLATES='false'
for file in $(ls stable-templates/$TEMPLATE_BRANCH.* 2>/dev/null)
do
  HAS_TEMPLATES='true'
  name=${file#stable-templates/$TEMPLATE_BRANCH.}
  # the template branch comes first, so duplicated strings keep its comments
  msgcat --use-first $file $(ls stable-templates/*.$name | grep -vxF "$file") > translationfiles/templates/$name
done

# remove intermediate POT files
rm -rf stable-templates

# Checkout master so we have the newest .tx/config with the newest list of resources
git checkout master

# push sources
if [ "$HAS_TEMPLATES" = 'true' ]; then
  tx push -s
else
  echo "No source templates found for branch $TEMPLATE_BRANCH, not pushing sources"
fi

# pull translations - force pull because a fresh clone has newer time stamps
tx pull -f -a --minimum-perc=50
# pull all "lib" translations for the language name
tx pull -a -f -r nextcloud.lib --minimum-perc=0
# pull 20% of "settings" translations for the region name
tx pull -a -f -r nextcloud.settings-1 --minimum-perc=20


# delete removed l10n files
find core/l10n/*.js -type f -delete
find core/l10n/*.json -type f -delete
find lib/l10n/*.js -type f -delete
find lib/l10n/*.json -type f -delete
find apps/*/l10n/*.js -type f -delete
find apps/*/l10n/*.json -type f -delete

# build JS/JSON based on translations
/translationtool.phar convert-po-files

##################################
# Security files: CA certificate bundle and code signing revocation list
##################################
security_versions="$versions stable32 stable31 stable30 stable29 stable28 stable27 stable26 stable25 stable24 stable23 stable22"
SECURITY_FAILED='false'

set +e
for version in $security_versions
do
  [ -d /app/$version ] || git clone git@github.com:nextcloud/server /app/$version --depth 1 --branch $version
done

# Download once, never overwrite the files with a failed or bogus download
CA_BUNDLE_OK='false'
curl --fail --silent --show-error --location --retry 3 \
  --etag-save /app/ca-bundle-etag.txt --output /app/ca-bundle.crt https://curl.se/ca/cacert.pem \
  && grep -q 'BEGIN CERTIFICATE' /app/ca-bundle.crt \
  && CA_BUNDLE_OK='true'
CRL_OK='false'
curl --fail --silent --show-error --location --retry 3 \
  --output /app/root.crl https://raw.githubusercontent.com/nextcloud/appstore/master/nextcloudappstore/certificate/nextcloud.crl \
  && CRL_OK='true'
set -e

if [ "$CA_BUNDLE_OK" != 'true' ] || [ "$CRL_OK" != 'true' ]; then
  echo "Downloading security files failed (CA bundle: $CA_BUNDLE_OK, CRL: $CRL_OK)"
  SECURITY_FAILED='true'
fi

# Commits the security files in the current checkout (does not push)
update_security_files() {
  if [ "$CRL_OK" = 'true' ]; then
    if openssl crl -verify -in /app/root.crl -CAfile resources/codesigning/root.crt -noout; then
      cp /app/root.crl resources/codesigning/root.crl
      git add resources/codesigning/root.crl
      git commit -m "fix(security): Update code signing revocation list" -s || true
    else
      echo "CRL is not signed by resources/codesigning/root.crt of $1, not updating"
      SECURITY_FAILED='true'
    fi
  fi

  if [ "$CA_BUNDLE_OK" = 'true' ]; then
    cp /app/ca-bundle.crt resources/config/ca-bundle.crt
    git add resources/config/ca-bundle.crt
    if [ -f /app/ca-bundle-etag.txt ] && [ -f build/ca-bundle-etag.txt ]; then
      cp /app/ca-bundle-etag.txt build/ca-bundle-etag.txt
      git add build/ca-bundle-etag.txt
    fi
    git commit -m "fix(security): Update CA certificate bundle" -s || true
  fi
}

# Branches that only get the security files, translation branches follow below
for version in $security_versions
do
  case " $versions " in *" $version "*) continue ;; esac
  [ -d /app/$version ] || continue

  cd /app/$version
  update_security_files $version
  git push origin $version
done

##################################
# Add translations to branches again
##################################
for version in $versions
do
  if [ ! -d /app/$version ]; then
    # skip if the branch doesn't exist
    continue
  fi

  cd /app/$version
  update_security_files $version

  # delete removed l10n files that are used for language detection (they will be recreated during the write)
  find core/l10n/*.js -type f -delete
  find core/l10n/*.json -type f -delete
  find lib/l10n/*.js -type f -delete
  find lib/l10n/*.json -type f -delete
  find apps/*/l10n/*.js -type f -delete
  find apps/*/l10n/*.json -type f -delete

  # Copy JS and JSON
  cd /app/default
  find core/l10n/*.js -type f -exec cp {} /app/$version/{} \;
  find core/l10n/*.json -type f -exec cp {} /app/$version/{} \;
  find lib/l10n/*.js -type f -exec cp {} /app/$version/{} \;
  find lib/l10n/*.json -type f -exec cp {} /app/$version/{} \;
  find apps/*/l10n/*.js -type f -exec cp {} /app/$version/{} \;
  find apps/*/l10n/*.json -type f -exec cp {} /app/$version/{} \;
  cd /app/$version

  # create git commit and push it
  git add --force apps/*/l10n/*.js apps/*/l10n/*.json core/l10n/*.js core/l10n/*.json lib/l10n/*.js lib/l10n/*.json

  git commit -am "fix(l10n): Update translations from Transifex" -s || true
  git push origin $version

  echo "done with $version"
done

##################################
# Validate translations
##################################
cd /app/default
set +xe
EXIT_CODE=0
/validateTranslationFiles.sh core
EXIT_CODE=$(($?+$EXIT_CODE))
/validateTranslationFiles.sh lib
EXIT_CODE=$(($?+$EXIT_CODE))

for app in $(ls apps)
do
  if [ -d "apps/$app/l10n" ]; then
    /validateTranslationFiles.sh apps/$app
    EXIT_CODE=$(($?+$EXIT_CODE))
  fi
done;

if [ "$SECURITY_FAILED" = 'true' ]; then
  echo "Updating the security files failed, see above"
  exit 1
fi
