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
git clone git@github.com:$1/$2 /app/default --depth 1

cd default

if [ ! -f '.tx/config' ]; then
  echo "Missing transifex configuration file .tx/config"
  exit 1
fi

##################################
# Migrate the transifex config to the new client version
##################################
tx migrate
git add --force .tx/config
rm .tx/config_*
git commit -am "fix(l10n): Update Transifex configuration" -s || true
git push

##################################
# Validate sync setup
##################################
DAY_OF_WEEK=$(date "+%w")
RESOURCE_ID=$(grep -oE '\[o:nextcloud:p:nextcloud:r:.*\]' .tx/config | sed -E 's/\[o:nextcloud:p:nextcloud:r:(.*)\]/\1/')

if [ "$RESOURCE_ID" = "talk_desktop" ]; then
  echo "Skipping release check of Talk Desktop"
elif [ $DAY_OF_WEEK -eq 6 ]; then
  APP_MAX_VERSION=$(grep -oE '<nextcloud.*max-version=".*".*>' appinfo/info.xml | head --lines 1 | sed -E 's/(.*)max-version="(.*)"(.*)/\2/')
  if [ ! $APP_MAX_VERSION ]; then
    echo "App has no max-version defined"
    grep '<nextcloud' appinfo/info.xml | head --lines 1
    exit 1
  elif [ $APP_MAX_VERSION -lt 31 ]; then
    echo "App was not released in the last year and translations should be stopped"
    echo "Defined max-version is $APP_MAX_VERSION"
    exit 1
  fi

  echo "App has max-version $APP_MAX_VERSION"
fi

APP_ID=$(grep -oE '<id>.*</id>' appinfo/info.xml | head --lines 1 | sed -E 's/<id>(.*)<\/id>/\1/')
IS_EX_APP=$(grep -q '<external-app>' appinfo/info.xml && grep -q '</external-app>' appinfo/info.xml && echo "true" || echo "false")
SOURCE_FILE=$(grep -oE '^source_file\s*=\s*(.+)$' .tx/config | sed -E 's/source_file\s*=\s*(.+)/\1/')

if [ "$RESOURCE_ID" = "MYAPP" ]; then
  echo "Invalid transifex configuration file .tx/config (translating MYAPP instead of real value)"
  exit 2
fi

if [ "$RESOURCE_ID" = "talk_desktop" ]; then
  # Desktop client has no appinfo/info.xml
  APP_ID="talk_desktop"
fi

versions='main master stable35 stable34 stable33 stable32'
if [ -f '.tx/backport' ]; then
  versions="main master $(cat .tx/backport)"
fi

# During a string freeze no new strings may enter the translation system.
# This only affects apps that are shipped with the server, all other apps keep
# syncing their strings. For shipped apps the branch with the new strings is
# skipped completely and the newest stable branch defines the source strings.
STRING_FREEZE='false'
TEMPLATE_BRANCHES='master main'
if is_string_freeze "$versions" && is_shipped_app "$APP_ID"; then
  STRING_FREEZE='true'
  TEMPLATE_BRANCHES=$(newest_stable_branch "$versions")
  echo "String freeze is active and $APP_ID is shipped: not syncing new strings from main and master, using $TEMPLATE_BRANCHES instead"
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
  git clone git@github.com:$1/$2 /app/$version --depth 1 --branch $version
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
    FILE_SAVE_VERSION=$(echo $version | sed -E 's/\//-/')
    mv $file /app/default/stable-templates/$FILE_SAVE_VERSION.$RESOURCE_ID.pot
  done
  cd ../..
done


##################################
# Sync with transifex
##################################
cd /app/default

# merge POT files into one
# the last branch of the list wins, so main takes precedence over master
HAS_TEMPLATES='false'
for template_branch in $TEMPLATE_BRANCHES
do
  for file in $(ls stable-templates/$template_branch.* 2>/dev/null)
  do
    HAS_TEMPLATES='true'
    name=${file#stable-templates/$template_branch.}
    # the template branch comes first, so duplicated strings keep its comments
    msgcat --use-first $file $(ls stable-templates/*.$name | grep -vxF "$file") > $SOURCE_FILE
  done
done

# remove intermediate POT files
rm -rf stable-templates

# push sources
if [ "$HAS_TEMPLATES" = 'true' ]; then
  tx push -s
else
  echo "No source templates found for branch $TEMPLATE_BRANCHES, not pushing sources"
fi

# pull translations - force pull because a fresh clone has newer time stamps
tx pull -f -a --minimum-perc=5

# delete removed l10n files that are used for language detection (they will be recreated during the write)
rm -f l10n/*.js l10n/*.json

# Copy back the po files from transifex resource id to app id
if [ "$RESOURCE_ID" = "$APP_ID" ] ; then
  echo 'App id and transifex resource id are the same, not renaming po files …'
else
  echo "App id [$APP_ID] and transifex resource id [$RESOURCE_ID] mismatch"
  echo 'Renaming po files …'
  for file in $(ls translationfiles)
  do
    if [ "$file" = 'templates' ]; then
      continue;
    fi

    # Some special handling for apps where the resource name is reserved by transifex (transfer, analytics, ...)
    # in that case the downloaded ".po" files already have the correct name, so we skip the renaming.
    if [ -f translationfiles/$file/$RESOURCE_ID.po ]; then
      mv translationfiles/$file/$RESOURCE_ID.po translationfiles/$file/$APP_ID.po
    fi
  done
fi

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
  rm -f l10n/*.js l10n/*.json

  # Copy JS and JSON
  cp /app/default/l10n/*.js /app/default/l10n/*.json l10n

  # create git commit and push it
  git add --force l10n/*.js l10n/*.json

  # for ExApps, we need to include .po translation files as well
  if [ "$IS_EX_APP" = "true" ]; then
    cp /app/default/translationfiles/*.po translationfiles
    git add --force translationfiles/*.po
  fi

  git commit -am "fix(l10n): Update translations from Transifex" -s || true
  git push origin $version

  echo "done with $version"
done

# End of verbose mode
set +xe

##################################
# Validate translations
##################################
if [ -f '.tx/validate' ]; then
  /validateTranslationFiles.sh /app/default
fi

exit $?
