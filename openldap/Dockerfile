# Based on https://github.com/dinkel/docker-openldap by Christian Luginbühl, MIT licensed
# simplified to our needs to due https://github.com/dinkel/docker-openldap/issues/21
# (Proposed my solution in https://github.com/dinkel/docker-openldap/issues/21#issuecomment-468839994)

FROM debian:trixie-slim

MAINTAINER Arthur Schiwon <blizzz@arthur-schiwon.de>

ENV OPENLDAP_VERSION 2.6.10

RUN apt-get update && \
    DEBIAN_FRONTEND=noninteractive apt-get install --no-install-recommends -y \
        slapd=${OPENLDAP_VERSION}* ldap-utils=${OPENLDAP_VERSION}* libldap-common=${OPENLDAP_VERSION}* gnutls-bin && \
    apt-get clean && \
    rm -rf /var/lib/apt/lists/*

RUN mv /etc/ldap /etc/ldap.dist

COPY modules/ /etc/ldap.dist/modules
COPY LDIFs/* /etc/ldap/prepopulate/
COPY certificate/ /tmp/certificate

RUN cp -r /etc/ldap.dist/* /etc/ldap

COPY slapf_config /tmp/slapd_config
RUN debconf-set-selections /tmp/slapd_config \
    && dpkg-reconfigure -f noninteractive slapd \
    && rm /tmp/slapd_config \
    && sed -i "s/^#BASE.*/BASE c=nextcloud,dc=ci/g" /etc/ldap/ldap.conf

RUN slapadd -v -n0 -F /etc/ldap/slapd.d -l "/etc/ldap/modules/memberof.ldif"

# create a self-signed certificate
RUN mkdir /etc/ldap/cert/ \
	&& cd /etc/ldap/cert \
	&& certtool --generate-privkey --outfile ca.key \
	&& certtool --generate-self-signed --load-privkey ca.key --outfile ca.crt \
	   --template /tmp/certificate/template \
	&& chown -R openldap:openldap /etc/ldap/cert \
	&& slapmodify -v -n0 -F /etc/ldap/slapd.d -l "/tmp/certificate/config.ldif"

RUN mkdir /var/run/slapd
RUN chown -R openldap:openldap /etc/ldap/slapd.d/ /var/lib/ldap/ /var/run/slapd

COPY entrypoint.sh /entrypoint.sh

EXPOSE 389 636

VOLUME ["/etc/ldap", "/var/lib/ldap"]

ENTRYPOINT ["/entrypoint.sh"]

CMD ["slapd", "-d", "32768", "-u", "openldap", "-g", "openldap", "-h", "ldaps:/// ldap:/// ldapi:///"]
