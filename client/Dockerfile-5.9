FROM ubuntu:xenial

MAINTAINER Roeland Jago Douma <roeland@famdouma.nl>

RUN apt-get update && \
    apt-get install -y wget libsqlite3-dev libssl-dev cmake git \
        software-properties-common build-essential mesa-common-dev

RUN add-apt-repository -y ppa:beineri/opt-qt595-xenial && \
    apt-get update && \
    apt-get install -y qt59base qt59tools qt59webengine qt59svg

# Install openssl
RUN cd /tmp && \
    wget https://www.openssl.org/source/openssl-1.1.0h.tar.gz && \
    tar -xvf openssl-1.1.0h.tar.gz && \
    cd openssl-1.1.0h && \
    ./config && \
    make && \
    make install && \
    cd .. && \
    rm -rf openssl*

