#!/bin/sh

# verbose and exit on error
set -xe

# import GPG keys
gpg --import /gpg/nextcloud-bot.public.asc
gpg --allow-secret-key-import --import /gpg/nextcloud-bot.asc
gpg --list-keys

# fetch git repo
git clone git@github.com:nextcloud/talk-ios /app

# Migrate the transifex config to the new client version
tx migrate
git add .tx/config
rm .tx/config_*
git commit -am "fix(l10n): Update Transifex configuration" -s || true
git push

# remove all translations (they are added afterwards anyways but allows to remove languages via transifex)
rm -r NextcloudTalk/*.lproj
git checkout -- NextcloudTalk/Base.lproj
git checkout -- NextcloudTalk/en.lproj

# push sources
tx push -s

# pull translations
tx pull -f -a --minimum-perc=25

cd NextcloudTalk

# use de_DE instead of de
rm -rf ./de.lproj
mv de_DE.lproj de.lproj

cd ..

# create git commit and push it
git add .
git commit -am "fix(l10n): Update translations from Transifex" -s || true
git push origin master
echo "done"
