FROM ghcr.io/nextcloud/continuous-integration-php8.1:latest
RUN apt-get update && \
    apt-get purge -y php8.1-apcu && \
    apt-get install -y php8.1-memcached memcached && \
    update-rc.d memcached enable && \
    apt-get autoremove -y && apt-get autoclean && apt-get clean && \
    rm -rf /tmp/* /var/tmp/* /var/lib/apt/lists/*

ADD nextcloud.ini /etc/php/8.1/cli/conf.d/nextcloud.ini
ENTRYPOINT service memcached restart && bash
