FROM debian:bullseye
RUN apt-get update && apt-get install -y wget gnupg2 libzip4 apt-transport-https lsb-release ca-certificates && \
    wget -O /etc/apt/trusted.gpg.d/php.gpg https://packages.sury.org/php/apt.gpg && \
    echo "deb https://packages.sury.org/php/ $(lsb_release -sc) main" > /etc/apt/sources.list.d/php.list && \
    apt-get update && apt-get install -y php8.2-intl php8.2-gd git curl \
    php8.2-cli php8.2-curl php8.2-pgsql php8.2-ldap \
    php8.2-sqlite php8.2-mysql php8.2-zip php8.2-xml \
    php8.2-redis php8.2-imagick php8.2-xdebug php8.2-apcu \
    php8.2-mbstring make libmagickcore-6.q16-6-extra && \
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
ADD nextcloud.ini /etc/php/8.2/cli/conf.d/nextcloud.ini
