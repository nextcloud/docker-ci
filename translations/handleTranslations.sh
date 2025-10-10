#!/bin/sh

# verbose and exit on error
set -xe

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
git add .tx/config
rm .tx/config_*
git commit -am "fix(l10n): Update Transifex configuration" -s || true
git push

##################################
# Prepare sync setup
##################################
versions='master stable32 stable31'

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
for file in $(ls stable-templates/master.*)
do
  # Change below to 23 when server switches to main
  name=$(echo $file | cut -b 25- )
  msgcat --use-first stable-templates/*.$name > translationfiles/templates/$name
done

# remove intermediate POT files
rm -rf stable-templates

# Checkout master so we have the newest .tx/config with the newest list of resources
git checkout master

# push sources
tx push -s

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
# Add translations to branches again
##################################
for version in $versions
do
  if [ ! -d /app/$version ]; then
    # skip if the branch doesn't exist
    continue
  fi

  cd /app/$version

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
  git add apps core lib

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
