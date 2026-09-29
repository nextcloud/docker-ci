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
git clone git@github.com:nextcloud/NextcloudKit /app --depth 1

CATALOG=Sources/NextcloudKitUI/Localizable.xcstrings

# An XCSTRINGS upload replaces the translations of every language with the file's content,
# so pull the current translations first and upload them unchanged together with the source strings.

# pull translations: any one language returns the whole catalog with all languages,
# Transifex is made to work with a single file per language, unlike string catalogs. In this case we specify a language (de_DE) as it's required, but the .xcstrings contains all languages.
# (onlytranslated leaves out the English fallbacks for untranslated strings)
tx pull --force --languages de_DE --mode onlytranslated

# never push without the translations from Transifex, the upload would delete all of them
COUNT_TRANSLATIONS='[.strings[] | (.localizations // {}) | keys[] | select(. != "en")] | length'
ONLINE_TRANSLATIONS=$(jq "$COUNT_TRANSLATIONS" translations/de_DE.xcstrings)
REPO_TRANSLATIONS=$(jq "$COUNT_TRANSLATIONS" "$CATALOG")

if [ "$ONLINE_TRANSLATIONS" -eq 0 ] && [ "$REPO_TRANSLATIONS" -gt 0 ]; then
  echo "Transifex returned no translations while the catalog has $REPO_TRANSLATIONS, not pushing"
  exit 1
fi

# push sources: our strings without stale ones (not present in code, detected by Xcode), with the translations from Transifex
jq --slurpfile online translations/de_DE.xcstrings '
  .strings |= with_entries( 
    select(.value.extractionState != "stale")
    | .key as $key
    | ((.value.localizations // {} | with_entries(select(.key == "en"))) + ($online[0].strings[$key].localizations // {} | del(.en))) as $localizations
    | if $localizations == {} then .value |= del(.localizations) else .value.localizations = $localizations end
  )
' "$CATALOG" > FixedLocalizable.xcstrings
mv -f FixedLocalizable.xcstrings "$CATALOG"
tx push -s

# use de_DE instead of de, and sr-Latn for sr@latin, which iOS doesn't recognize (other Transifex codes work as they are)
jq '
  {"de_DE": "de", "sr@latin": "sr-Latn"} as $codes
  | .strings[] |= (if .localizations then .localizations |= (del(.de) | with_entries(.key |= ($codes[.] // .)) | to_entries | sort_by(.key) | from_entries) else . end)
' "$CATALOG" > FixedLocalizable.xcstrings

# keep Xcode's "key" : value formatting
sed -E 's/^( *"([^"\\]|\\.)*"): /\1 : /' FixedLocalizable.xcstrings > "$CATALOG"
rm -rf FixedLocalizable.xcstrings translations

# create git commit and push it
git add "$CATALOG"
git commit -m "fix(l10n): Update translations from Transifex" -s || true
git push origin main
echo "done"
