FROM nextcloudci/php7.1:php7.1-16

RUN apt-get update
RUN apt-get install -y apache2 apache2-utils
RUN a2enmod dav dav_fs
RUN a2dissite 000-default

ENV APACHE_RUN_USER www-data
ENV APACHE_RUN_GROUP www-data
ENV APACHE_LOG_DIR /var/log/apache2
ENV APACHE_PID_FILE /var/run/apache2.pid
ENV APACHE_LOCK_DIR /var/lock/apache2
ENV APACHE_RUN_DIR /var/run/apache2

RUN mkdir -p /var/lock/apache2; chown www-data /var/lock/apache2
RUN mkdir -p /var/webdav; chown www-data /var/webdav

ADD webdav.conf /etc/apache2/sites-available/webdav.conf
RUN a2ensite webdav

RUN htpasswd -cb /etc/apache2/webdav.password test pass
RUN chown root:www-data /etc/apache2/webdav.password
RUN chmod 640 /etc/apache2/webdav.password

RUN apt-get autoremove -y && \
    apt-get autoclean && \
    apt-get clean && \
    rm -rf /tmp/* /var/tmp/* /var/lib/apt/lists/*

