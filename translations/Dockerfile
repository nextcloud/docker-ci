FROM ubuntu:24.04

ARG GETTEXT_VERSION=0.26

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
        build-essential \
    && apt-get clean

RUN update-ca-certificates

# Install Transifex client
RUN cd /root
RUN curl -o- https://raw.githubusercontent.com/transifex/cli/master/install.sh | bash

# Install newer gettext version from source
RUN curl -L https://ftp.gnu.org/pub/gnu/gettext/gettext-${GETTEXT_VERSION}.tar.gz -o /tmp/gettext-${GETTEXT_VERSION}.tar.gz \
    && tar -xzf /tmp/gettext-${GETTEXT_VERSION}.tar.gz -C /tmp \
    && cd /tmp/gettext-${GETTEXT_VERSION} \
    && ./configure \
    && make \
    && make install \
    && rm -rf /tmp/gettext-${GETTEXT_VERSION}*

ENV PATH=${PATH}:/

RUN mkdir -p /app

ADD gitconfig /root/.gitconfig
ADD known_hosts /root/.ssh/known_hosts
ADD handleTranslations.sh /handleTranslations.sh
ADD validateTranslationFiles.sh /validateTranslationFiles.sh
ADD translationtool/translationtool.phar /translationtool.phar

WORKDIR /app

ENTRYPOINT ["/handleTranslations.sh"]
