FROM debian:bullseye

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
    libapache2-mod-php8.2 php-pear libaio1 build-essential expect && \
    apt-get autoremove -y && apt-get autoclean && apt-get clean && \
    rm -rf /tmp/* /var/tmp/* /var/lib/apt/lists/*

# Oracle setup
COPY oci8-setup.sh /tmp/oci8-setup.sh
RUN chmod +x /tmp/oci8-setup.sh && \
    /tmp/oci8-setup.sh && \
    rm -f /tmp/oci8-setup.sh

COPY apc-cli-enable.ini /etc/php/8.2/cli/conf.d/99-apc-cli-enable.ini
COPY oci8-enable.ini /etc/php/8.2/apache2/conf.d/99-oci8-enable.ini
COPY opcache-recommended.ini /etc/php/8.2/cli/conf.d/99-opcache-recommended.ini

WORKDIR /var/www/html

RUN rm -rf /var/www/html
RUN mkdir -p /var/www/html
RUN chown www-data:www-data .

USER www-data
RUN git clone --depth 1 https://github.com/nextcloud/server.git .
RUN git submodule update --init --depth 1
USER root

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
ADD default-nextcloud.conf /etc/apache2/sites-enabled/default-nextcloud.conf
ADD nextcloud.ini /etc/php/8.2/apache2/conf.d/nextcloud.ini

RUN chmod +x /usr/local/bin/*

EXPOSE 80
ENTRYPOINT ["/usr/local/bin/initAndRun.sh"]
