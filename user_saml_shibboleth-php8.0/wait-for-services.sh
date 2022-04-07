#!/usr/bin/env bash

# wait for Jetty
for i in {1..300}
do
   nc -zw 5 localhost 4443
   IS_JETTY=$?
   if [ ${IS_JETTY} -eq 0 ]; then
     break
   fi
   sleep 1
done

# wait for IdP becoming ready
for i in {1..300}
do
   if curl -f --silent -I -k https://localhost:4443/idp/ > /dev/null ; then
     exit 0
     break
   fi
   sleep 1
done

echo "Jetty or IdP not ready"
exit 1
