FROM debian:buster
RUN apt-get update && apt-get install -y wget gnupg2 libzip4 apt-transport-https lsb-release ca-certificates && \
    wget -O /etc/apt/trusted.gpg.d/php.gpg https://packages.sury.org/php/apt.gpg && \
    echo "deb https://packages.sury.org/php/ $(lsb_release -sc) main" > /etc/apt/sources.list.d/php.list && \
    apt-get update && apt-get install -y php8.0-intl php8.0-gd git curl \
    php8.0-cli php8.0-curl php8.0-pgsql php8.0-ldap \
    php8.0-sqlite php8.0-mysql php8.0-zip php8.0-xml \
    php8.0-redis php8.0-imagick php8.0-xdebug php8.0-apcu \
    php8.0-mbstring make libmagickcore-6.q16-2-extra && \
    apt-get autoremove -y && apt-get autoclean && apt-get clean && \
    rm -rf /tmp/* /var/tmp/* /var/lib/apt/lists/*

RUN phpenmod zip intl gd systemd

RUN curl -O -L https://phar.phpunit.de/phpunit-9.5.28.phar \
    && chmod +x phpunit-9.5.28.phar \
    && mv phpunit-9.5.28.phar /usr/local/bin/phpunit
RUN curl -O -L https://getcomposer.org/download/1.10.15/composer.phar \
    && chmod +x composer.phar \
    && mv composer.phar /usr/local/bin/composer

RUN phpdismod xdebug
ADD nextcloud.ini /etc/php/8.0/cli/conf.d/nextcloud.ini
