#!/bin/sh

# verbose and exit on error
set -xe

# import GPG keys
gpg --import /gpg/nextcloud-bot.public.asc
gpg --allow-secret-key-import --import /gpg/nextcloud-bot.asc
gpg --list-keys

# fetch git repo
git clone git@github.com:nextcloud/$1 /app

# build PO file
cd l10n
wget https://raw.githubusercontent.com/nextcloud/docker-ci/master/translations/l10n.pl
wget https://raw.githubusercontent.com/nextcloud/server/master/build/l10nParseAppInfo.php
perl ./l10n.pl $1 read

versions='stable11 stable12 master abc'

# build POT files for all versions
mkdir stable-templates
for version in $versions
do
  # skip if the branch doesn't exist
  if git branch | egrep "^  $version$" ; then
    echo "Valid branch"
  else
    echo "Invalid branch"
    continue
  fi
  git checkout $version
  perl ./l10n.pl $1 read > /dev/null
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

rm l10nParseAppInfo.php

# remove intermediate POT files
rm -rf stable-templates

# push sources
tx push -s

# pull translations - force pull because a fresh clone has newer time stamps
tx pull -f -a --minimum-perc=25

# delete removed l10n files that are used for language detection (they will be recreated during the write)
rm -f *.js *.json

cd ..

backportVersions='master stable12 stable11'
for version in $backportVersions
do
  # skip if the branch doesn't exist
  if git branch | egrep "^  $version$" ; then
    echo "Valid branch"
  else
    echo "Invalid branch"
    continue
  fi
  git checkout $version

  cd l10n

  # build JS/JSON based on translations
  perl ./l10n.pl $1 write

  cd ..

  if [ -d tests ]; then
    # remove tests/
    rm -rf tests
    git checkout -- tests/
  fi

  # create git commit and push it
  git add l10n/*.js l10n/*.json
  git commit -am "[tx-robot] updated from transifex" || true
  git push origin $version

  echo "done with $version"
done

rm l10n.pl
