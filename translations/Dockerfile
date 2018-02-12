FROM alpine

MAINTAINER Morris Jobke <hey@morrisjobke.de>

# Install python
RUN apk update && \
    apk add python3 py3-pip gettext make gnupg git openssh wget php5 && \
    rm -rf /var/cache/apk/*

# Install Transifex client
RUN pip3 install transifex-client

# Add sym link for php
RUN ln -s /usr/bin/php5 /usr/bin/php

RUN mkdir -p /app

ADD gitconfig /root/.gitconfig
ADD known_hosts /root/.ssh/known_hosts
ADD handleTranslations.sh /handleTranslations.sh
ADD translationtool/translationtool.phar /translationtool.phar

WORKDIR /app

ENTRYPOINT ["/handleTranslations.sh"]
