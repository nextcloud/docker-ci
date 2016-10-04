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
pip install Django==1.9.8

# create po files
./manage.py makemessages

# push sources
tx push -s

# pull translations - force pull because a fresh clone has newer time stamps
tx pull -f -a --minimum-perc=75

# don't add de_DE translation
rm -rf locale/de_DE/

# create git commit and push it
git add locale/
git commit -am "[tx-robot] updated from transifex" || true
git push origin master
echo "done"
