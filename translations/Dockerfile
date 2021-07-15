FROM ubuntu:20.04

MAINTAINER Morris Jobke <hey@morrisjobke.de>

# Install python
RUN apt-get update -q && \
    DEBIAN_FRONTEND=noninteractive apt-get install -q -y --no-install-recommends \
        gawk \
        gettext \
        git \
        gnupg \
        make \
        openssh-client \
        php7.4-cli \
        php7.4-json \
        php7.4-xml \
        python3 \
        python3-pip \
        python3-setuptools \
        python3-wheel \
        qttools5-dev-tools \
    && apt-get clean

# Install Transifex client
RUN pip3 install transifex-client

# Add sym link for php & python
RUN ln -s /usr/bin/python3 /usr/bin/python

RUN mkdir -p /app

ADD gitconfig /root/.gitconfig
ADD known_hosts /root/.ssh/known_hosts
ADD handleTranslations.sh /handleTranslations.sh
ADD translationtool/translationtool.phar /translationtool.phar

WORKDIR /app

ENTRYPOINT ["/handleTranslations.sh"]
