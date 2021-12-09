FROM ghcr.io/nextcloud/continuous-integration-samba-non-native-php7.4:latest

RUN apt update
RUN apt-get install -y php7.4-dev libsmbclient-dev git
RUN git clone git://github.com/eduardok/libsmbclient-php.git
RUN cd libsmbclient-php && phpize && ./configure && make && make install

ADD smbclient.ini /etc/php/7.4/cli/conf.d/smbclient.ini
