# This Dockerfile builds an image of a system in which a Nextcloud server could
# be installed. It is based on the Dockerfile for Nextcloud 11 on Apache
# (https://github.com/nextcloud/docker/blob/843d309ee62b9d2704e6141d2103f9ded97e35b6/11.0/apache/Dockerfile),
# although without the download and copy of a specific Nextcloud version; there
# is no volume either at "/var/www/html" as it offers no value for its intended
# use case as a Drone service. As such, it provides a remote control system for
# the Nextcloud server (for example, to reset it), including a helper script to
# setup the Nextcloud server as needed from the Git repository cloned by Drone.

FROM php:7.1-apache

RUN apt-get update && apt-get install -y \
  bzip2 \
  libcurl4-openssl-dev \
  libfreetype6-dev \
  libicu-dev \
  libjpeg-dev \
  libldap2-dev \
  libmcrypt-dev \
  libmemcached-dev \
  libpng12-dev \
  libpq-dev \
  libxml2-dev \
  && rm -rf /var/lib/apt/lists/*

# https://docs.nextcloud.com/server/9/admin_manual/installation/source_installation.html
RUN docker-php-ext-configure gd --with-png-dir=/usr --with-jpeg-dir=/usr \
  && docker-php-ext-configure ldap --with-libdir=lib/x86_64-linux-gnu \
  && docker-php-ext-install gd exif intl mbstring mcrypt ldap mysqli opcache pdo_mysql pdo_pgsql pgsql zip

# set recommended PHP.ini settings
# see https://secure.php.net/manual/en/opcache.installation.php
RUN { \
    echo 'opcache.memory_consumption=128'; \
    echo 'opcache.interned_strings_buffer=8'; \
    echo 'opcache.max_accelerated_files=4000'; \
    echo 'opcache.revalidate_freq=60'; \
    echo 'opcache.fast_shutdown=1'; \
    echo 'opcache.enable_cli=1'; \
  } > /usr/local/etc/php/conf.d/opcache-recommended.ini
RUN a2enmod rewrite

# PECL extensions
RUN set -ex \
 && pecl install APCu-5.1.8 \
 && pecl install memcached-3.0.2 \
 && pecl install redis-3.1.1 \
 && docker-php-ext-enable apcu redis memcached
RUN a2enmod rewrite

# php-sockets is needed by Nextcloud server control
RUN docker-php-ext-install sockets

# Add Git to be able to restore "/var/www/html/" to a saved state
RUN apt-get update && apt-get install -y \
  git \
  && rm -rf /var/lib/apt/lists/*

# Configure Apache to listen on a non-privileged port so it can be started by
# "www-data" user in "nextcloud-server-control.php"
RUN sed --in-place "s/Listen 80/Listen 8000/" /etc/apache2/ports.conf \
  && sed --in-place "s/<VirtualHost \*:80>/<VirtualHost \*:8000>/" /etc/apache2/sites-enabled/000-default.conf

# Unlink the logs from stdout and stderr set by the parent Dockerfile, as in
# this image the user running Apache may not be the same as the user running the
# container (and Apache would refuse to start if it was not able to write to the
# logs)
RUN set -ex \
  && . "$APACHE_ENVVARS" \
  && unlink "$APACHE_LOG_DIR/error.log" \
  && unlink "$APACHE_LOG_DIR/access.log" \
  && unlink "$APACHE_LOG_DIR/other_vhosts_access.log"

COPY nextcloud-server-control.php /usr/local/bin/
COPY nextcloud-server-control-setup.sh /usr/local/bin/
