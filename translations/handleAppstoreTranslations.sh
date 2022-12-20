#!/bin/sh

# verbose and exit on error
set -xe

# import GPG keys
gpg --import /gpg/nextcloud-bot.public.asc
gpg --allow-secret-key-import --import /gpg/nextcloud-bot.asc
gpg --list-keys

# fetch git repo
git clone git@github.com:nextcloud/appstore /app

# install django
pip3 install Django==1.9.8

# create po files
./manage.py makemessages

# Migrate the transifex config to the new client version
tx migrate
git add .tx/config
rm .tx/config_*
git commit -am "Fix(l10n): 🛠️ Update Transifex configuration" -s || true
git push origin master

# push sources
tx push -s

# pull translations - force pull because a fresh clone has newer time stamps
tx pull -f -a --minimum-perc=75

# don't add de_DE translation
rm -rf locale/de_DE/

# create git commit and push it
git add locale/
git commit -am "Fix(l10n): 🔠 Update translations from Transifex" -s || true
git push origin master
echo "done"
