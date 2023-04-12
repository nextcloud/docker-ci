FROM cscfi/shibboleth-idp:release-4.1.4

ADD shibboleth-idp/ /opt/shibboleth-idp/
ADD start.sh /usr/local/bin/

RUN chmod a+x /usr/local/bin/start.sh
RUN init-idp.sh

CMD "start.sh"
