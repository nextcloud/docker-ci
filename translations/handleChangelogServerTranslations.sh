#!/bin/sh

# verbose and exit on error
set -xe

# import GPG keys
gpg --import /gpg/nextcloud-bot.public.asc
gpg --allow-secret-key-import --import /gpg/nextcloud-bot.asc
gpg --list-keys

# fetch git repo
git clone git@github.com:$1/$2 /app

# extract l10n strings into POT
php /translationtool/extract-l10n.php /app/data/

# push sources
tx push -s

# pull translations
tx pull -f -a --minimum-perc=50

# generate updated XML
php /translationtool/generate-xml.php /app/data/

# create git commit and push it
git add .
git commit -am "[tx-robot] updated from transifex" -s || true
git push origin master
echo "done"
