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

# Convert a translated PO file to the l10n files of Nextcloud
rm -f l10n/de.js l10n/de.json
if ! php ../src/translationtool.php convert-po-files > /dev/null; then
	echo 'Converting the PO files failed'
	exit 1
fi

if ! git diff --quiet -- l10n/ || [ -n "$(git ls-files --others --exclude-standard -- l10n/)" ]; then
	echo 'l10n files changed'
	git status --short -- l10n/
	git diff l10n/
	exit 1
fi

# Report translation calls that xgettext cannot extract
php ../src/translationtool.php check-files | grep -E '^(\*\* Warning|> Starting|  ->)' | sed -e "s#${PWD}/##" > expected-check-files.txt

CHANGED_LINES=$(git diff expected-check-files.txt | wc -l)

if ! [[ "$CHANGED_LINES" = "0" ]]; then
	echo 'check-files output changed'
	git diff expected-check-files.txt
	exit 1
fi

# Lint the source strings missing in the base checkout, as plain text and as GitHub annotations, then all of them
lint() {
	php ../src/translationtool.php lint-source-strings "$@"
	echo "exit $?"
}
{
	lint --base=../tests-base
	lint --base=../tests-base --format=github
	lint
} | grep -E ': (error|warning): |^::|source strings$|^exit ' > expected-lint.txt

CHANGED_LINES=$(git diff expected-lint.txt | wc -l)

if ! [[ "$CHANGED_LINES" = "0" ]]; then
	echo 'Lint result changed'
	git diff expected-lint.txt
	exit 1
fi
