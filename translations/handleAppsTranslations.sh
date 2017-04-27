#!/bin/sh

# verbose and exit on error
set -xe

# import GPG keys
gpg --import /gpg/nextcloud-bot.public.asc
gpg --allow-secret-key-import --import /gpg/nextcloud-bot.asc
gpg --list-keys

# fetch git repo
git clone git@github.com:nextcloud/$1 /app


for app in $(ls)
do
  if [ ! -d "$app/l10n" ]; then
    continue
  fi
  
  cd "$app/l10n"
    
  # build PO file
  wget https://raw.githubusercontent.com/owncloud/administration/master/jenkins/translation_sync/l10n.pl
  perl ./l10n.pl $app read

  if [ -d "templates" ]; then
    # push sources
    tx push -s

    # pull translations - force pull because a fresh clone has newer time stamps
    tx pull -f -a --minimum-perc=25
  done

  # delete removed l10n files that are used for language detection (they will be recreated during the write)
  rm -f *.js *.json

  # build JS/JSON based on translations
  perl ./l10n.pl $app write

  rm l10n.pl
  cd ..

  if [ -d tests ]; then
    # remove tests/
    rm -rf tests
    git checkout -- tests/
  fi

  # create git commit and push it
  git add l10n/*.js l10n/*.json

  cd ../..
done

git commit -am "[tx-robot] updated from transifex" || true
git push origin master
echo "done"
