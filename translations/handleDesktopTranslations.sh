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

# Generate source translation files for master and stable branches
mkdir /branches

versions="stable-2.6 master"
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
lconvert-qt5 -i /branches/*.ts -o translations/client_en.ts

# push sources
tx push -s

# undo local changes
git checkout -f --

# reverse version list to apply backports
backportVersions=$(echo $versions | awk '{for(i=NF;i>=1;i--) printf "%s ", $i;print ""}')
for version in $backportVersions
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
