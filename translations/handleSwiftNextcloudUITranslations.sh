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
git clone git@github.com:nextcloud/swiftnextcloudui /app

# Remove non-english strings from xcstrings before pushing to transifex
cat Sources/SwiftNextcloudUI/Localizable.xcstrings | jq '. | .strings = (.strings | map_values(.localizations = {en: .localizations.en}))' > FixedLocalizable.xcstrings
mv -f FixedLocalizable.xcstrings Sources/SwiftNextcloudUI/Localizable.xcstrings

# push sources
tx push -s

git restore Sources/SwiftNextcloudUI/Localizable.xcstrings

# pull translations
tx pull -f -a

# create git commit and push it

git diff


#git add .
#git commit -am "fix(l10n): Update translations from Transifex" -s || true
#git push origin main
echo "done"
