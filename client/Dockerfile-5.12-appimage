FROM ubuntu:16.04

MAINTAINER Felix Weilbach <felix.weilbach@nextcloud.com>

# Run 'docker build' with '--build-arg BUILD_QT=1' to build Qt from source (default: not set)
ARG BUILD_QT

ENV VER_QT 5.12.10
ENV VER_QT_DATE 2021-06-01
ENV VER_OPENSSL 1.1.1k

ENV QT_ROOT /opt/qt${VER_QT}

# https://askubuntu.com/questions/158871/how-do-i-enable-the-source-code-repositories
RUN sed -i '/deb-src/s/^# //' /etc/apt/sources.list && \
    # Ubuntus version of inkscape is outdated
    apt-get update && \
    apt-get install -y apt-transport-https && \
    DEBIAN_FRONTEND=noninteractive apt-get install -q -y software-properties-common && \
    rm -rf /var/lib/apt/lists/* && \
    add-apt-repository -y ppa:inkscape.dev/stable && \
    apt-get update && \
    DEBIAN_FRONTEND=noninteractive apt-get install -q -y \
        wget \
        libsqlite3-dev \
        git \
        curl \
        jq \
        perl \
        python \
        software-properties-common \
        build-essential \
        mesa-common-dev \
        pkg-config \
        ninja-build \
        gcc \
        g++ \
        clang \
        clang-format \
        clang-tidy \
        cmake \
        zlib1g-dev \
        xz-utils \
# For cmocka based csync tests
        libcmocka-dev \
# Add libsecret for qtkeychain
        libsecret-1-dev \
# Add Qt-5.12 build dependencies
        libclang-dev \
        gperf \
        flex \
        bison \
# Libxcb, libxcb-xinerama0-dev
        '^libxcb.*-dev' \
        libx11-xcb-dev \
        libglu1-mesa-dev \
        libxrender-dev \
        libxi-dev \
# OpenGL support
        libicu-dev \
        libxslt-dev \
        ruby \
# Qt WebEngine
        libssl-dev \
        libxcursor-dev \
        libxcomposite-dev \
        libxdamage-dev \
        libxrandr-dev \
        libdbus-1-dev \
        libfontconfig1-dev \
        libcap-dev \
        libxtst-dev \
        libpulse-dev \
        libudev-dev \
        libpci-dev \
        libnss3-dev \
        libasound2-dev \
        libxss-dev \
        libegl1-mesa-dev \
        libbz2-dev \
        libgcrypt20-dev \
        libdrm-dev \
        libcups2-dev \
        libatkmm-1.6-dev \
# Qt Multimedia
        libasound2-dev \
        libgstreamer1.0-dev \
        libgstreamer-plugins-base1.0-dev \
# QDoc Documentation Generator Tool
        libclang-dev \
        llvm \
# generate png images from svg
        inkscape \
# html documentation generation
        python3-sphinx && \
# https://wiki.qt.io/Building_Qt_5_from_Git
    apt-get build-dep -y qt5-default && \
# Cleaning up
    apt-get clean && \
    rm -rf /var/lib/apt/lists/*

###########################################################################

# Install openssl
RUN cd /tmp && \
    wget https://www.openssl.org/source/openssl-${VER_OPENSSL}.tar.gz && \
    tar -xvf openssl-${VER_OPENSSL}.tar.gz && \
    cd openssl-${VER_OPENSSL} && \
    ./config && \
    make -j$(nproc) && \
    make install && \
    cd .. && \
    rm -rf openssl*

###########################################################################

# Download Qt-5.12 sources
RUN if [ "$BUILD_QT" = "1" ] ; then echo Build Qt from source. && \
      wget https://download.qt.io/official_releases/qt/5.12/${VER_QT}/single/qt-everywhere-src-${VER_QT}.tar.xz && \
      tar -xvf qt-everywhere-src-${VER_QT}.tar.xz && \
      cd qt-everywhere-src-${VER_QT} \
    ; fi

# Build Qt-5.12
RUN if [ "$BUILD_QT" = "1" ] ; then \
      cd qt-everywhere-src-${VER_QT} && \
      OPENSSL_LIBS='-L/usr/local/lib -lssl -lcrypto' ./configure -nomake tests -nomake examples -opensource \
          -confirm-license -release -openssl-linked -prefix ${QT_ROOT} && \
      make -j$(nproc) && \
      make install && \
      cd .. && \
      rm -rf qt-everywhere* && \
      tar cfJ /qt-bin-${VER_QT}-openssl-${VER_OPENSSL}-linux-x86_64-$(date +"%Y-%m-%d").tar.xz ${QT_ROOT} \
    ; fi

#
# The following precompiled Qt package has been built with the commands above, using this Dockerfile.
#
# Since it takes a very long time to compile, the build on Docker Hub fails due to a timeout.
#
# This is why we're going to use our own precompiled version here.
#
# Run 'docker build' with '--build-arg BUILD_QT=1' to build Qt from source (default: not set)
# on a dedicated build machine:
#
#   docker build -f Dockerfile-5.12 -t client-5.12 . --build-arg BUILD_QT=1
#

# Download Qt-5.12 precompiled
ENV QT_TARBALL qt-bin-${VER_QT}-openssl-${VER_OPENSSL}-linux-x86_64-${VER_QT_DATE}.tar.xz

RUN if [ "$BUILD_QT" != "1" ] ; then echo Download precompiled Qt. && \
      wget https://download.nextcloud.com/desktop/development/qt/${QT_TARBALL} && \
      tar -xvf ${QT_TARBALL} && \
      rm ${QT_TARBALL} \
    ; fi

###########################################################################

ENV QTDIR ${QT_ROOT}
ENV PATH ${QT_ROOT}/bin:${PATH}
ENV LD_LIBRARY_PATH ${QT_ROOT}/lib/x86_64-linux-gnu:${QT_ROOT}/lib:/usr/local/lib:${LD_LIBRARY_PATH}
ENV PKG_CONFIG_PATH ${QT_ROOT}/lib/pkgconfig:${PKG_CONFIG_PATH}

# Install QtKeychain
RUN cd /tmp && \
    git clone https://github.com/frankosterfeld/qtkeychain.git --depth 1 -b v0.10.0 && \
    cd qtkeychain && \
    mkdir build && \
    cd build && \
    cmake ../ && \
    make -j$(nproc) && \
    make install && \
    cd .. && \
    rm -rf qtkeychain
