# Nextcloud translation tool

This tool is used to extract translatable texts from the source code and to create translation files that can be used by Nextcloud.

## Generating PO files

To extract the texts from the source code and create a pot file simply go to the folder of your app and run
`php /path/to/translationtool.phar create-pot-files`. Now the script will create a folder called *translationfiles* 
and put the generated pot file in there. One pot file will be created for each app.

You'll find the pot files in *translationfiles/templates/$app.pot*

## Generating Nextcloud files

Once you have the translated po files under *translationfiles/$lang/$app.po* you can create the js and json files used by Nextcloud.
To do so run `php /path/to/translationtool.phar convert-po-files`. The files are then put in the *l10n* folder of the apps.
