FROM ubuntu:24.04

LABEL org.opencontainers.image.authors="Joas Schilling <joas.schilling@nextcloud.com>"

RUN apt-get update -q && \
    DEBIAN_FRONTEND=noninteractive apt-get install -q -y --no-install-recommends \
        gawk \
        git \
        gnupg \
        make \
        curl \
        openssh-client \
        ca-certificates \
        php8.3-cli \
        php8.3-xml \
        jq \
    && apt-get clean

RUN update-ca-certificates

# Install Transifex client
RUN cd /root
RUN curl -o- https://raw.githubusercontent.com/transifex/cli/master/install.sh | bash

# Install newer gettext version
RUN cd /root
RUN curl http://archive.ubuntu.com/ubuntu/pool/main/g/gettext/gettext_0.23.1-1_amd64.deb -o /root/gettext_0.23.1-1_amd64.deb \
    && apt install -y /root/gettext_0.23.1-1_amd64.deb

ENV PATH=${PATH}:/

RUN mkdir -p /app

ADD gitconfig /root/.gitconfig
ADD known_hosts /root/.ssh/known_hosts
ADD handleTranslations.sh /handleTranslations.sh
ADD validateTranslationFiles.sh /validateTranslationFiles.sh
ADD translationtool/translationtool.phar /translationtool.phar

WORKDIR /app

ENTRYPOINT ["/handleTranslations.sh"]
