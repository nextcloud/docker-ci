FROM debian:jessie
RUN apt-get update && apt-get install -y wget gnupg2 libzip2 && \
    wget http://www.dotdeb.org/dotdeb.gpg && \
    apt-key add dotdeb.gpg && \
    echo "deb http://packages.dotdeb.org jessie all" >> /etc/apt/sources.list && \
    echo "deb-src http://packages.dotdeb.org jessie all" >> /etc/apt/sources.list && \
    apt-get update && apt-get install -y php7.0-intl php7.0-gd git curl \
    php7.0-cli php7.0-curl php7.0-imagick php7.0-pgsql php7.0-mcrypt php7.0-ldap \
    php7.0-apcu php7.0-redis php7.0-sqlite php7.0-mysql php7.0-zip php7.0-xml \
    php7.0-mbstring php7.0-xdebug php7.0-dev make libmagickcore-6.q16-2-extra unzip && \
    apt-get autoremove -y && apt-get autoclean && apt-get clean && \
    rm -rf /tmp/* /var/tmp/* /var/lib/apt/lists/*

RUN cd /tmp/ && \
    git clone https://github.com/phpredis/phpredis.git && \
    cd phpredis && \
    git checkout 3.1.3 && \
    phpize && \
    ./configure && \
    make && \
    make install && \
    cd / && \
    rm -rf /tmp/phpredis

RUN cd /tmp/ && wget https://github.com/nikic/php-ast/archive/master.zip && unzip master.zip
RUN cd /tmp/php-ast-master/ && phpize && ./configure && make && make install && rm -rf /tmp/php-ast-master/
RUN echo "extension=ast.so" >> /etc/php/7.0/cli/conf.d/20-ast.ini
RUN phpenmod zip intl gd
RUN curl -O -L https://phar.phpunit.de/phpunit-6.5.5.phar \
    && chmod +x phpunit-6.5.5.phar \
    && mv phpunit-6.5.5.phar /usr/local/bin/phpunit
RUN curl -O -L https://getcomposer.org/download/1.6.2/composer.phar \
    && chmod +x composer.phar \
    && mv composer.phar /usr/local/bin/composer

RUN phpdismod xdebug
ADD nextcloud.ini /etc/php/7.0/cli/conf.d/nextcloud.ini
