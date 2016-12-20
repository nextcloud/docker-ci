#!/bin/sh

# verbose and exit on error
set -xe

# import GPG keys
gpg --import /gpg/nextcloud-bot.public.asc
gpg --allow-secret-key-import --import /gpg/nextcloud-bot.asc
gpg --list-keys

# fetch git repo
git clone git@github.com:nextcloud/ios /app
cd iOSClient

# remove all translations (they are added afterwards anyways but allows to remove languages via transifex)
rm -r *.lproj
git checkout -- en.lproj

# push sources
tx push -s

# pull translations
tx pull -f -a --minimum-perc=75

# create git commit and push it
git add .
git commit -am "[tx-robot] updated from transifex" || true
git push origin master
echo "done"
