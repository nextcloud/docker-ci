FROM nextcloudci/samba-non-native-php7.0:samba-non-native-php7.0-4

RUN apt update
RUN apt-get install -y php7.0-dev libsmbclient-dev git
RUN git clone git://github.com/eduardok/libsmbclient-php.git
RUN cd libsmbclient-php && phpize && ./configure && make && make install

ADD smbclient.ini /etc/php/7.0/cli/conf.d/smbclient.ini
