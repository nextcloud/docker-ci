#!/bin/sh

##################################
# Lock languages of a Transifex resource
#
# Tags every source string of the resource with "locked_<language>", so
# translators can not edit a language the repository maintains itself.
# Strings that already carry the tags are left alone, so after the first run
# only new source strings are touched.
#
# Usage: lockLanguages.sh <resource id> <file with one language code per line>
# Run it in the root of the repository, as the lang_map of .tx/config is used.
##################################

RESOURCE=$1
LANGUAGES_FILE=$2

API=$(sed -n -E 's/^rest_hostname *= *//p' ~/.transifexrc | head -n 1)
API=${API:-https://rest.api.transifex.com}
TOKEN=$(sed -n -E 's/^token *= *//p' ~/.transifexrc | head -n 1)
# A transifexrc that was not migrated yet has the token as password of the "api" user
TOKEN=${TOKEN:-$(sed -n -E 's/^password *= *//p' ~/.transifexrc | head -n 1)}

if [ -z "$TOKEN" ]; then
  echo 'No Transifex API token found in ~/.transifexrc' 1>&2
  exit 1
fi

# Print the Transifex code of a local language code,
# lang_map entries are "<transifex code>: <local code>"
transifex_language() {
  MAPPED=$(sed -n -E 's/^lang_map *= *//p' .tx/config | tr ',' '\n' | tr -d ' ' | awk -F: -v code="$1" '$2 == code { print $1 }' | head -n 1)
  echo "${MAPPED:-$1}"
}

WANTED_TAGS='[]'
for language in $(grep -E '^[A-Za-z0-9_@]+$' "$LANGUAGES_FILE")
do
  WANTED_TAGS=$(jq -c -n --argjson tags "$WANTED_TAGS" --arg tag "locked_$(transifex_language "$language")" '$tags + [$tag]')
done

if [ "$WANTED_TAGS" = '[]' ]; then
  echo "No languages listed in $LANGUAGES_FILE, nothing to lock"
  exit 0
fi

WORK_DIR=$(mktemp -d)
trap 'rm -rf "$WORK_DIR"' EXIT

# Collect the strings that miss at least one of the tags
URL="$API/resource_strings?filter[resource]=$RESOURCE&limit=1000"
while [ -n "$URL" ]
do
  curl --silent --show-error --fail --globoff --retry 3 \
    -H "Authorization: Bearer $TOKEN" \
    -o "$WORK_DIR/page.json" "$URL" || {
    echo "Could not read the source strings of $RESOURCE" 1>&2
    exit 1
  }

  jq -c --argjson wanted "$WANTED_TAGS" '
    .data[]
    | (.attributes.tags // []) as $tags
    | ($wanted - $tags) as $missing
    | select($missing | length > 0)
    | {type: "resource_strings", id: .id, attributes: {tags: ($tags + $missing)}}
  ' "$WORK_DIR/page.json" >> "$WORK_DIR/updates.jsonl"

  URL=$(jq -r '.links.next // empty' "$WORK_DIR/page.json")
done

# The bulk endpoint takes at most 150 strings per request
split -l 150 "$WORK_DIR/updates.jsonl" "$WORK_DIR/chunk."
for chunk in $(ls "$WORK_DIR"/chunk.* 2> /dev/null)
do
  jq -c -s '{data: .}' "$chunk" > "$WORK_DIR/body.json"
  curl --silent --show-error --fail --retry 3 \
    -X PATCH \
    -H "Authorization: Bearer $TOKEN" \
    -H 'Content-Type: application/vnd.api+json;profile="bulk"' \
    --data-binary "@$WORK_DIR/body.json" \
    -o /dev/null "$API/resource_strings" || {
    echo "Could not tag the source strings of $RESOURCE with $WANTED_TAGS" 1>&2
    exit 1
  }
done

echo "Tagged $(wc -l < "$WORK_DIR/updates.jsonl" | tr -d ' ') source strings of $RESOURCE with $WANTED_TAGS"
