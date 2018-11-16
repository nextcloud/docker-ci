FROM alpine

MAINTAINER Morris Jobke <hey@morrisjobke.de>

RUN apk update && \
    apk add gnupg git openssh php7 composer curl && \
    rm -rf /var/cache/apk/*

RUN mkdir -p /app

WORKDIR /app

RUN git clone https://github.com/MorrisJobke/nextcloud-config-converter.git .
RUN composer install

ADD gitconfig /root/.gitconfig
ADD known_hosts /root/.ssh/known_hosts


ENTRYPOINT ["/app/updateConfig.sh"]
