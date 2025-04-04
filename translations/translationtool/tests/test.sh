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
