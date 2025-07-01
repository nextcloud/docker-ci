#!/bin/sh

# verbose and exit on error
set -xe

# Print tooling information
php -v
tx -v
xgettext -V

# import GPG keys
gpg --import /gpg/nextcloud-bot.public.asc
gpg --allow-secret-key-import --import /gpg/nextcloud-bot.asc
gpg --list-keys

# fetch git repo
git clone git@github.com:nextcloud/server /app/default --depth 1

##################################
# Migrate the transifex config to the new client version
##################################
cd /app/default
tx migrate
git add .tx/config
rm .tx/config_*
git commit -am "fix(l10n): Update Transifex configuration" -s || true
git push

##################################
# Prepare sync setup
##################################
versions='master stable31 stable30'

mkdir stable-templates
mkdir -p translationfiles/templates/

##################################
# Clone backport branches
##################################
# Don't fail on checking out non existing branches
set +e
for version in $versions
do
  git clone git@github.com:nextcloud/server /app/$version --depth 1 --branch $version
done
set -e

##################################
# Build POT files for all versions
##################################
for version in $versions
do
  if [ ! -d /app/$version ]; then
    # skip if the branch doesn't exist
    continue
  fi

  cd /app/$version

  # build POT files
  /translationtool.phar create-pot-files

  cd translationfiles/templates/
  for file in $(ls)
  do
    mv $file /app/default/stable-templates/$version.$file
  done
  cd ../..
done

##################################
# Sync with transifex
##################################
cd /app/default

# merge POT files into one
for file in $(ls stable-templates/master.*)
do
  # Change below to 23 when server switches to main
  name=$(echo $file | cut -b 25- )
  msgcat --use-first stable-templates/*.$name > translationfiles/templates/$name
done

git checkout -b nickv/$(date '+%H%M')
git add translationfiles/templates

git commit -am "fix(l10n): Update translations from Transifex" -s || true
git push origin nickv/$version/$(date '+%H%M')
