.PHONY: translationtool.phar

translationtool.phar: install-composer-deps
	php -d phar.readonly=off vendor/bin/phar-composer build src
	chmod +x translationtool.phar

install-composer-deps: composer.phar
	php composer.phar install
	php composer.phar install -d src

composer.phar:
	curl -sS https://getcomposer.org/installer | php

clean:
	rm -f translationtool.phar
	rm -rf vendor src/vendor
