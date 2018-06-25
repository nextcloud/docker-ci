FROM ubuntu:trusty

MAINTAINER Roeland Jago Douma <roeland@famdouma.nl>

RUN apt-get update && \
    apt-get install -y wget libsqlite3-dev git \
        software-properties-common build-essential mesa-common-dev \
        fuse rsync curl

RUN add-apt-repository -y ppa:beineri/opt-qt595-trusty && \
    apt-get update && \
    apt-get install -y qt59base qt59tools qt59webengine qt59svg

RUN add-apt-repository -y ppa:ubuntu-toolchain-r/test && \
    apt-get update && \
    apt-get install -y gcc-7 g++-7

RUN cd /tmp && \
    wget https://www.openssl.org/source/openssl-1.1.0h.tar.gz && \
    tar -xvf openssl-1.1.0h.tar.gz && \
    cd openssl-1.1.0h && \
    ./config && \
    make && \
    make install && \
    cd .. && \
    rm -rf openssl*

RUN cd /tmp && \
    wget https://zlib.net/zlib-1.2.11.tar.gz && \
    tar -xvf zlib-1.2.11.tar.gz && \
    cd zlib-1.2.11 && \
    ./configure && \
    make && \
    make install && \
    cd .. && \
    rm -rf zlib*

RUN cd /tmp && \
    wget https://cmake.org/files/v3.11/cmake-3.11.4-Linux-x86_64.tar.gz && \
    tar -xvf cmake-3.11.4-Linux-x86_64.tar.gz && \
    cd cmake-3.11.4-Linux-x86_64 && \
    cp -r bin /usr/ && \
    cp -r share /usr/ && \
    cp -r doc /usr/share/ && \
    cp -r man /usr/share/ && \
    cd .. && \
    rm -rf cmake*
