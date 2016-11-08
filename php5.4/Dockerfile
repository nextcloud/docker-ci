FROM debian:oldstable
RUN apt-get update && apt-get install -y php5-intl php5-gd git curl \
    php5-cli php5-curl php5-imagick php5-mcrypt php5-ldap php5-sqlite \
    make libmagickcore5-extra && \
    apt-get autoremove -y && apt-get autoclean && apt-get clean && \
    rm -rf /tmp/* /var/tmp/* /var/lib/apt/lists/*
RUN php5enmod zip intl gd
RUN curl -O -L https://phar.phpunit.de/phpunit-4.8.24.phar \
    && chmod +x phpunit-4.8.24.phar \
    && mv phpunit-4.8.24.phar /usr/local/bin/phpunit
RUN curl -O -L https://getcomposer.org/download/1.1.2/composer.phar \
    && chmod +x composer.phar \
    && mv composer.phar /usr/local/bin/composer

ADD nextcloud.ini /etc/php5/cli/conf.d/nextcloud.ini
