#!/usr/bin/env bash

#set -x

sed 's/^::1.*localhost/::1\tip6-localhost/g' /etc/hosts > /etc/hosts.tmp
cat /etc/hosts.tmp > /etc/hosts
rm -f /etc/hosts.tmp

export JAVA_HOME=/opt/jre-home
export PATH=$PATH:$JAVA_HOME/bin
export JETTY_BACKCHANNEL_SSL_KEYSTORE_PASSWORD=nextcloud
export JETTY_BROWSER_SSL_KEYSTORE_PASSWORD=nextcloud

if [ -e "/opt/shibboleth-idp/ext-conf/idp-secrets.properties" ]; then
  export JETTY_BACKCHANNEL_SSL_KEYSTORE_PASSWORD=`gawk 'match($0,/^jetty.backchannel.sslContext.keyStorePassword=\s?(.*)\s?$/, a) {print a[1]}' /opt/shibboleth-idp/ext-conf/idp-secrets.properties`
  export JETTY_BROWSER_SSL_KEYSTORE_PASSWORD=`gawk 'match($0,/^jetty\.sslContext\.keyStorePassword=\s?(.*)\s?$/, a) {print a[1]}' /opt/shibboleth-idp/ext-conf/idp-secrets.properties`
fi

export JETTY_ARGS="jetty.sslContext.keyStorePassword=$JETTY_BROWSER_SSL_KEYSTORE_PASSWORD jetty.backchannel.sslContext.keyStorePassword=$JETTY_BACKCHANNEL_SSL_KEYSTORE_PASSWORD"
sed -i "s/^-Xmx.*$/-Xmx$JETTY_MAX_HEAP/g" /opt/shib-jetty-base/start.ini

php-fpm -D
apachectl &
/usr/sbin/ns-slapd -D /etc/dirsrv/slapd-dir &

# wait for LDAP
for i in {1..300}
do
   nc -zw 5 localhost 389
   IS_LDAP=$?
   if [ ${IS_LDAP} -eq 0 ]; then
     break
   fi
   sleep 1
done
if [ ${IS_LDAP} -ne 0 ]; then
	echo "LDAP is not ready"
	cat /var/log/dirsrv/slapd-dir/errors
	exit 1
fi

run-jetty.sh &
