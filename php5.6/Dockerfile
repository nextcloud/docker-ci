FROM debian:jessie
RUN apt-get update && apt-get install -y php5-intl php5-gd git curl \
    php5-cli php5-curl php5-imagick php5-pgsql php5-mcrypt php5-ldap \
    php5-apcu php5-redis php5-sqlite php5-mysql wget make libmagickcore-6.q16-2-extra \
    && apt-get autoremove -y && apt-get autoclean && apt-get clean && \
    rm -rf /tmp/* /var/tmp/* /var/lib/apt/lists/*
RUN php5enmod zip intl gd
RUN curl -O -L https://phar.phpunit.de/phpunit-5.5.4.phar \
    && chmod +x phpunit-5.5.4.phar \
    && mv phpunit-5.5.4.phar /usr/local/bin/phpunit
RUN curl -O -L https://getcomposer.org/download/1.1.2/composer.phar \
    && chmod +x composer.phar \
    && mv composer.phar /usr/local/bin/composer

ADD nextcloud.ini /etc/php5/cli/conf.d/nextcloud.ini
