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

# push sources
tx push -s

# pull translations
tx pull -f -a

# Use de_DE instead of de
# 1. Remove informal german keys
# 2. Replace formal german with informal german key
# 3. Save again
cat Sources/SwiftNextcloudUI/Localizable.xcstrings | jq '. | del(.strings .[] .localizations .de)' | sed 's/"de_DE": {/"de": {/' > Sources/SwiftNextcloudUI/Localizable.xcstrings

# create git commit and push it
git add .
git commit -am "fix(l10n): Update translations from Transifex" -s || true
git push origin main
echo "done"
