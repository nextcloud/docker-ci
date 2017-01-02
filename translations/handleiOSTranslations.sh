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

# remove folder that doesn't contain all translations
l10nFolder=$(ls -1 | grep lproj)

for folder in $l10nFolder; do
    count=$(ls -1 $folder | wc -l)
    if [ $count != "6" ]; then
        echo remove $folder
        rm -rf ./$folder
    fi
done

rm -rf ./de_DE.lproj

# create git commit and push it
git add .
git commit -am "[tx-robot] updated from transifex" || true
git push origin master
echo "done"
