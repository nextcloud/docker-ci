FROM ghcr.io/nextcloud/continuous-integration-samba-non-native-php8.0:latest

RUN apt update
RUN apt-get install -y php8.0-dev libsmbclient-dev git
RUN git clone https://github.com/eduardok/libsmbclient-php.git
RUN cd libsmbclient-php && phpize && ./configure && make && make install

ADD smbclient.ini /etc/php/8.0/cli/conf.d/smbclient.ini
