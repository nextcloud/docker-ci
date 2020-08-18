FROM jakzal/phpqa:latest

ENV BUILD_DEPS="autoconf file g++ gcc libc-dev pkg-config re2c"
ENV LIB_DEPS="libxml2-dev libpng-dev libldap2-dev"
ENV PHP_EXTENSIONS="xml intl gd ldap"

RUN apt-get update && apt-get install -y --no-install-recommends $BUILD_DEPS $LIB_DEPS && rm -rf /var/lib/apt/lists/* \
 && docker-php-ext-install $PHP_EXTENSIONS \
 && apt-get purge -y --auto-remove $BUILD_DEPS
