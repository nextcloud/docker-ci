FROM nextcloudci/php7.0:php7.0-2
RUN mkdir /tmp/server
RUN cd /tmp/server && git clone --recursive https://github.com/nextcloud/server.git
RUN cd /tmp/server/server/build/integration && composer install
