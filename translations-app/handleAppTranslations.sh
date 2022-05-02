#!/bin/sh

# verbose and exit on error
set -xe

# import GPG keys
gpg --import /gpg/nextcloud-bot.public.asc
gpg --allow-secret-key-import --import /gpg/nextcloud-bot.asc
gpg --list-keys

# fetch git repo
git clone git@github.com:$1/$2 /app

# TODO use build/l10nParseAppInfo.php to fetch app names for l10n

versions='stable22 stable23 stable24 master main'
if [ -f '/app/.tx/backport' ]; then
  versions="$(cat /app/.tx/backport) master"
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
# alternative merge of main branch
for file in $(ls stable-templates/main.*)
do
  name=$(echo $file | cut -b 23- )
  msgcat --use-first stable-templates/*.$name > translationfiles/templates/$name
done

# remove intermediate POT files
rm -rf stable-templates

# push sources
tx push -s

# pull translations - force pull because a fresh clone has newer time stamps
tx pull -f -a --minimum-perc=5

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
