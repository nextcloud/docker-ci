#!/bin/sh

# verbose and exit on error
set -xe

# import GPG keys
gpg --import /gpg/nextcloud-bot.public.asc
gpg --allow-secret-key-import --import /gpg/nextcloud-bot.asc
gpg --list-keys

# fetch git repo
git clone git@github.com:nextcloud/server /app

versions='stable10 stable11 master'

# build POT files for all versions
cd l10n
mkdir stable-templates
for version in $versions
do
  git checkout $version
  perl ./l10n.pl read > /dev/null
  cd templates
  for file in $(ls)
  do
    mv $file ../stable-templates/$version.$file
  done
  cd ..
done

# merge POT files into one
for file in $(ls stable-templates/master.*)
do
  name=$(echo $file | cut -b 25- )
  msgcat --use-first stable-templates/*.$name > templates/$name
done

# remove intermediate POT files
rm -rf stable-templates

# push sources
tx push -s

# pull translations - force pull because a fresh clone has newer time stamps
tx pull -f -a --minimum-perc=75
# pull all settings for language name
tx pull -a -f -r nextcloud.settings-1 --minimum-perc=1

cd ..

backportVersions='master stable11 stable10'
for version in $backportVersions
do
  git checkout $version

  # delete removed l10n files that are used for language detection (they will be recreated during the write)
  find core/l10n -type f -delete
  find lib/l10n -type f -delete
  find settings/l10n -type f -delete

  cd l10n

  # build JS/JSON based on translations
  perl ./l10n.pl write

  cd ..

  # remove tests/
  rm -rf tests
  git checkout -- tests/

  # create git commit and push it
  git add .
  git commit -am "[tx-robot] updated from transifex" || true
  git push origin $version

  echo "done with $version"
done
