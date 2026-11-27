#!/bin/bash
php ../src/translationtool.php create-pot-files

# Remove unstable data like "POT-Creation-Date: 2024-09-10 14:25+0200\n"
cat translationfiles/templates/tests.pot | \
	# Remove meta data
	tail -n +21 | \
	# And remove the development path
	sed -e "s/${PWD//\//\\/}\\///" > expected.pot

CHANGED_LINES=$(git diff expected.pot | wc -l)

if ! [[ "$CHANGED_LINES" = "0" ]]; then
	echo 'POT file changed'
	git diff expected.pot
	exit 1
fi

# Batched extraction must give the pot file of one xgettext call per file
TOOL="$PWD/../src/translationtool.php"
APPS=$(mktemp -d)
trap 'chmod -R u+r "$APPS"; rm -rf "$APPS"' EXIT

# Without Vue files the first file has no strings, the pot file is still created by a single file and gets the same header
mkdir -p "$APPS/header/l10n" "$APPS/header/src"
echo "t('app', 'First string');" > "$APPS/header/src/a.js"
echo "t('app', 'Second string');" > "$APPS/header/src/b.js"
echo "t('app', 'Third string');" > "$APPS/header/src/c.js"
(cd "$APPS/header" && php "$TOOL" create-pot-files > /dev/null 2>&1)
if [ "$(grep -h '^msgid "[^"]' "$APPS"/header/translationfiles/templates/*.pot 2>/dev/null)" != $'msgid "First string"\nmsgid "Second string"\nmsgid "Third string"' ]; then
	echo 'Strings are missing in the pot file'
	exit 1
fi
if ! grep -q 'charset=UTF-8' "$APPS"/header/translationfiles/templates/*.pot; then
	echo 'The pot file header changed'
	exit 1
fi

# A file xgettext cannot read must not drop the strings of the other files
mkdir -p "$APPS/unreadable/l10n" "$APPS/unreadable/src"
echo "t('app', 'First string');" > "$APPS/unreadable/src/a.js"
echo "t('app', 'Unreadable string');" > "$APPS/unreadable/src/b.js"
echo "t('app', 'Third string');" > "$APPS/unreadable/src/c.js"
chmod 000 "$APPS/unreadable/src/b.js"
if [ -r "$APPS/unreadable/src/b.js" ]; then
	echo 'Skipping the unreadable file test, the file is still readable'
else
	(cd "$APPS/unreadable" && php "$TOOL" create-pot-files > /dev/null 2>&1)
	if [ "$(grep -h '^msgid "[^"]' "$APPS"/unreadable/translationfiles/templates/*.pot 2>/dev/null)" != $'msgid "First string"\nmsgid "Third string"' ]; then
		echo 'The strings of readable files were lost'
		exit 1
	fi
fi
