FROM ghcr.io/nextcloud/continuous-integration-php7.4:latest
RUN apt-get update && \
    apt-get purge -y php7.4-apcu && \
    apt-get install -y php7.4-memcached memcached && \
    update-rc.d memcached enable && \
    apt-get autoremove -y && apt-get autoclean && apt-get clean && \
    rm -rf /tmp/* /var/tmp/* /var/lib/apt/lists/*

ADD nextcloud.ini /etc/php/7.4/cli/conf.d/nextcloud.ini
ENTRYPOINT service memcached restart && bash
