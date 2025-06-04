#!/bin/sh

# verbose and exit on error
set -xe

# import GPG keys
gpg --import /gpg/nextcloud-bot.public.asc
gpg --allow-secret-key-import --import /gpg/nextcloud-bot.asc
gpg --list-keys

# fetch git repo
git clone git@github.com:nextcloud/swiftnextcloudui /app

# push sources
tx push -s

# pull translations
tx pull -f -a

# create git commit and push it
git add .
git commit -am "fix(l10n): Update translations from Transifex" -s || true
git push origin main
echo "done"
