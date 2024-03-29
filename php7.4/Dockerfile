FROM debian:buster
RUN apt-get update && apt-get install -y wget gnupg2 libzip4 apt-transport-https lsb-release ca-certificates && \
    wget -O /etc/apt/trusted.gpg.d/php.gpg https://packages.sury.org/php/apt.gpg && \
    echo "deb https://packages.sury.org/php/ $(lsb_release -sc) main" > /etc/apt/sources.list.d/php.list && \
    apt-get update && apt-get install -y php7.4-intl php7.4-gd git curl \
    php7.4-cli php7.4-curl php7.4-pgsql php7.4-ldap \
    php7.4-sqlite php7.4-mysql php7.4-zip php7.4-xml \
    php7.4-mbstring make libmagickcore-6.q16-2-extra unzip \
    php7.4-redis php7.4-imagick php7.4-dev php7.4-xdebug php7.4-apcu \
    libsystemd-dev && \
    apt-get autoremove -y && apt-get autoclean && apt-get clean && \
    rm -rf /tmp/* /var/tmp/* /var/lib/apt/lists/*

RUN cd /tmp/ && wget https://github.com/nikic/php-ast/archive/master.zip && unzip master.zip
RUN cd /tmp/php-ast-master/ && phpize && ./configure && make && make install && rm -rf /tmp/php-ast-master/
RUN echo "extension=ast.so" >> /etc/php/7.4/cli/conf.d/20-ast.ini

RUN cd /tmp && wget -O php-systemd-src.zip https://github.com/systemd/php-systemd/archive/master.zip && \
    unzip php-systemd-src.zip && cd /tmp/php-systemd-master && phpize && \
    ./configure --with-systemd && make && make install && rm -rf /tmp/php-systemd-master && \
    echo "extension=systemd.so" >> /etc/php/7.4/mods-available/systemd.ini

RUN phpenmod zip intl gd systemd
RUN curl -O -L https://phar.phpunit.de/phpunit-8.phar \
    && chmod +x phpunit-8.phar \
    && mv phpunit-8.phar /usr/local/bin/phpunit
RUN curl -O -L https://getcomposer.org/download/1.6.2/composer.phar \
    && chmod +x composer.phar \
    && mv composer.phar /usr/local/bin/composer

RUN phpdismod xdebug
ADD nextcloud.ini /etc/php/7.4/cli/conf.d/nextcloud.ini
