FROM debian
RUN apt-get update && apt-get install -y php5-intl php5-gd git curl
RUN php5enmod zip intl gd
RUN curl -O -L https://phar.phpunit.de/phpunit-4.8.24.phar \
	&& chmod +x phpunit-4.8.24.phar \
	&& mv phpunit-4.8.24.phar /usr/local/bin/phpunit

ADD nextcloud.ini /usr/local/etc/php/conf.d/nextcloud.ini
