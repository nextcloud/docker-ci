#!/usr/bin/env bash

#set -x

#sed 's/^::1.*localhost/::1\tip6-localhost/g' /etc/hosts > /etc/hosts.tmp
#cat /etc/hosts.tmp > /etc/hosts
#rm -f /etc/hosts.tmp

export JETTY_BACKCHANNEL_SSL_KEYSTORE_PASSWORD=nextcloud
export JETTY_BROWSER_SSL_KEYSTORE_PASSWORD=nextcloud

init-idp.sh
$JAVA_HOME/bin/java -jar $JETTY_HOME/start.jar jetty.home=$JETTY_HOME jetty.base=$JETTY_BASE -Djetty.sslContext.keyStorePassword=$JETTY_KEYSTORE_PASSWORD -Djetty.sslContext.keyStorePath=$JETTY_KEYSTORE_PATH
