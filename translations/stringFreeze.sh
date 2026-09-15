#!/bin/sh

##################################
# Helpers to handle the translation string freeze
#
# Between the first release candidate of a new major Nextcloud release and its
# final release no new source strings may enter the translation system.
# The freeze is active while the newest stable branch of nextcloud/server has an
# OC_VersionString like "35.0.0 RC3". Outside of the freeze the newest stable
# branch is on a maintenance version like "35.0.4 RC1" or "35.0.3".
##################################

SERVER_RAW_URL='https://raw.githubusercontent.com/nextcloud/server'

# Print the newest stable branch of a space separated branch list,
# e.g. "main master stable35 stable33" prints "stable35"
newest_stable_branch() {
  FREEZE_MAJOR=$(echo "$1" | tr ' ' '\n' | grep -E '^stable[0-9]+$' | sed -E 's/^stable//' | sort -n | tail -n 1)

  if [ -n "$FREEZE_MAJOR" ]; then
    echo "stable$FREEZE_MAJOR"
  fi
}

# Check whether the string freeze is active, based on the newest stable branch
# of the given space separated branch list. Returns 0 (true) when it is active.
is_string_freeze() {
  FREEZE_BRANCH=$(newest_stable_branch "$1")

  if [ -z "$FREEZE_BRANCH" ]; then
    echo 'No stable branch in the branch list, so there is no string freeze to respect'
    return 1
  fi

  FREEZE_VERSION_PHP=$(curl --silent --show-error --fail --location --retry 3 "$SERVER_RAW_URL/$FREEZE_BRANCH/version.php") || {
    echo "Could not read version.php of nextcloud/server branch $FREEZE_BRANCH"
    # Acting like freeze and trying tomorrow again
    return 0
  }

  FREEZE_VERSION_STRING=$(echo "$FREEZE_VERSION_PHP" | grep -oE "OC_VersionString *= *'[^']*'" | sed -E "s/.*'(.*)'/\1/")
  echo "Newest stable branch $FREEZE_BRANCH is at version \"$FREEZE_VERSION_STRING\""

  if [ -z "$FREEZE_VERSION_STRING" ]; then
    echo "Could not read the OC_VersionString of nextcloud/server branch $FREEZE_BRANCH"
    # Acting like freeze and trying tomorrow again
    return 0
  fi

  case "$FREEZE_VERSION_STRING" in
    *.0.0\ RC*)
      return 0
      ;;
  esac

  return 1
}

# Check whether the given app id is shipped with Nextcloud server.
# Returns 0 (true) when the app is listed in core/shipped.json
is_shipped_app() {
  FREEZE_SHIPPED_JSON=$(curl --silent --show-error --fail --location --retry 3 "$SERVER_RAW_URL/master/core/shipped.json") || {
    echo 'Could not read core/shipped.json of nextcloud/server'
    # Acting like freeze and trying tomorrow again
    return 0
  }

  echo "$FREEZE_SHIPPED_JSON" | jq --exit-status --arg app "$1" '(.shippedApps + .defaultEnabled + .alwaysEnabled) | index($app) != null' > /dev/null
}
