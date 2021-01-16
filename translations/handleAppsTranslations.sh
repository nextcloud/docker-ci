#!/bin/sh

# verbose and exit on error
set -xe

# import GPG keys
gpg --import /gpg/nextcloud-bot.public.asc
gpg --allow-secret-key-import --import /gpg/nextcloud-bot.asc
gpg --list-keys

# fetch git repo
git clone git@github.com:nextcloud/$1 /app

# TODO use build/l10nParseAppInfo.php to fetch app names for l10n

for app in $(ls)
do
  if [ ! -d "$app/.tx" ]; then
    continue
  fi
  
  cd "$app"

  # build POT files
  /translationtool.phar create-pot-files

  # delete removed l10n files that are used for language detection (they will be recreated during the write)
  rm -f l10n/*.js l10n/*.json

  if [ -e "translationfiles/templates/*.pot" ]; then
    # push sources
    tx push -s

    # pull translations - force pull because a fresh clone has newer time stamps
    tx pull -f -a --minimum-perc=25

    # build JS/JSON based on translations
    /translationtool.phar convert-po-files

    if [ -d tests ]; then
      # remove tests/
      rm -rf tests
      git checkout -- tests/
    fi
  fi

  # prepate git commit
  git add l10n/*.js l10n/*.json || true

  cd ..
done

# create git commit and push it
git commit -m "[tx-robot] updated from transifex" -s || true
git push origin master
echo "done"
