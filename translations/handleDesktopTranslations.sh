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

versions=$(git branch -r | grep "origin\/stable\-[0-9]\.[0-9]$" | cut -f2 -d"/")" master"

# Allow to manually limit translations to specified backport branches within the repo
if [[ -f '.tx/backport' ]]; then
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

  lupdate-qt5 src/gui/ src/cmd/ src/common/ src/crashreporter/ src/csync/ src/libsync/ -ts /branches/$version.ts
done

# Merge source translation files and filter duplicates
lconvert-qt5 -i /branches/*.ts -o /merged_en.ts

# Fix missing <numerusform> elements (always two are required but lconvert strips out one)
sed 's/<numerusform><\/numerusform>/<numerusform><\/numerusform><numerusform><\/numerusform>/' /merged_en.ts > translations/client_en.ts

# push sources
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
  tx pull -f -a --minimum-perc=25

  # create git commit and push it
  git add .
  git commit -am "[tx-robot] updated from transifex" || true
  git push origin $version
  echo "done with $version"
done
