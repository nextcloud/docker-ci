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

sh -c "while true ; do cat data/nextcloud.log | tail -n 200; sleep 2; done "

apache2 -DFOREGROUND "$@"
