FROM debian:stretch

RUN apt-get update \
    && apt-get install -y \
       php \
       php-curl \
       php-gd \
       php-imagick \
       php-mbstring \
       php-pdo-mysql \
       php-pdo-pgsql \
       php-redis \
       php-sqlite3 \
       php-xml \
       php-zip \
       git \
    && apt-get autoremove -y \
    && apt-get autoclean \
    && apt-get clean \
    && rm -rf /tmp/* /var/tmp/* /var/lib/apt/lists/*

COPY php.ini /etc/php/7.0/apache2/conf.d/30-php.ini

WORKDIR /var/www/html

RUN rm index.html
RUN git clone https://github.com/nextcloud/server.git .
RUN git submodule update --init

RUN chown -R www-data:www-data .
RUN chsh -s /bin/bash www-data

ADD init.sh /initnc.sh
RUN chmod +x /initnc.sh

EXPOSE 80
ENTRYPOINT /initnc.sh