#!/bin/sh

# verbose and exit on error
set -xe

# import GPG keys
gpg --import /gpg/nextcloud-bot.public.asc
gpg --allow-secret-key-import --import /gpg/nextcloud-bot.asc
gpg --list-keys

# fetch git repo
git clone git@github.com:nextcloud/$1 /app
cd NextcloudApp/Strings

# push sources
tx push -s

# pull translations - force pull because a fresh clone has newer time stamps
tx pull -f -a --minimum-perc=75

# create git commit and push it
git add .
git commit -am "[tx-robot] updated from transifex" -s || true
git push origin master
echo "done"
