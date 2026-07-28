FROM ubuntu:24.04

RUN apt-get update && apt-get install -y git vim apache2 apache2-dev make gcc \
    php-fpm php-zip php-dom php-xml php-gd php-sqlite3 php-mbstring php-curl

RUN git clone https://github.com/FastCGI-Archives/mod_fastcgi.git && \
    cd mod_fastcgi && cp Makefile.AP2 Makefile && \
    make top_dir=/usr/share/apache2 && \
    make install top_dir=/usr/share/apache2

RUN a2enmod actions && a2enmod rewrite

COPY <<EOF /etc/apache2/sites-enabled/000-default.conf
<VirtualHost *:80>
    ServerAdmin webmaster@localhost
    DocumentRoot /var/www/html
    ErrorLog ${APACHE_LOG_DIR}/error.log
    CustomLog ${APACHE_LOG_DIR}/access.log combined

    LoadModule fastcgi_module /usr/lib/apache2/modules/mod_fastcgi.so
    ScriptAlias /php.mod_fastcgi /php.mod_fastcgi
    FastCGIExternalServer /php.mod_fastcgi -socket /run/php/php8.3-fpm.sock -idle-timeout 600
    AddHandler php.mod_fastcgi .php
    Action php.mod_fastcgi /php.mod_fastcgi

    <Files php.mod_fastcgi>
        Require all granted
    </Files>

    <Directory /var/www/html/server>
        AllowOverride All
        Options FollowSymLinks MultiViews
    </Directory>
</VirtualHost>
EOF

CMD /etc/init.d/php8.3-fpm start && apachectl -D FOREGROUND
