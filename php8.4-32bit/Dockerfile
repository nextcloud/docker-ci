FROM i386/debian:stable
RUN apt-get update && apt-get install -y wget apt-transport-https lsb-release ca-certificates \
    git curl make unzip libmagickcore-7.q16-10-extra \
    php8.4-apcu php8.4-bz2 php8.4-cli php8.4-ctype php8.4-curl \
    php8.4-dom php8.4-fileinfo php8.4-gd php8.4-iconv \
    php8.4-imagick php8.4-intl php8.4-ldap php8.4-mbstring \
    php8.4-posix php8.4-redis php8.4-simplexml php8.4-sqlite \
    php8.4-sqlite3 php8.4-xdebug php8.4-xml \
    php8.4-xmlreader php8.4-xmlwriter php8.4-zip && \
    apt-get autoremove -y && apt-get autoclean && apt-get clean && \
    rm -rf /tmp/* /var/tmp/* /var/lib/apt/lists/*

RUN phpenmod zip intl gd systemd
RUN curl -O -L https://getcomposer.org/download/2.9.2/composer.phar \
    && chmod +x composer.phar \
    && mv composer.phar /usr/local/bin/composer

RUN phpdismod xdebug
ADD nextcloud.ini /etc/php/8.4/cli/conf.d/nextcloud.ini
