#!/bin/sh

# verbose and exit on error
set -xe

# Print tooling information
tx -v

# import GPG keys
gpg --import /gpg/nextcloud-bot.public.asc
gpg --allow-secret-key-import --import /gpg/nextcloud-bot.asc
gpg --list-keys

# fetch git repo
git clone git@github.com:nextcloud/notes-ios /app

# remove all translations (they are added afterwards anyways but allows to remove languages via transifex)
rm -r Source/*.lproj/Localizable.strings
rm -r Source/*.lproj/Main_iPhone.strings
rm -r Source/*.lproj/Categories.strings
rm -r Source/Settings.bundle/*.lproj/Root.strings
rm -r Source/Screens/Settings/*.lproj/Settings.strings

git checkout -- Source/en.lproj/Localizable.strings
git checkout -- Source/en.lproj/Main_iPhone.strings
git checkout -- Source/en.lproj/Categories.strings
git checkout -- Source/Settings.bundle/en.lproj/Root.strings
git checkout -- Source/Screens/Settings/en.lproj/Settings.strings


# push sources
tx push -s

# pull translations
tx pull -f -a


# use de_DE instead of de
rm -rf Source/de.lproj/Localizable.strings
rm -rf Source/de.lproj/Main_iPhone.strings
rm -rf Source/de.lproj/Categories.strings
rm -rf Source/Settings.bundle/de.lproj/Root.strings
rm -rf Source/Screens/Settings/de.lproj/Settings.strings

mv Source/de_DE.lproj/Localizable.strings Source/de.lproj/Localizable.strings
mv Source/de_DE.lproj/Main_iPhone.strings Source/de.lproj/Main_iPhone.strings
mv Source/de_DE.lproj/Categories.strings Source/de.lproj/Categories.strings
mv Source/Settings.bundle/de_DE.lproj/Root.strings Source/Settings.bundle/de.lproj/Root.strings
mv Source/Screens/Settings/de_DE.lproj/Settings.strings Source/Screens/Settings/de.lproj/Settings.strings

# create git commit and push it
git add .
git commit -am "fix(l10n): Update translations from Transifex" -s || true
git push origin develop
echo "done"
