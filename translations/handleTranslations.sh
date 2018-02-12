#!/bin/sh

# verbose and exit on error
set -xe

# import GPG keys
gpg --import /gpg/nextcloud-bot.public.asc
gpg --allow-secret-key-import --import /gpg/nextcloud-bot.asc
gpg --list-keys

# fetch git repo
git clone git@github.com:nextcloud/server /app

# fetch translation script
# TODO ship this inside the docker container
wget https://github.com/nextcloud/travis_ci/raw/master/translationtool/translationtool.phar
# TODO use build/l10nParseAppInfo.php to fetch app names for l10n

versions='stable12 stable13 master'

# build POT files for all versions
mkdir stable-templates
for version in $versions
do
  git checkout $version
  php5 translationtool.phar create-pot-files

  cd translationfiles/templates/
  for file in $(ls)
  do
    mv $file ../../stable-templates/$version.$file
  done
  cd ../..
done

# merge POT files into one
for file in $(ls stable-templates/master.*)
do
  name=$(echo $file | cut -b 25- )
  msgcat --use-first stable-templates/*.$name > translationfiles/templates/$name
done

# remove intermediate POT files
rm -rf stable-templates

# push sources
tx push -s

# pull translations - force pull because a fresh clone has newer time stamps
tx pull -f -a --minimum-perc=75
# pull all "settings" translations for the language name (for 12)
tx pull -a -f -r nextcloud.settings-1 --minimum-perc=1
# pull all "lib" translations for the language name (for 13 and master)
tx pull -a -f -r nextcloud.lib --minimum-perc=1

backportVersions='master stable13 stable12'
for version in $backportVersions
do
  git checkout $version

  # delete removed l10n files that are used for language detection (they will be recreated during the write)
  find core/l10n -type f -delete
  find lib/l10n -type f -delete
  find settings/l10n -type f -delete

  # build JS/JSON based on translations
  php5 translationtool.phar convert-po-files

  # remove tests/
  rm -rf tests
  git checkout -- tests/

  # create git commit and push it
  git add apps core lib settings
  git commit -am "[tx-robot] updated from transifex" || true
  git push origin $version

  echo "done with $version"
done
