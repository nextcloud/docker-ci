#!/usr/bin/env bash

# wait for Jetty
for i in {1..90}
do
   nc -zw 5 localhost 4443
   IS_JETTY=$?
   if [ ${IS_JETTY} -eq 0 ]; then
     break
   fi
   sleep 1
done
