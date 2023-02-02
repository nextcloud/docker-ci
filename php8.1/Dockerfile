FROM debian:buster
RUN apt-get update && apt-get install -y wget gnupg2 libzip4 apt-transport-https lsb-release ca-certificates && \
    wget -O /etc/apt/trusted.gpg.d/php.gpg https://packages.sury.org/php/apt.gpg && \
    echo "deb https://packages.sury.org/php/ $(lsb_release -sc) main" > /etc/apt/sources.list.d/php.list && \
    apt-get update && apt-get install -y php8.1-intl php8.1-gd git curl \
    php8.1-cli php8.1-curl php8.1-pgsql php8.1-ldap \
    php8.1-sqlite php8.1-mysql php8.1-zip php8.1-xml \
    php8.1-redis php8.1-imagick php8.1-xdebug php8.1-apcu \
    php8.1-mbstring make libmagickcore-6.q16-2-extra && \
    apt-get autoremove -y && apt-get autoclean && apt-get clean && \
    rm -rf /tmp/* /var/tmp/* /var/lib/apt/lists/*

RUN phpenmod zip intl gd systemd
RUN curl -O -L https://phar.phpunit.de/phpunit-9.5.28.phar \
    && chmod +x phpunit-9.5.28.phar \
    && mv phpunit-9.5.28.phar /usr/local/bin/phpunit
RUN curl -O -L https://getcomposer.org/download/2.5.1/composer.phar \
    && chmod +x composer.phar \
    && mv composer.phar /usr/local/bin/composer

RUN phpdismod xdebug
ADD nextcloud.ini /etc/php/8.1/cli/conf.d/nextcloud.ini
