FROM quay.io/389ds/dirsrv:latest

ENV DS_DM_PASSWORD admin
ENV DS_SUFFIX_NAME dc=idptestbed

ADD conf/* /var/opt/

RUN rm -Rf /data/*

RUN /usr/libexec/dirsrv/dscontainer -r & \
    WAIT_TIME=25; \
    sleep ${WAIT_TIME}; \
    while : ; do \
    	if /usr/libexec/dirsrv/dscontainer -H; then \
    		break; \
    	fi; \
    	sleep 5; \
    	WAIT_TIME=$((WAIT_TIME + 5)); \
    	if [ ${WAIT_TIME} -gt 180 ]; then \
    		echo "dirsrv not ready – giving up checking after 3min"; \
    		exit 3 ;\
    	fi; \
    done; \
    dsconf localhost backend create --suffix dc=idptestbed --be-name ci_root; \
    mv /var/opt/98nextcloud-schema.ldif /etc/dirsrv/slapd-localhost/schema/; \
    dsconf localhost schema reload; \
    dsconf localhost plugin memberof enable; \
    dsconf localhost plugin memberof set --autoaddoc inetOrgPerson; \
    echo "### Memberof Status" \
    dsconf localhost plugin memberof status; \
    echo "### Memberof Show" \
    dsconf localhost plugin memberof show; \
    dsconf localhost backend import "dc=idptestbed" /var/opt/entries.ldif; \
    rm /var/opt/entries.ldif;

EXPOSE 3389
