FROM debian:stable

# Update repos
RUN apt-get update && \ 
    apt-get install -y wget gnupg2 git libzip4 apt-transport-https lsb-release ca-certificates && \
    wget -O /etc/apt/trusted.gpg.d/php.gpg https://packages.sury.org/php/apt.gpg && \
    echo "deb https://packages.sury.org/php/ $(lsb_release -sc) main" > /etc/apt/sources.list.d/php.list && \
    apt-get update

# Install packages
RUN apt-get install -y php7.3-cli php7.3-common php7.3-mbstring \
    php7.3-gd php-imagick php7.3-intl php7.3-bz2 php7.3-xml \
    php7.3-mysql php7.3-zip php7.3-dev curl php7.3-curl \
    php-dompdf php-apcu redis-server php-redis php-smbclient \
    php7.3-ldap unzip php7.3-pgsql php7.3-sqlite make apache2 \
    php7.3-json php7.3-opcache libmagickcore-6.q16-2-extra \
    libapache2-mod-php7.3

# Cleanup
RUN apt-get autoremove -y && apt-get autoclean && apt-get clean && \
    rm -rf /tmp/* /var/tmp/* /var/lib/apt/lists/*

COPY opcache-recommended.ini /usr/local/etc/php/conf.d/

WORKDIR /var/www/html

RUN rm -rf /var/www/html
RUN mkdir -p /var/www/html

RUN git clone https://github.com/nextcloud/server.git .
RUN git submodule update --init

RUN chown -R www-data:www-data .
RUN chsh -s /bin/bash www-data

ADD initnc.sh /usr/local/bin/
ADD run.sh /usr/local/bin/
ADD initAndRun.sh /usr/local/bin/

RUN chmod +x /usr/local/bin/*

EXPOSE 80
ENTRYPOINT ["/usr/local/bin/initAndRun.sh"]
