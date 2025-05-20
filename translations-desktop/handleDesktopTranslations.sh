#!/bin/sh

# verbose and exit on error
set -xe

# import GPG keys
gpg --import /gpg/nextcloud-bot.public.asc
gpg --allow-secret-key-import --import /gpg/nextcloud-bot.asc
gpg --list-keys

# fetch git repo
git clone git@github.com:nextcloud/desktop.git
cd desktop

# Generate source translation files for master and stable-x.y branches
mkdir /branches

single_versions=$(git branch -r | grep "origin\/stable\-[0-9]\.[0-9]$" | cut -f2 -d"/" | sort -r | head -n 1)
double_versions=$(git branch -r | grep "origin\/stable\-[0-9]\.[0-9][0-9]$" | cut -f2 -d"/" | sort -r | head -n 1)
versions="$single_versions $double_versions master"

# Allow to manually limit translations to specified backport branches within the repo
if [ -f '.tx/backport' ]; then
  versions="$(cat .tx/backport) master"
fi

for version in $versions
do
  # skip if the branch doesn't exist
  if git branch -r | egrep "^\W*origin/$version$" ; then
    echo "Valid branch"
  else
    echo "Invalid branch"
    continue
  fi

  git checkout $version

  # Migrate the transifex config to the new client version
  tx migrate
  git add .tx/config
  rm .tx/config_*
  git commit -am "fix(l10n): Update Transifex configuration" -s || true
  git push origin $version

  if [ -f './resources.qrc' ]; then
    resources="resources.qrc"
  else
    resources=""
  fi

  if [ -d 'src/crashreporter/' ]; then
    crashreporter="src/crashreporter/"
  else
    crashreporter=""
  fi

  # copy existing translation file from the branch, this allows `lupdate` to e.g. keep existing plural forms
  cp "translations/client_en.ts" "/branches/$version.ts"

  lupdate -no-obsolete src/gui/ src/cmd/ src/common/ $crashreporter src/csync/ src/libsync/ $resources -ts /branches/$version.ts
done

# Merge source translation files and filter duplicates
# This will be used as the source file for Transifex, it contains all translatable strings from each branch
lconvert -i /branches/*.ts -o /merged_en.ts

# Fix paths, changed by lupdate/lconvert.  Needs to be done for all branch-specific translations and the combined one
for ts_file in /merged_en.ts /branches/*.ts; do
  sed -i -e 's,app/desktop/src,src,' "$ts_file"
done

# Copy merged translation to the repo to let `tx` use it as a source file, and push it to Transifex.
cp /merged_en.ts translations/client_en.ts
tx push -s

# undo local changes
git checkout -f --

# apply backports
for version in $versions
do
  # skip if the branch doesn't exist
  if git branch -r | egrep "^\W*origin/$version$" ; then
    echo "Valid branch"
  else
    echo "Invalid branch"
    continue
  fi

  git checkout $version

  # pull translations
  tx pull -f --minimum-perc=25 -a

  rm -rf translations/client_de.ts
  mv translations/client_de_DE.ts translations/client_de.ts
  rm -rf nextcloud.client-desktop/de_translation.desktop
  mv nextcloud.client-desktop/de_DE_translation.desktop nextcloud.client-desktop/de_translation.desktop
  if [ -f "/branches/$version.ts" ]; then
    cp "/branches/$version.ts" translations/client_en.ts
  fi

  # create git commit and push it
  git add .
  git commit -am "fix(l10n): Update translations from Transifex" -s || true
  git push origin $version
  echo "done with $version"
done
