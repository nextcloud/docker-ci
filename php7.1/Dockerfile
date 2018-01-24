FROM debian:jessie
RUN apt-get update && apt-get install -y wget gnupg2 libzip2 apt-transport-https lsb-release ca-certificates && \
    wget -O /etc/apt/trusted.gpg.d/php.gpg https://packages.sury.org/php/apt.gpg && \
    echo "deb https://packages.sury.org/php/ $(lsb_release -sc) main" > /etc/apt/sources.list.d/php.list && \
    apt-get update && apt-get install -y php7.1-intl php7.1-gd git curl \
    php7.1-cli php7.1-curl php7.1-imagick php7.1-pgsql php7.1-mcrypt php7.1-ldap \
    php7.1-apcu php7.1-redis php7.1-sqlite php7.1-mysql php7.1-zip php7.1-xml \
    php7.1-mbstring php7.1-xdebug php7.1-dev make libmagickcore-6.q16-2-extra unzip && \
    apt-get autoremove -y && apt-get autoclean && apt-get clean && \
    rm -rf /tmp/* /var/tmp/* /var/lib/apt/lists/*

RUN cd /tmp/ && wget https://github.com/nikic/php-ast/archive/master.zip && unzip master.zip
RUN cd /tmp/php-ast-master/ && phpize && ./configure && make && make install && rm -rf /tmp/php-ast-master/
RUN echo "extension=ast.so" >> /etc/php/7.1/cli/conf.d/20-ast.ini
RUN phpenmod zip intl gd
RUN curl -O -L https://phar.phpunit.de/phpunit-6.5.5.phar \
    && chmod +x phpunit-6.5.5.phar \
    && mv phpunit-6.5.5.phar /usr/local/bin/phpunit
RUN curl -O -L https://getcomposer.org/download/1.6.2/composer.phar \
    && chmod +x composer.phar \
    && mv composer.phar /usr/local/bin/composer

RUN phpdismod xdebug
ADD nextcloud.ini /etc/php/7.1/cli/conf.d/nextcloud.ini
