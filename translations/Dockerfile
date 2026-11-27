FROM ubuntu:26.04

LABEL org.opencontainers.image.authors="Joas Schilling <joas.schilling@nextcloud.com>"

RUN apt-get update -q && \
    DEBIAN_FRONTEND=noninteractive apt-get install -q -y --no-install-recommends \
        gawk \
        git \
        gnupg \
        gpgv \
        make \
        curl \
        openssh-client \
        openssl \
        ca-certificates \
        php-cli \
        php-xml \
        jq \
        patch \
        build-essential \
    && apt-get clean

RUN update-ca-certificates

# Install newer gettext version from source
ARG GETTEXT_VERSION=1.0
ARG GETTEXT_SIGNING_KEY=E0FFBD975397F77A32AB76ECB6301D9E1BBEAC08
RUN curl -fL https://ftp.gnu.org/pub/gnu/gettext/gettext-${GETTEXT_VERSION}.tar.gz -o /tmp/gettext-${GETTEXT_VERSION}.tar.gz \
    && curl -fL https://ftp.gnu.org/pub/gnu/gettext/gettext-${GETTEXT_VERSION}.tar.gz.sig -o /tmp/gettext-${GETTEXT_VERSION}.tar.gz.sig \
    && curl -fL https://ftp.gnu.org/pub/gnu/gnu-keyring.gpg -o /tmp/gnu-keyring.gpg \
    && if ! gpgv --keyring /tmp/gnu-keyring.gpg --status-fd 1 /tmp/gettext-${GETTEXT_VERSION}.tar.gz.sig /tmp/gettext-${GETTEXT_VERSION}.tar.gz \
        | grep -q "^\[GNUPG:\] VALIDSIG .* ${GETTEXT_SIGNING_KEY}\$"; then \
        echo "gettext-${GETTEXT_VERSION}.tar.gz is not signed by ${GETTEXT_SIGNING_KEY}" >&2; exit 1; \
    fi \
    && rm /tmp/gettext-${GETTEXT_VERSION}.tar.gz.sig /tmp/gnu-keyring.gpg \
    && tar -xzf /tmp/gettext-${GETTEXT_VERSION}.tar.gz -C /tmp

RUN cd /tmp/gettext-${GETTEXT_VERSION} \
    && ./configure --prefix=/usr --libexecdir=/usr/lib \
    && make \
    && make install \
    && rm -rf /tmp/gettext-${GETTEXT_VERSION}*

# Install Transifex client
ARG TX_VERSION=1.6.17
ARG TX_SHA256=002dec5b9e71248a7e6a0808118e9da940205828d5a33ce88e04bb57a967164d
RUN curl -fL https://github.com/transifex/cli/releases/download/v${TX_VERSION}/tx-linux-amd64.tar.gz -o /tmp/tx.tar.gz \
    && echo "${TX_SHA256}  /tmp/tx.tar.gz" | sha256sum -c - \
    && tar -xzf /tmp/tx.tar.gz --no-same-owner -C / tx \
    && mkdir -p /usr/share/doc/tx \
    && tar -xzf /tmp/tx.tar.gz --no-same-owner -C /usr/share/doc/tx LICENSE \
    && rm /tmp/tx.tar.gz \
    && /tx --version

ENV PATH=${PATH}:/

RUN mkdir -p /app

ADD gitconfig /root/.gitconfig
ADD known_hosts /root/.ssh/known_hosts
ADD handleTranslations.sh /handleTranslations.sh
ADD stringFreeze.sh /stringFreeze.sh
ADD validateTranslationFiles.sh /validateTranslationFiles.sh
ADD translationtool/translationtool.phar /translationtool.phar

WORKDIR /app

ENTRYPOINT ["/handleTranslations.sh"]
