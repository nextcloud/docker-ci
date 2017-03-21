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
rm -r Supporting\ Files/*.lproj
git checkout -- Supporting\ Files/en.lproj

# push sources
tx push -s

# pull translations
tx pull -f -a --minimum-perc=75

cd Supporting\ Files

# remove folder that doesn't contain all translations
l10nFolder=$(ls -1 | grep lproj)

for folder in $l10nFolder; do
    count=$(ls -1 $folder | wc -l)
    if [ $count != "7" ]; then
        echo remove $folder
        rm -rf ./$folder
    fi
done

# use de_DE instead of de
rm -rf ./de.lproj
mv de_DE.lproj de.lproj

cd ..

# create git commit and push it
git add .
git commit -am "[tx-robot] updated from transifex" || true
git push origin master
echo "done"
