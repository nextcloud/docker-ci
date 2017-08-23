# Based upon https://github.com/MorrisJobke/docker-proftpd
From nextcloudci/php7.0:php7.0-16

RUN apt-get update
RUN apt-get -y install debconf-utils
RUN echo "poftpd-basic shared/proftpd/inetd_or_standalone select standalone" | debconf-set-selections

RUN apt-get install -y proftpd

ADD launch /launch
ADD proftpd.conf /etc/proftpd/proftpd.conf
RUN chown root:root /etc/proftpd/proftpd.conf
RUN mkdir /ftp
RUN chmod a+x /launch