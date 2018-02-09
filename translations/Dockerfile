FROM alpine

MAINTAINER Morris Jobke <hey@morrisjobke.de>

# Install python and perl
RUN apk update && \
    apk add python3 py3-pip gettext make gnupg git openssh wget php5 && \
    rm -rf /var/cache/apk/*

# Install Transifex client
RUN pip3 install transifex-client

RUN mkdir -p /app

ADD gitconfig /root/.gitconfig
ADD known_hosts /root/.ssh/known_hosts
ADD handleTranslations.sh /handleTranslations.sh

WORKDIR /app

ENTRYPOINT ["/handleTranslations.sh"]
