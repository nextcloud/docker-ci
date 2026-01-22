#!/bin/sh

if [ ! -d "temp" ]; then
	mkdir "temp"
fi

cd temp
echo $PWD

git clone git@github.com:$1/$2
cd $2

versions='main master stable33 stable32 stable31'
if [ -f '.tx/backport' ]; then
	versions="main master $(cat .tx/backport)"
fi

for version in $versions
do
	# skip if the branch doesn't exist
	if git branch -r | egrep "^\W*origin/$version$" ; then
		echo "Validating branch: $version"
		echo "---"
	else
		echo "Invalid branch: $version"
		continue
	fi
	git checkout $version

	if [ ! -f '.tx/config' ]; then
		echo 'Translation config .tx/config is missing'
	fi

	if [ ! -f 'l10n/.gitkeep' ]; then
		echo 'l10n/.gitkeep is missing'
	fi

	if [ ! -f '.l10nignore' ]; then
		echo 'Consider adding .l10nignore and exclude 3rd-party directories like vendor/ and vendor-bin/ as well as directories with compiled javascript assets like js/'
	fi

	echo 'Checking translation source strings'

	php ../../translations/translationtool/translationtool.phar prepare-source-validation
	../../translations/validateTranslationFiles.sh .
done

cd ../../
rm -rf temp

