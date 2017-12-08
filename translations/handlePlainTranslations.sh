#!/bin/sh

# verbose and exit on error
set -xe

# import GPG keys
gpg --import /gpg/nextcloud-bot.public.asc
gpg --allow-secret-key-import --import /gpg/nextcloud-bot.asc
gpg --list-keys

# fetch git repo
git clone git@github.com:$1/$2 /app

# remove existing translations to cleanup not maintained languages
if [ -d src/main/res ]; then
  rm -rf src/main/res/values-*/strings.xml
fi

# push sources
tx push -s

# pull translations
tx pull -f -a --minimum-perc=50

if [ -e "scripts/metadata/generate_metadata.py" ]; then
  # copy transifex strings to fastlane
  python3 scripts/metadata/generate_metadata.py
fi

# create git commit and push it
git add .
git commit -am "[tx-robot] updated from transifex" || true
git push origin master
echo "done"
