FROM debian:bullseye

# Update repos install packages and cleanup
# all in one step so we avoid large intermediate layers.
RUN apt-get update && \
    apt-get install -y wget gnupg2 git libzip4 apt-transport-https lsb-release ca-certificates && \
    wget -O /etc/apt/trusted.gpg.d/php.gpg https://packages.sury.org/php/apt.gpg && \
    echo "deb https://packages.sury.org/php/ $(lsb_release -sc) main" > /etc/apt/sources.list.d/php.list && \
    apt-get update && \
    apt-get install -y php8.1-cli php8.1-common php8.1-mbstring \
    php8.1-gd php8.1-imagick php8.1-intl php8.1-bz2 php8.1-xml \
    php8.1-mysql php8.1-zip php8.1-dev curl php8.1-curl \
    php-dompdf php8.1-apcu redis-server php8.1-redis php8.1-smbclient \
    php8.1-ldap unzip php8.1-pgsql php8.1-sqlite make apache2 \
    php8.1-opcache libmagickcore-6.q16-2-extra \
    libapache2-mod-php8.1 && \
    apt-get autoremove -y && apt-get autoclean && apt-get clean && \
    rm -rf /tmp/* /var/tmp/* /var/lib/apt/lists/*

COPY opcache-recommended.ini /usr/local/etc/php/conf.d/

WORKDIR /var/www/html

# Looks like there's an issue on self-hosted runners with fuse-overlayfs that prevents us deleting the directory directly
# so rename it first and then remove it
RUN mv /var/www/html/ /var/www/html2/ && rm -rf /var/www/html2/
RUN mkdir -p /var/www/html

RUN git clone --depth 1 https://github.com/nextcloud/server.git .
RUN git submodule update --init --depth 1

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
ADD ssl/selfsigned-cli.crt /etc/ssl/certs/selfsigned-ca.crt
ADD ssl/selfsigned-cli.key /etc/ssl/private/selfsigned-cli.key
ADD ssl/default-ssl.conf /etc/apache2/sites-available/default-ssl.conf
ADD default-nextcloud.conf /etc/apache2/sites-enabled/default-nextcloud.conf
ADD nextcloud.ini /etc/php/8.1/apache2/conf.d/nextcloud.ini

RUN chmod +x /usr/local/bin/*

EXPOSE 80
ENTRYPOINT ["/usr/local/bin/initAndRun.sh"]
