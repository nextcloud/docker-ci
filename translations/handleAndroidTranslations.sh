#!/bin/sh

# verbose and exit on error
set -xe

# import GPG keys
gpg --import /gpg/nextcloud-bot.public.asc
gpg --allow-secret-key-import --import /gpg/nextcloud-bot.asc
gpg --list-keys

# fetch git repo
git clone git@github.com:nextcloud/android /app

# remove existing translations to cleanup not maintained languages
rm -rf src/main/res/values-*/strings.xml

# push sources
tx push -s

# pull translations
tx pull -f -a --minimum-perc=50

# copy transifex strings to fastlane
scripts/metadata/generate_metadata.py

# create git commit and push it
git add .
git commit -am "[tx-robot] updated from transifex" || true
git push origin master
echo "done"
