# Based on https://github.com/dinkel/docker-openldap by Christian Luginbühl, MIT licensed
# simplified to our needs to due https://github.com/dinkel/docker-openldap/issues/21
# (Proposed my solution in https://github.com/dinkel/docker-openldap/issues/21#issuecomment-468839994)

FROM debian:stretch

MAINTAINER Arthur Schiwon <blizzz@arthur-schiwon.de>

ENV OPENLDAP_VERSION 2.4.44

RUN apt-get update && \
    DEBIAN_FRONTEND=noninteractive apt-get install --no-install-recommends -y \
        slapd=${OPENLDAP_VERSION}* ldap-utils=${OPENLDAP_VERSION}* && \
    apt-get clean && \
    rm -rf /var/lib/apt/lists/*

RUN mv /etc/ldap /etc/ldap.dist

COPY modules/ /etc/ldap.dist/modules
COPY LDIFs/* /etc/ldap/prepopulate/

RUN cp -r /etc/ldap.dist/* /etc/ldap

COPY slapf_config /tmp/slapd_config
RUN cat /tmp/slapd_config | debconf-set-selections \
    && dpkg-reconfigure -f noninteractive slapd >/dev/null 2>&1 \
    && rm /tmp/slapd_config \
    && sed -i "s/^#BASE.*/BASE c=nextcloud,dc=ci/g" /etc/ldap/ldap.conf \
    && slapadd -n0 -F /etc/ldap/slapd.d -l "/etc/ldap/modules/memberof.ldif" \
    && chown -R openldap:openldap /etc/ldap/slapd.d/ /var/lib/ldap/ /var/run/slapd/

COPY entrypoint.sh /entrypoint.sh

EXPOSE 389

VOLUME ["/etc/ldap", "/var/lib/ldap"]

ENTRYPOINT ["/entrypoint.sh"]

CMD ["slapd", "-d", "32768", "-u", "openldap", "-g", "openldap"]
