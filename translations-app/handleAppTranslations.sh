#!/bin/sh

# verbose and exit on error
set -xe

# import GPG keys
gpg --import /gpg/nextcloud-bot.public.asc
gpg --allow-secret-key-import --import /gpg/nextcloud-bot.asc
gpg --list-keys

# fetch git repo
git clone git@github.com:$1/$2 /app

# Migrate the transifex config to the new client version
cd /app
tx migrate
git add --force .tx/config
rm .tx/config_*
git commit -am "[tx-robot] Update transifex configuration" -s || true
git push
cd -

if [ ! -f '/app/.tx/config' ]; then
  echo "Missing transifex configuration file .tx/config"
  exit 1
fi

APP_ID=$(grep -oE '<id>.*</id>' appinfo/info.xml | head --lines 1 | sed -E 's/<id>(.*)<\/id>/\1/')
RESOURCE_ID=$(grep -oE '\[o:nextcloud:p:nextcloud:r:.*\]' config | sed -E 's/\[o:nextcloud:p:nextcloud:r:(.*)\]/\1/')
SOURCE_FILE=$(grep -oE '^source_file\s*=\s*(.+)$' .tx/config | sed -E 's/source_file\s*=\s*(.+)/\1/')

if [ "$RESOURCE_ID" = "MYAPP" ]; then
  echo "Invalid transifex configuration file .tx/config (translating MYAPP instead of real value)"
  exit 2
fi

# TODO use build/l10nParseAppInfo.php to fetch app names for l10n

versions='stable22 stable23 stable24 master main'
if [ -f '/app/.tx/backport' ]; then
  versions="$(cat /app/.tx/backport) master main"
fi

# build POT files for all versions
mkdir stable-templates
for version in $versions
do
  # skip if the branch doesn't exist
  if git branch -r | egrep "^\W*origin/$version$" ; then
    echo "Valid branch"
  else
    echo "Invalid branch"
    continue
  fi
  git checkout $version

  # ignore build folder logreader
  if [ "$version" = "stable22" ] || [ "$version" = "stable23" ] ; then
    if [ "$2" = "logreader" ] ; then
      rm -rf build
    fi
  fi

  # build POT files
  /translationtool.phar create-pot-files

  # ignore build folder logreader
  if [ "$version" = "stable22" ] || [ "$version" = "stable23" ] ; then
    if [ "$2" = "logreader" ] ; then
      git checkout -- build
    fi
  fi

  cd translationfiles/templates/
  for file in $(ls)
  do
    FILE_SAVE_VERSION=$(echo $version | sed -E 's/\//-/')
    mv $file ../../stable-templates/$FILE_SAVE_VERSION.$RESOURCE_ID.pot
  done
  cd ../..
done

# merge POT files into one
for file in $(ls stable-templates/master.*)
do
  name=$(echo $file | cut -b 25- )
  msgcat --use-first stable-templates/*.$name > $SOURCE_FILE
done
# alternative merge of main branch
for file in $(ls stable-templates/main.*)
do
  name=$(echo $file | cut -b 23- )
  msgcat --use-first stable-templates/*.$name > $SOURCE_FILE
done

# remove intermediate POT files
rm -rf stable-templates

# push sources
tx push -s

# pull translations - force pull because a fresh clone has newer time stamps
tx pull -f -a --minimum-perc=5

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

# reverse version list to apply backports
backportVersions=$(echo $versions | awk '{for(i=NF;i>=1;i--) printf "%s ", $i;print ""}')
for version in $backportVersions
do
  # skip if the branch doesn't exist
  if git branch -r | egrep "^\W*origin/$version$" ; then
    echo "Valid branch"
  else
    echo "Invalid branch"
    continue
  fi
  git checkout $version

  # delete removed l10n files that are used for language detection (they will be recreated during the write)
  rm -f l10n/*.js l10n/*.json

  # build JS/JSON based on translations
  /translationtool.phar convert-po-files

  if [ -d tests ]; then
    # remove tests/
    rm -rf tests
    git checkout -- tests/
  fi

  # create git commit and push it
  git add l10n/*.js l10n/*.json
  git commit -am "[tx-robot] updated from transifex" -s || true
  git push origin $version

  echo "done with $version"
done
