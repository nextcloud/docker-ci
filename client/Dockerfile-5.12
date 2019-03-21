FROM ubuntu:xenial

MAINTAINER Roeland Jago Douma <roeland@famdouma.nl>

RUN apt-get update && \
    apt-get install -y wget libsqlite3-dev git curl \
        software-properties-common build-essential mesa-common-dev

# Add Qt-5.11
RUN add-apt-repository ppa:beineri/opt-qt-5.12.1-xenial &&\
    apt-get update && \
    apt-get install -y qt512base qt512tools qt512webengine qt512svg qt512translations

# Install gcc-7
RUN add-apt-repository -y ppa:ubuntu-toolchain-r/test && \
    apt-get update && \
    apt-get install -y gcc-7 g++-7

# Install clang-6
RUN wget -O - https://apt.llvm.org/llvm-snapshot.gpg.key | apt-key add - && \
    apt-add-repository "deb http://apt.llvm.org/xenial/ llvm-toolchain-xenial-6.0 main" && \
    apt-get update && \
    apt-get install -y clang-6.0

# Install openssl
RUN cd /tmp && \
    wget https://www.openssl.org/source/openssl-1.1.1b.tar.gz && \
    tar -xvf openssl-1.1.1b.tar.gz && \
    cd openssl-1.1.1b && \
    ./config && \
    make && \
    make install && \
    cd .. && \
    rm -rf openssl*

# Install zlib
RUN cd /tmp && \
    wget https://zlib.net/zlib-1.2.11.tar.gz && \
    tar -xvf zlib-1.2.11.tar.gz && \
    cd zlib-1.2.11 && \
    ./configure && \
    make && \
    make install && \
    cd .. && \
    rm -rf zlib*

# Install cmake
RUN cd /tmp && \
    wget https://github.com/Kitware/CMake/releases/download/v3.14.0/cmake-3.14.0-Linux-x86_64.tar.gz && \
    tar -xvf cmake-3.14.0-Linux-x86_64.tar.gz && \
    cd cmake-3.14.0-Linux-x86_64 && \
    cp -r bin /usr/ && \
    cp -r share /usr/ && \
    cp -r doc /usr/share/ && \
    cp -r man /usr/share/ && \
    cd .. && \
    rm -rf cmake*

