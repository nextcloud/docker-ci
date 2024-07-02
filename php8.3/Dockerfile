FROM debian:bookworm
RUN apt-get update && apt-get install -y wget gnupg2 libzip4 apt-transport-https lsb-release ca-certificates && \
    wget -O /etc/apt/trusted.gpg.d/php.gpg https://packages.sury.org/php/apt.gpg && \
    echo "deb https://packages.sury.org/php/ $(lsb_release -sc) main" > /etc/apt/sources.list.d/php.list && \
    apt-get update && apt-get install -y php8.3-intl php8.3-gd git curl \
    php8.3-cli php8.3-curl php8.3-pgsql php8.3-ldap \
    php8.3-sqlite php8.3-mysql php8.3-zip php8.3-xml \
    php8.3-redis php8.3-imagick php8.3-xdebug php8.3-apcu \
    php8.3-mbstring make libmagickcore-6.q16-6-extra && \
    apt-get autoremove -y && apt-get autoclean && apt-get clean && \
    rm -rf /tmp/* /var/tmp/* /var/lib/apt/lists/*

RUN phpenmod zip intl gd systemd
RUN curl -O -L https://phar.phpunit.de/phpunit-9.5.28.phar \
    && chmod +x phpunit-9.5.28.phar \
    && mv phpunit-9.5.28.phar /usr/local/bin/phpunit
RUN curl -O -L https://getcomposer.org/download/2.7.7/composer.phar \
    && chmod +x composer.phar \
    && mv composer.phar /usr/local/bin/composer

RUN phpdismod xdebug
ADD nextcloud.ini /etc/php/8.3/cli/conf.d/nextcloud.ini
