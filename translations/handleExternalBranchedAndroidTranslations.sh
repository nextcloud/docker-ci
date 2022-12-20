#!/bin/sh

# verbose and exit on error
set -xe

# import GPG keys
gpg --import /gpg/nextcloud-bot.public.asc
gpg --allow-secret-key-import --import /gpg/nextcloud-bot.asc
gpg --list-keys

# fetch git repo
git clone git@github.com:$1/$2 /app

# create or reset translation branch
git checkout -B $3

# Migrate the transifex config to the new client version
tx migrate
git add .tx/config
rm .tx/config_*
git commit -am "Fix(l10n): 🛠️ Update Transifex configuration" -s || true
git push -f origin $3

# push sources
tx push -s

# pull translations
tx pull -f -a --minimum-perc=75

# for the Android apps notes/deck rename the informal german to the formal version
if [ -d app/src/main/res ]; then
  rm -rf app/src/main/res/values-de
  mv app/src/main/res/values-de-rDE app/src/main/res/values-de
fi

# create git commit and push it
git add .
git commit -am "Fix(l10n): 🔠 Update translations from Transifex" -s || true
git push -f origin $3
echo "done"
