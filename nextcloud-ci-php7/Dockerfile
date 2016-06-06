FROM php:7
RUN apt-get update && apt-get install -y \
        libfreetype6-dev \
        libjpeg62-turbo-dev \
        libpng12-dev \
        git \
    && docker-php-ext-configure gd --with-freetype-dir=/usr/include/ --with-jpeg-dir=/usr/include/ \
    && docker-php-ext-install gd zip \
    && docker-php-ext-enable gd zip
RUN curl -O -L https://phar.phpunit.de/phpunit-4.8.24.phar \
	&& chmod +x phpunit-4.8.24.phar \
	&& mv phpunit-4.8.24.phar /usr/local/bin/phpunit

ADD nextcloud.ini /usr/local/etc/php/conf.d/nextcloud.ini
