#!/bin/sh

export BRANCH=${BRANCH:=master}

# Where we do all the work
cd /var/www/html/

# Update code
su www-data -c "
git fetch origin
git checkout ${BRANCH}
git pull
git submodule update

# init
php occ maintenance:install --admin-user=admin --admin-pass=admin
OC_PASS=test php occ user:add --password-from-env --display-name='User Test' -- test
OC_PASS=user1 php occ user:add --password-from-env --display-name='User One' -- user1
OC_PASS=user2 php occ user:add --password-from-env --display-name='User Two' -- user2

php occ group:add users
php occ group:adduser users user1
php occ group:adduser users user2

# Trusted domains
php occ config:system:set trusted_domains 1 --value=*
php occ config:system:set loglevel --value='0'
"

# allow eval script for executing javascript in webview (LoginIT test for Android)
# it needs EVAL set to true within environment in .drone.yml

if test -z "$EVAL"
then
	echo "\$EVAL not set, ignoring..."
else
    echo "\$EVAL is set, allowing eval script in ContentSecurityPolicy.php"
	sed -i s'/protected $evalScriptAllowed = false;/protected $evalScriptAllowed = true;/' lib/public/AppFramework/Http/ContentSecurityPolicy.php
fi


if test -z "$REDIS" 
then
	  echo "\$REDIS not set, ignoring..."
else
    su www-data -c "
	php occ config:system:set redis host --value=${REDIS}
	php occ config:system:set redis port --value=6379 --type=integer
	php occ config:system:set redis timeout --value=0 --type=integer
	php occ config:system:set --type string --value '\\OC\\Memcache\\Redis' memcache.local
	php occ config:system:set --type string --value '\\OC\\Memcache\\Redis' memcache.distributed
	"
fi

set -e

. /etc/apache2/envvars

tail -f data/nextcloud.log &

apache2 -DFOREGROUND "$@"
