FROM ghcr.io/nextcloud/continuous-integration-php7.3:php7.3-5 

RUN apt-get update && apt-get install -y apache2 libapache2-mod-php7.3 && \
    apt-get autoremove -y && apt-get autoclean && apt-get clean && \
    rm -rf /tmp/* /var/tmp/* /var/lib/apt/lists/*

# /var/www/html has to be linked to the root directory of the Nextcloud server
# before the tests are run on Apache.
RUN rm -fr /var/www/html
