FROM ubuntu:22.04

MAINTAINER Morris Jobke <hey@morrisjobke.de>

# Install python
RUN apt-get update -q && \
    DEBIAN_FRONTEND=noninteractive apt-get install -q -y --no-install-recommends \
        gawk \
        gettext \
        git \
        gnupg \
        make \
        curl \
        openssh-client \
        ca-certificates \
        php8.1-cli \
        php8.1-xml \
        qttools5-dev-tools \
    && apt-get clean

RUN update-ca-certificates

# Install Transifex client
RUN cd /root
RUN curl -o- https://raw.githubusercontent.com/transifex/cli/master/install.sh | bash

ENV PATH=${PATH}:/

RUN mkdir -p /app

ADD gitconfig /root/.gitconfig
ADD known_hosts /root/.ssh/known_hosts
ADD handleTranslations.sh /handleTranslations.sh
ADD translationtool/translationtool.phar /translationtool.phar

WORKDIR /app

ENTRYPOINT ["/handleTranslations.sh"]
