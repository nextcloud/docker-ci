FROM unicon/shibboleth-idp:3.4.3

# Add Shibboleth config stuff
ADD shibboleth/ /opt/shibboleth-idp/

# Install the LDAP server
RUN yum install -y https://dl.fedoraproject.org/pub/epel/epel-release-latest-7.noarch.rpm
RUN yum install -y --enablerepo=centosplus 389-ds
RUN rm -fr /var/lock /usr/lib/systemd/system
ADD ldap/ds-setup.inf /ds-setup.inf
ADD ldap/users.ldif /users.ldif
ADD ldap/nextcloud.ldif /nextcloud.ldif
RUN sed -i 's/checkHostname {/checkHostname {\nreturn();/g' /usr/lib64/dirsrv/perl/DSUtil.pm
RUN sed -i 's/updateSelinuxPolicy($inf);//g' /usr/lib64/dirsrv/perl/*
ADD ldap/DSCreate.pm /usr/lib64/dirsrv/perl/DSCreate.pm
ADD ldap/AdminServer.pm /usr/lib64/dirsrv/perl/AdminServer.pm
RUN setup-ds-admin.pl --silent --file /ds-setup.inf
RUN /usr/sbin/ns-slapd -D /etc/dirsrv/slapd-dir \
	# ensure to have enough time for startup
	&& sleep 40 \
	&& ldapmodify -H ldap:/// -f nextcloud.ldif -x -D "cn=Directory Manager" -w password \
	&& ldapadd -H ldap:/// -f users.ldif -x -D "cn=Directory Manager" -w password \
	&& sleep 10 \
 	&& kill -TERM "$(ps -C ns-slapd -o pid h)" \
	# ensure to have enough time for shut-down
	&& sleep 20 \
	# debug output
	&& ps -aux
RUN rm /*.ldif
RUN echo '' > /var/log/dirsrv/slapd-dir/errors

# Install Apache and PHP 7.3 for Nextcloud
RUN yum -y install centos-release-scl
RUN yum -y install rh-php73 rh-php73-php rh-php73-php-gd rh-php73-php-mbstring rh-php73-php-sqlite httpd git rh-php73-mcrypt rh-php73-php-pdo sudo
RUN sed -i "s/128M/512M/" /etc/opt/rh/rh-php73/php.ini
RUN source scl_source enable rh-php73
RUN chmod -R 777 /opt/
RUN rm -f /etc/httpd/conf.d/nss.conf
ADD apache/httpd.conf /etc/httpd/conf/httpd.conf

RUN yum -y install nc

# Add the startup file
ADD start.sh /start.sh
RUN chmod a+x /start.sh

ADD wait-for-services.sh /wait-for-services.sh
RUN chmod a+x /wait-for-services.sh

CMD ["/bin/bash"]
