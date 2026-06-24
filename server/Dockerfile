FROM debian:bookworm

# Update repos install packages and cleanup
# all in one step so we avoid large intermediate layers.
RUN apt-get update && \
    apt-get install -y wget gnupg2 git libzip4 apt-transport-https lsb-release ca-certificates && \
    wget -O /etc/apt/trusted.gpg.d/php.gpg https://packages.sury.org/php/apt.gpg && \
    echo "deb https://packages.sury.org/php/ $(lsb_release -sc) main" > /etc/apt/sources.list.d/php.list && \
    apt-get update && \
    apt-get install -y php8.2-cli php8.2-common php8.2-mbstring \
    php8.2-gd php8.2-imagick php8.2-intl php8.2-bz2 php8.2-xml \
    php8.2-mysql php8.2-zip php8.2-dev curl php8.2-curl \
    php-dompdf php8.2-apcu redis-server php8.2-redis php8.2-smbclient \
    php8.2-ldap unzip php8.2-pgsql php8.2-sqlite make apache2 \
    php8.2-opcache libmagickcore-6.q16-2-extra \
    libapache2-mod-php8.2 && \
    apt-get autoremove -y && apt-get autoclean && apt-get clean && \
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

# self signed cert
ADD ssl/dhparam.pem /etc/ssl/certs/
ADD ssl/default-ssl.conf /etc/apache2/conf-available/ssl-params.conf
ADD ssl/nextcloud.crt /etc/ssl/certs/nextcloud.crt
ADD ssl/nextcloud.key /etc/ssl/private/nextcloud.key
ADD ssl/default-ssl.conf /etc/apache2/sites-available/default-ssl.conf

RUN chmod +x /usr/local/bin/*

EXPOSE 80
ENTRYPOINT ["/usr/local/bin/initAndRun.sh"]
