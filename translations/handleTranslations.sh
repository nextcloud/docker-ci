#!/bin/sh

# verbose and exit on error
set -xe

# import GPG keys
gpg --import /gpg/nextcloud-bot.public.asc
gpg --allow-secret-key-import --import /gpg/nextcloud-bot.asc
gpg --list-keys

# fetch git repo
git clone git@github.com:nextcloud/server /app

# build PO file
cd l10n
perl ./l10n.pl read

# push sources
tx push -s

# pull translations
tx pull -a --minimum-perc=75

# build JS/JSON based on translations
perl ./l10n.pl write

cd ..

# remove tests/
rm -rf tests
git checkout -- tests/

# create git commit and push it
git add .
git commit -am "[tx-robot] updated from transifex" || true
git push origin master
echo "done"
