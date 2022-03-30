FROM ghcr.io/nextcloud/continuous-integration-php8.0:latest

RUN mkdir /tmp/server && \
    cd /tmp/server && git clone --recursive https://github.com/nextcloud/server.git && \
    cd /tmp/server/server/build/integration && composer install && \
    cd /tmp/server/server/tests/acceptance && composer install && \
    rm -rf /tmp/server
