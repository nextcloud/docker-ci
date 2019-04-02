#!/bin/sh

export BRANCH=${BRANCH:=master}

#Where we do all the work
cd /var/www/html/

#Update code
su www-data -c "
git checkout ${BRANCH}
git pull
git submodule update

#init
php occ maintenance:install --admin-user=admin --admin-pass=admin
OC_PASS=test php occ user:add --password-from-env -- test

#Trusted domains
php occ config:system:set trusted_domains 1 --value=*
"

set -e

. /etc/apache2/envvars

tail -f data/nextcloud.log &

apache2 -DFOREGROUND "$@"
