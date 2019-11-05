#!/bin/sh

set -e
cd /var/www/html/

. /etc/apache2/envvars

# allow php and apache2 to create their run socket
mkdir -p /run/php
mkdir -p /var/run/apache2

tail -f data/nextcloud.log &

apache2 -DFOREGROUND "$@"