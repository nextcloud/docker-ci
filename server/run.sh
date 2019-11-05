#!/bin/sh

set -e
cd /var/www/html/

. /etc/apache2/envvars

tail -f data/nextcloud.log &

apache2-foreground "$@"