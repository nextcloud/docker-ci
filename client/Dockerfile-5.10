FROM ubuntu:xenial

MAINTAINER Roeland Jago Douma <roeland@famdouma.nl>

RUN apt-get update && \
    apt-get install -y wget libsqlite3-dev git curl \
        software-properties-common build-essential mesa-common-dev

# Add Qt-5.10.1
RUN add-apt-repository -y ppa:beineri/opt-qt-5.10.1-xenial && \
    apt-get update && \
    apt-get install -y qt510base qt510tools qt510webengine qt510svg

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
    wget https://www.openssl.org/source/openssl-1.1.0h.tar.gz && \
    tar -xvf openssl-1.1.0h.tar.gz && \
    cd openssl-1.1.0h && \
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
    wget https://cmake.org/files/v3.11/cmake-3.11.4-Linux-x86_64.tar.gz && \
    tar -xvf cmake-3.11.4-Linux-x86_64.tar.gz && \
    cd cmake-3.11.4-Linux-x86_64 && \
    cp -r bin /usr/ && \
    cp -r share /usr/ && \
    cp -r doc /usr/share/ && \
    cp -r man /usr/share/ && \
    cd .. && \
    rm -rf cmake*
