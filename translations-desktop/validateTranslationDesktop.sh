#!/bin/sh

EXIT_CODE=0

# Confirm English source does not use triple dots
if [ $(grep '\.\.\.' $1 | wc -l) -ne 0 ]; then
  echo "" 1>&2
  echo "English source $1 contains three consecutive dots. Unicode … should be used instead" 1>&2
  echo "---" 1>&2
  EXIT_CODE=4
fi

exit $EXIT_CODE
