FROM debian:jessie

RUN apt-get update && \ 
    apt-get install -y wget gnupg2 libzip2 apt-transport-https lsb-release ca-certificates && \
    wget -O /etc/apt/trusted.gpg.d/php.gpg https://packages.sury.org/php/apt.gpg && \
    echo "deb https://packages.sury.org/php/ $(lsb_release -sc) main" > /etc/apt/sources.list.d/php.list && \
    apt-get update && apt-get install -y php7.2-intl php7.2-gd git curl \
    php7.2-cli php7.2-curl php7.2-pgsql php7.2-ldap \
    php7.2-sqlite php7.2-mysql php7.2-zip php7.2-xml \
    php7.2-mbstring php7.2-dev make libmagickcore-6.q16-2-extra unzip \
    php7.2-redis php7.2-imagick apache2 php7.2 && \
    apt-get autoremove -y && apt-get autoclean && apt-get clean && \
    rm -rf /tmp/* /var/tmp/* /var/lib/apt/lists/*

COPY php.ini /etc/php/7.0/apache2/conf.d/30-php.ini

WORKDIR /var/www/html

RUN rm -f index.html
RUN git clone https://github.com/nextcloud/server.git .
RUN git submodule update --init

RUN chown -R www-data:www-data .
RUN chsh -s /bin/bash www-data

ADD init.sh /initnc.sh
RUN chmod +x /initnc.sh

EXPOSE 80
ENTRYPOINT /initnc.sh
