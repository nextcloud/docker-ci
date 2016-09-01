FROM debian
RUN apt-get update && apt-get install -y wget
RUN wget http://www.dotdeb.org/dotdeb.gpg && \
    apt-key add dotdeb.gpg
RUN echo "deb http://packages.dotdeb.org jessie all" >> /etc/apt/sources.list
RUN echo "deb-src http://packages.dotdeb.org jessie all" >> /etc/apt/sources.list
RUN apt-get update && apt-get install -y php7.0-intl php7.0-gd git curl \
    php7.0-cli php7.0-curl php7.0-imagick php7.0-pgsql php7.0-mcrypt php7.0-ldap \
    php7.0-apcu php7.0-redis php7.0-sqlite php7.0-mysql php7.0-zip php7.0-xml \
    php7.0-mbstring php7.0-xdebug
RUN phpenmod zip intl gd xdebug
RUN curl -O -L https://phar.phpunit.de/phpunit-5.4.6.phar \
    && chmod +x phpunit-5.4.6.phar \
    && mv phpunit-5.4.6.phar /usr/local/bin/phpunit
RUN curl -O -L https://getcomposer.org/download/1.1.2/composer.phar \
    && chmod +x composer.phar \
    && mv composer.phar /usr/local/bin/composer

ADD nextcloud.ini /etc/php/7.0/cli/conf.d/nextcloud.ini
