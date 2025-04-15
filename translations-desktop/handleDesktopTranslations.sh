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

  # Migrate the transifex config to the new client version
  tx migrate
  git add .tx/config
  rm .tx/config_*
  git commit -am "fix(l10n): Update Transifex configuration" -s || true
  git push origin $version

  if [[ -f './resources.qrc' ]]; then
    resources="resources.qrc"
  else
    resources=""
  fi

  lupdate src/gui/ src/cmd/ src/common/ src/crashreporter/ src/csync/ src/libsync/ $resources -ts /branches/$version.ts
done

# Merge source translation files and filter duplicates
lconvert -i /branches/*.ts -o /merged_en.ts

# Fix missing <numerusform> elements (always two are required but lconvert strips out one)
# Fix paths, changed by lconvert
sed -e 's/<numerusform><\/numerusform>/<numerusform><\/numerusform><numerusform><\/numerusform>/' -e 's/app\/desktop\/src/src/' /merged_en.ts > translations/client_en.ts

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
  tx pull -f --minimum-perc=25 -a

  rm -rf translations/client_de.ts
  mv translations/client_de_DE.ts translations/client_de.ts
  rm -rf nextcloud.client-desktop/de_translation.desktop
  mv nextcloud.client-desktop/de_DE_translation.desktop nextcloud.client-desktop/de_translation.desktop

  # create git commit and push it
  git add .
  git commit -am "fix(l10n): Update translations from Transifex" -s || true
  git push origin $version
  echo "done with $version"
done
