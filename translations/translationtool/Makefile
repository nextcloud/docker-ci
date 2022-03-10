.PHONY: translationtool.phar

translationtool.phar: install-composer-deps
	php -d phar.readonly=off vendor/bin/phar-composer build src
	chmod +x translationtool.phar

install-composer-deps: composer.phar
	php composer.phar install
	php composer.phar install -d src
	rm src/vendor/gettext/gettext/CHANGELOG.md
	rm src/vendor/gettext/gettext/composer.json
	rm src/vendor/gettext/gettext/CONTRIBUTING.md
	rm src/vendor/gettext/gettext/LICENSE
	rm src/vendor/gettext/gettext/phpcs.xml
	rm src/vendor/gettext/gettext/README.md
	rm src/vendor/gettext/languages/composer.json
	rm src/vendor/gettext/languages/LICENSE
	rm src/vendor/gettext/languages/UNICODE-LICENSE.txt

composer.phar:
	curl -sS https://getcomposer.org/installer | php

clean:
	rm -f translationtool.phar
	rm -rf vendor src/vendor
