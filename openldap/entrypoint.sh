#!/bin/bash

# When not limiting the open file descritors limit, the memory consumption of
# slapd is absurdly high. See https://github.com/docker/docker/issues/8231
ulimit -n 8192

set -em

"$@" &

# apt install errors with conflicts due to the slapd state (and its version perhaps) in the Dockerfile
# marking it "hold" does not work due to other dependencies.
#apt update && \
#    DEBIAN_FRONTEND=noninteractive apt install --no-install-recommends -y ldap-utils && \
#    apt clean && \
#    rm -rf /var/lib/apt/lists/*
# we enable job control to send the slapd to background, but still to be able to pre-populate
# the directory AND having memberof already working.
sleep 2 # might be a race condition
for file in `ls /etc/ldap/prepopulate/*.ldif`; do
    ldapadd -x -D "cn=admin,dc=nextcloud,dc=ci" -w "$SLAPD_PASSWORD" -f "$file"
done
fg


