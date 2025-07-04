#!/bin/sh

# verbose and exit on error
set -xe

# Print tooling information
python3 --version
tx -v

# import GPG keys
gpg --import /gpg/nextcloud-bot.public.asc
gpg --allow-secret-key-import --import /gpg/nextcloud-bot.asc
gpg --list-keys

# fetch git repo
git clone git@github.com:nextcloud/appstore /app --depth 1

# install django
python3 -m venv venv
. venv/bin/activate
pip3 install Django==1.9.8

# create po files
./manage.py makemessages

# Migrate the transifex config to the new client version
tx migrate
git add .tx/config
rm .tx/config_*
git commit -am "fix(l10n): Update Transifex configuration" -s || true
git push origin master

# push sources
tx push -s

# pull translations - force pull because a fresh clone has newer time stamps
tx pull -f -a --minimum-perc=75

# don't add de_DE translation
rm -rf locale/de_DE/

# Remove UG translation as they break the HTML markup too regularly which causes deployment issues
rm -rf locale/ug/

# create git commit and push it
git add locale/
git commit -am "fix(l10n): Update translations from Transifex" -s || true
git push origin master
echo "done"
