FROM ubuntu:26.04 AS gettext

RUN apt-get update -q && \
    DEBIAN_FRONTEND=noninteractive apt-get install -q -y --no-install-recommends \
        build-essential \
        ca-certificates \
        curl \
        gpgv \
        xz-utils

# Install newer gettext version from source
ARG GETTEXT_VERSION=1.0
ARG GETTEXT_SIGNING_KEY=E0FFBD975397F77A32AB76ECB6301D9E1BBEAC08
RUN curl -fL https://ftp.gnu.org/pub/gnu/gettext/gettext-${GETTEXT_VERSION}.tar.xz -o /tmp/gettext-${GETTEXT_VERSION}.tar.xz \
    && curl -fL https://ftp.gnu.org/pub/gnu/gettext/gettext-${GETTEXT_VERSION}.tar.xz.sig -o /tmp/gettext-${GETTEXT_VERSION}.tar.xz.sig \
    && curl -fL https://ftp.gnu.org/pub/gnu/gnu-keyring.gpg -o /tmp/gnu-keyring.gpg \
    && if ! gpgv --keyring /tmp/gnu-keyring.gpg --status-fd 1 /tmp/gettext-${GETTEXT_VERSION}.tar.xz.sig /tmp/gettext-${GETTEXT_VERSION}.tar.xz \
        | grep -q "^\[GNUPG:\] VALIDSIG .* ${GETTEXT_SIGNING_KEY}\$"; then \
        echo "gettext-${GETTEXT_VERSION}.tar.xz is not signed by ${GETTEXT_SIGNING_KEY}" >&2; exit 1; \
    fi \
    && tar -xJf /tmp/gettext-${GETTEXT_VERSION}.tar.xz -C /tmp

RUN cd /tmp/gettext-${GETTEXT_VERSION} \
    && ./configure -C --prefix=/usr --libexecdir=/usr/lib --disable-static \
    && make -j"$(nproc)" \
    && make install-strip DESTDIR=/gettext \
    && rm -rf /gettext/usr/share/doc /gettext/usr/share/info /gettext/usr/share/locale /gettext/usr/share/man \
    && find /gettext -name '*.la' -delete

FROM ubuntu:26.04

LABEL org.opencontainers.image.authors="Joas Schilling <joas.schilling@nextcloud.com>"

RUN apt-get update -q && \
    DEBIAN_FRONTEND=noninteractive apt-get install -q -y --no-install-recommends \
        git \
        gnupg \
        curl \
        openssh-client \
        openssl \
        ca-certificates \
        php-cli \
        php-xml \
        jq \
        patch \
        libgomp1 \
        xz-utils \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

COPY --from=gettext /gettext/ /
RUN xgettext --version && msgmerge --version

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
