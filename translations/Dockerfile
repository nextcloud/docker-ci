FROM debian:bookworm-slim

MAINTAINER Morris Jobke <hey@morrisjobke.de>

# Install python
RUN apt-get update -q && \
    apt-get install -q -y --no-install-recommends \
        gawk \
        gettext \
        git \
        gnupg \
        make \
        curl \
        openssh-client \
        ca-certificates \
        php8.2-cli \
        php8.2-xml \
        qttools5-dev-tools \
    && rm -rf /var/lib/apt/lists/*

RUN update-ca-certificates

# Install Transifex client
WORKDIR /root
RUN curl -fsSL https://raw.githubusercontent.com/transifex/cli/master/install.sh | bash

ENV PATH=${PATH}:/

RUN mkdir -p /app

COPY gitconfig /root/.gitconfig
COPY known_hosts /root/.ssh/known_hosts
COPY handleTranslations.sh /handleTranslations.sh
COPY translationtool/translationtool.phar /translationtool.phar

WORKDIR /app

ENTRYPOINT ["/handleTranslations.sh"]
