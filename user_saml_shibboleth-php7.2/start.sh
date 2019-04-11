#!/bin/sh

#set -x

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

apachectl &
/usr/sbin/ns-slapd -D /etc/dirsrv/slapd-dir &
sleep 4 && /etc/init.d/jetty run &
