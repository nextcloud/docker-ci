#!/bin/sh

# verbose and exit on error
set -xe

# Load the string freeze helpers
. /stringFreeze.sh

# Print tooling information
php -v
tx -v
xgettext -V

# import GPG keys
gpg --import /gpg/nextcloud-bot.public.asc
gpg --allow-secret-key-import --import /gpg/nextcloud-bot.asc
gpg --list-keys

# fetch git repo
# the main branch of the viewer app is a library without translations,
# so only its hardcoded stable branches are translated
DEFAULT_BRANCH_ARG=''
if [ "$2" = 'viewer' ]; then
  DEFAULT_BRANCH_ARG='--branch stable35'
fi
git clone git@github.com:$1/$2 /app/default --depth 1 $DEFAULT_BRANCH_ARG

cd default

if [ ! -f '.tx/config' ]; then
  echo "Missing transifex configuration file .tx/config"
  exit 1
fi

##################################
# Migrate the transifex config to the new client version
##################################
tx migrate
git add --force .tx/config
rm .tx/config_*
git commit -am "fix(l10n): Update Transifex configuration" -m "[skip ci]" -s || true
git push

##################################
# Validate sync setup
##################################
DAY_OF_WEEK=$(date "+%w")
RESOURCE_ID=$(grep -oE '\[o:nextcloud:p:nextcloud:r:.*\]' .tx/config | sed -E 's/\[o:nextcloud:p:nextcloud:r:(.*)\]/\1/')

if [ "$RESOURCE_ID" = "talk_desktop" ]; then
  echo "Skipping release check of Talk Desktop"
elif [ $DAY_OF_WEEK -eq 6 ]; then
  APP_MAX_VERSION=$(grep -oE '<nextcloud.*max-version=".*".*>' appinfo/info.xml | head --lines 1 | sed -E 's/(.*)max-version="(.*)"(.*)/\2/')
  if [ ! $APP_MAX_VERSION ]; then
    echo "App has no max-version defined"
    grep '<nextcloud' appinfo/info.xml | head --lines 1
    exit 1
  elif [ $APP_MAX_VERSION -lt 31 ]; then
    echo "App was not released in the last year and translations should be stopped"
    echo "Defined max-version is $APP_MAX_VERSION"
    exit 1
  fi

  echo "App has max-version $APP_MAX_VERSION"
fi

APP_ID=$(grep -oE '<id>.*</id>' appinfo/info.xml | head --lines 1 | sed -E 's/<id>(.*)<\/id>/\1/')
IS_EX_APP=$(grep -q '<external-app>' appinfo/info.xml && grep -q '</external-app>' appinfo/info.xml && echo "true" || echo "false")
SOURCE_FILE=$(grep -oE '^source_file\s*=\s*(.+)$' .tx/config | sed -E 's/source_file\s*=\s*(.+)/\1/')

if [ "$RESOURCE_ID" = "MYAPP" ]; then
  echo "Invalid transifex configuration file .tx/config (translating MYAPP instead of real value)"
  exit 2
fi

if [ "$RESOURCE_ID" = "talk_desktop" ]; then
  # Desktop client has no appinfo/info.xml
  APP_ID="talk_desktop"
fi

versions='main master stable35 stable34 stable33'
if [ -f '.tx/backport' ]; then
  versions="main master $(cat .tx/backport)"
fi
if [ "$APP_ID" = "viewer" ]; then
  # only translate the stable branches of the viewer app that are still translated globally
  versions=$(echo "$versions" | tr ' ' '\n' | grep -xF -e stable35 -e stable34 -e stable33 | tr '\n' ' ')
fi

# During a string freeze no new strings may enter the translation system.
# This only affects apps that are shipped with the server, all other apps keep
# syncing their strings. For shipped apps the branch with the new strings is
# skipped completely and the newest stable branch defines the source strings.
STRING_FREEZE='false'
TEMPLATE_BRANCHES='master main'
if [ "$APP_ID" = "viewer" ]; then
  TEMPLATE_BRANCHES='stable35'
fi
if is_string_freeze "$versions" && is_shipped_app "$APP_ID"; then
  STRING_FREEZE='true'
  TEMPLATE_BRANCHES=$(newest_stable_branch "$versions")
  echo "String freeze is active and $APP_ID is shipped: not syncing new strings from main and master, using $TEMPLATE_BRANCHES instead"
fi

mkdir stable-templates
mkdir -p translationfiles/templates/

##################################
# Clone backport branches
##################################
# Don't fail on checking out non existing branches
set +e
for version in $versions
do
  git clone git@github.com:$1/$2 /app/$version --depth 1 --branch $version
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

  if [ "$STRING_FREEZE" = 'true' ] && { [ "$version" = 'master' ] || [ "$version" = 'main' ]; }; then
    # during the string freeze the new strings of the development branch
    # must not be added to the translation system
    echo "Skipping templates of $version during the string freeze"
    continue
  fi

  cd /app/$version

  # build POT files
  /translationtool.phar create-pot-files

  cd translationfiles/templates/
  for file in $(ls)
  do
    FILE_SAVE_VERSION=$(echo $version | sed -E 's/\//-/')
    mv $file /app/default/stable-templates/$FILE_SAVE_VERSION.$RESOURCE_ID.pot
  done
  cd ../..
done


##################################
# Sync with transifex
##################################
cd /app/default

# merge POT files into one
# the last branch of the list wins, so main takes precedence over master
HAS_TEMPLATES='false'
for template_branch in $TEMPLATE_BRANCHES
do
  for file in $(ls stable-templates/$template_branch.* 2>/dev/null)
  do
    HAS_TEMPLATES='true'
    name=${file#stable-templates/$template_branch.}
    # the template branch comes first, so duplicated strings keep its comments
    msgcat --use-first $file $(ls stable-templates/*.$name | grep -vxF "$file") > $SOURCE_FILE
  done
done

# remove intermediate POT files
rm -rf stable-templates

# push sources
if [ "$HAS_TEMPLATES" = 'true' ]; then
  tx push -s
else
  echo "No source templates found for branch $TEMPLATE_BRANCHES, not pushing sources"
fi

# pull translations - force pull because a fresh clone has newer time stamps
tx pull -f -a --minimum-perc=5

# delete removed l10n files that are used for language detection (they will be recreated during the write)
rm -f l10n/*.js l10n/*.json

# Copy back the po files from transifex resource id to app id
if [ "$RESOURCE_ID" = "$APP_ID" ] ; then
  echo 'App id and transifex resource id are the same, not renaming po files …'
else
  echo "App id [$APP_ID] and transifex resource id [$RESOURCE_ID] mismatch"
  echo 'Renaming po files …'
  for file in $(ls translationfiles)
  do
    if [ "$file" = 'templates' ]; then
      continue;
    fi

    # Some special handling for apps where the resource name is reserved by transifex (transfer, analytics, ...)
    # in that case the downloaded ".po" files already have the correct name, so we skip the renaming.
    if [ -f translationfiles/$file/$RESOURCE_ID.po ]; then
      mv translationfiles/$file/$RESOURCE_ID.po translationfiles/$file/$APP_ID.po
    fi
  done
fi

# build JS/JSON based on translations
/translationtool.phar convert-po-files

##################################
# Workflow template sync helpers
##################################
# Only nextcloud/ repositories, without the clone sync_workflows is a no-op
if [ "$1" = 'nextcloud' ]; then
  set +e
  git clone git@github.com:nextcloud/.github /app/.github-templates --depth 1
  set -e
fi

# Merge freeze: block-merge-freeze.yml exists and server branch is in late RC
is_merge_freeze() {
  [ -f .github/workflows/block-merge-freeze.yml ] || return 1
  case "$1" in
    main|master) FREEZE_REF='master' ;;
    stable[0-9]*) FREEZE_REF="$1" ;;
    *) return 1 ;;
  esac
  curl -sf "$SERVER_RAW_URL/$FREEZE_REF/version.php" | grep 'OC_VersionString' | grep -q -i -E '(\.0\.0 RC[3-9]|\.[1-9][0-9]* RC)'
}

# Copies changed templates into the current checkout, reapplies *.yml.patch files
# and updates .github/actions-lock.txt. Changes are left uncommitted.
# Sets WORKFLOW_PATCH_FAILED and WORKFLOW_SUMMARY
sync_workflows() {
  WORKFLOW_PATCH_FAILED='false'
  WORKFLOW_SUMMARY=''
  [ -d /app/.github-templates/workflow-templates ] || return 0
  # Opt-in: apps must have the lock file (empty is fine)
  if [ ! -f .github/actions-lock.txt ]; then
    echo "No .github/actions-lock.txt on $1, skipping workflow sync"
    return 0
  fi
  if is_merge_freeze "$1"; then
    echo "Merge freeze is active for $1, skipping workflow sync"
    return 0
  fi
  if [ ! -s .github/actions-lock.txt ]; then
    echo "# SPDX-FileCopyrightText: $(date +%Y) Nextcloud GmbH and Nextcloud contributors" > .github/actions-lock.txt
    echo "# SPDX-License""-Identifier: MIT" >> .github/actions-lock.txt
  fi

  for workflow in /app/.github-templates/workflow-templates/*.yml; do
    filename=$(basename "$workflow")
    target_file=".github/workflows/$filename"
    # Only update workflows the app already uses
    [ -f "$target_file" ] || continue

    locked_version=$(grep " $filename\$" .github/actions-lock.txt | cut -f 1 -d ' ' || true)
    new_version=$(md5sum "$workflow" | cut -f 1 -d ' ')
    [ "$locked_version" = "$new_version" ] && continue

    echo "Updating workflow $filename"
    WORKFLOW_SUMMARY="$WORKFLOW_SUMMARY
- 🆙 Updated [$filename](https://github.com/$ORG/.github/commits/HEAD/workflow-templates/$filename)"
    cp "$workflow" "$target_file"

    if [ -f "$target_file.patch" ]; then
      # --forward: never prompt about reversed patches (container may have a tty)
      if patch --forward -p1 < "$target_file.patch"; then
        WORKFLOW_SUMMARY="$WORKFLOW_SUMMARY
  - Patch applied"
      else
        WORKFLOW_SUMMARY="$WORKFLOW_SUMMARY
  - [ ] ❌ Patch failed"
        WORKFLOW_PATCH_FAILED='true'
      fi
    fi

    if [ -n "$locked_version" ]; then
      sed -i "s/$locked_version $filename/$new_version $filename/" .github/actions-lock.txt
    else
      echo "$new_version $filename" >> .github/actions-lock.txt
    fi
  done
}

# Opens a draft PR (same branch name as the sync-workflow-templates action, so
# both converge). Needs GITHUB_TOKEN with pull-request write access.
create_workflow_pr() {
  if [ -z "$GITHUB_TOKEN" ]; then
    echo "GITHUB_TOKEN not set, open the PR manually: https://github.com/$1/$2/compare/$3...$4"
    return 0
  fi
  set +x # do not leak the token
  curl -sS -X POST \
    -H "Authorization: Bearer $GITHUB_TOKEN" \
    -H 'Accept: application/vnd.github+json' \
    "https://api.github.com/repos/$1/$2/pulls" \
    -d "$(jq -n --arg base "$3" --arg head "$4" --arg body "Automated update of all workflow templates from [$1/.github](https://github.com/$1/.github)
$WORKFLOW_SUMMARY" \
      '{title: "[\($base)] ci(actions): Update workflow templates from organization template repository", head: $head, base: $base, body: $body, draft: true}')" \
    || echo 'Creating the pull request failed (it may already exist)'
  set -x
}
ORG=$1

##################################
# Add translations to branches again
##################################
for version in $versions
do
  if [ ! -d /app/$version ]; then
    # skip if the branch doesn't exist
    continue
  fi

  cd /app/$version

  # delete removed l10n files that are used for language detection (they will be recreated during the write)
  rm -f l10n/*.js l10n/*.json

  # Copy JS and JSON
  cp /app/default/l10n/*.js /app/default/l10n/*.json l10n

  # create git commit and push it
  git add --force l10n/*.js l10n/*.json

  # for ExApps, we need to include .po translation files as well
  if [ "$IS_EX_APP" = "true" ]; then
    cp /app/default/translationfiles/*.po translationfiles
    git add --force translationfiles/*.po
  fi

  git commit -am "fix(l10n): Update translations from Transifex" -m "[skip ci]" -s || true

  ##################################
  # Sync workflow templates from the organization .github repository
  # (same logic as workflow-templates/sync-workflow-templates.yml)
  ##################################
  sync_workflows "$version"

  if [ -z "$(git status --porcelain -- .github/workflows/*.yml .github/actions-lock.txt)" ]; then
    git push origin $version
  elif [ "$WORKFLOW_PATCH_FAILED" = 'false' ]; then
    # Push translations and workflow updates in one go
    git add .github/workflows/*.yml .github/actions-lock.txt
    git commit -m "ci(actions): Update workflow templates from organization template repository" -m "[skip ci]" -s
    git push origin $version
  else
    # Translations go directly to the branch, workflows need a human
    git push origin $version
    WORKFLOW_BRANCH="automated/noid/$version-update-workflows"
    git checkout -b "$WORKFLOW_BRANCH"
    git add .github/workflows/*.yml .github/actions-lock.txt
    git commit -m "ci(actions): Update workflow templates from organization template repository" -s
    git push --force origin "$WORKFLOW_BRANCH"
    create_workflow_pr "$1" "$2" "$version" "$WORKFLOW_BRANCH"
  fi

  echo "done with $version"
done

# End of verbose mode
set +xe

##################################
# Validate translations
##################################
if [ -f '.tx/validate' ]; then
  /validateTranslationFiles.sh /app/default
fi

exit $?
