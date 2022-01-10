#!/bin/sh

export BRANCH=${BRANCH:=master}

# Where we do all the work
cd /var/www/html/

# Update code
su www-data -c "
git fetch origin ${BRANCH} --depth 1
git checkout origin/$BRANCH -B $BRANCH
git submodule update

# Creating data
mkdir -p /var/www/html/data

# Init
php occ maintenance:install --admin-user=admin --admin-pass=admin
OC_PASS=test php occ user:add --password-from-env -- test

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
