FROM ubuntu:24.04

MAINTAINER Morris Jobke <hey@morrisjobke.de>

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
        qt6-tools-dev-tools \
    && apt-get clean

RUN update-ca-certificates

# Install Transifex client
RUN cd /root
RUN curl -o- https://raw.githubusercontent.com/transifex/cli/master/install.sh | bash

ENV PATH=${PATH}:/usr/lib/qt6/bin:/

RUN mkdir -p /app

ADD gitconfig /root/.gitconfig
ADD known_hosts /root/.ssh/known_hosts
ADD handleDesktopTranslations.sh /handleTranslations.sh

WORKDIR /app

ENTRYPOINT ["/handleTranslations.sh"]
