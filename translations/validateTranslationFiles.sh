#!/bin/sh

cd $1

EXIT_CODE=0
# Confirm German translation does not have the false "don't translate" warning
if [ $(jq '.translations[]' l10n/de.json | grep 'Benötigt keine Übersetzung. Hier wird nur die formelle Übersetzung verwendet (de_DE).' | wc -l) -ne 0 ]; then
  echo "German language file $1/l10n/de.json contains the 'Benötigt keine Übersetzung. Hier wird nur die formelle Übersetzung verwendet (de_DE).' hint." 1>&2
  EXIT_CODE=3
fi

# Confirm English source does not contain the pipe character which breaks the Symfony Translation library
if [ $(jq '.translations | keys[]' l10n/en_GB.json | grep '|' | wc -l) -ne 0 ]; then
  echo "" 1>&2
  echo "English source $1/l10n/en_GB.json contains the pipe character" 1>&2
  echo "---" 1>&2
  jq '.translations | keys[]' l10n/en_GB.json | grep '|' 1>&2
  EXIT_CODE=4
fi

# Confirm English source does not contain the unicode single quote character
if [ $(jq '.translations | keys[]' l10n/en_GB.json | grep -E '(´|’)' | wc -l) -ne 0 ]; then
  echo "" 1>&2
  echo "English source $1/l10n/en_GB.json contains unicode single quote character, that should be replaced by normal single quotes" 1>&2
  echo "---" 1>&2
  jq '.translations | keys[]' l10n/en_GB.json | grep -E '(´|’)' 1>&2
  EXIT_CODE=4
fi

# Confirm English source does not use triple dots
if [ $(jq '.translations | keys[]' l10n/en_GB.json | grep '\.\.\.' | wc -l) -ne 0 ]; then
  echo "" 1>&2
  echo "English source $1/l10n/en_GB.json contains three consecutive dots. Unicode … should be used instead" 1>&2
  echo "---" 1>&2
  jq '.translations | keys[]' l10n/en_GB.json | grep '\.\.\.' 1>&2
  EXIT_CODE=4
fi

# Check for leading or trailing spaces
if [ $(jq '.translations | keys[]' l10n/en_GB.json | grep -E '(^\"(\s|\\t|\\n)|(\s|\\t|\\n)\"$)' | wc -l) -ne 0 ]; then
  echo "" 1>&2
  echo "English source $1/l10n/en_GB.json contains leading or trailing white spaces, tabs or new lines" 1>&2
  echo "---" 1>&2
  jq '.translations | keys[]' l10n/en_GB.json | grep -E '(^\"(\s|\\t|\\n)|(\s|\\t|\\n)\"$)' 1>&2
  EXIT_CODE=4
fi

for file in $(ls l10n/*.json)
do
  # Make sure only RTL languages contain such characters
  if [ "$file" != "l10n/ar.json" -a "$file" != "l10n/fa.json" -a "$file" != "l10n/he.json" -a "$file" != "l10n/ps.json" -a "$file" != "l10n/ug.json" -a "$file" != "l10n/ur_PK.json" ]; then
    if [ $(jq '.translations[]' $file | grep -E '(\x{061C}|\x{0623}|\x{200E}|\x{200F}|\x{202A}|\x{202B}|\x{202C}|\x{202D}|\x{202E}|\x{2066}|\x{2067}|\x{2068}|\x{2069}|\x{206C}|\x{206D})' | wc -l) -ne 0 ]; then
      echo "$1/$file contains a RTL limited characters in the translations" 1>&2
      EXIT_CODE=5
    fi
  fi

  # Confirm translations do not contain the pipe character which breaks the Symfony Translation library
  if [ $(jq '.translations[]' $file | grep '|' | wc -l) -ne 0 ]; then
    echo "$1/$file contains the pipe character" 1>&2
    EXIT_CODE=6
  fi
done

cd -

exit $EXIT_CODE
