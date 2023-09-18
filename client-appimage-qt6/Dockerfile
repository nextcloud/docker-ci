FROM ubuntu:23.04

MAINTAINER Desktop Team <desktop@nextcloud.com>

ENV VER_OPENSSL 3.0.10
ENV VER_LIBP11 libp11-0.4.12
ENV VER_KFRAMEWORKS 5.110.0
ENV VER_SQLITE 3430000
ENV VER_SQLITE_YEAR 2023

ENV QT_ROOT /opt/qt${VER_QT}

RUN sed -i '/deb-src/s/^# //' /etc/apt/sources.list && \
    apt-get update && \
    apt-get install -y apt-transport-https ca-certificates gnupg software-properties-common wget && \
    apt-get update && \
    DEBIAN_FRONTEND=noninteractive apt-get install -q -y \
        git \
        ninja-build \
        curl \
        jq \
        perl \
        python3 \
        python3-pip \
        software-properties-common \
        build-essential \
        mesa-common-dev \
        pkg-config \
        ninja-build \
        gcc-11 \
        g++-11 \
        clang-14 \
        clang-format-14 \
        clang-tidy-14 \
        clang-tidy \
        clazy \
        cmake \
        zlib1g-dev \
        xz-utils \
        qtkeychain-qt6-dev \
        qt6-base-dev \
        qt6-base-dev-tools \
        qt6-l10n-tools \
        qt6-declarative-dev-tools \
        qt6-connectivity-dev \
        qt6-multimedia-dev \
        qt6-webengine-dev \
        qt6-webengine-dev-tools \
        qt6-websockets-dev \
        qt6-tools-dev \
        qt6-tools-dev-tools \
        qt6-5compat-dev \
        qt6-declarative-dev \
        qt6-qmllint-plugins \
        qt6-qmltooling-plugins \
        qt6-l10n-tools \
        qt6-quick3d-dev \
        qt6-translations-l10n \
        qt6-svg-dev \
        qml6-module-qtquick3d-spatialaudio \
        libqt6sql6-sqlite \
        liblzma-dev \
        libzstd-dev \
        libbz2-dev \
        python3-sphinxcontrib.serializinghtml \
        python3-sphinxcontrib.qthelp \
        python3-sphinx \
        unzip \
        qt6-base-private-dev \
        qmake6 \
# for updating app images
        zsync \
# For cmocka based csync tests
        libcmocka-dev \
# generate png images from svg
        inkscape \
# SonarCloud
        openjdk-19-jdk \
# install xvfb to enable graphical tests
        xvfb \
# html documentation generation
        python3-sphinx && \
# Cleaning up
        DEBIAN_FRONTEND=noninteractive apt-get remove -q -y --purge libssl-dev && \
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
    make -j$(nproc) install && \
    cd .. && \
    rm -rf openssl*

# Install libp11 for PKCS#11 encryption
RUN cd /tmp && \
    wget https://github.com/OpenSC/libp11/releases/download/${VER_LIBP11}/${VER_LIBP11}.tar.gz && \
    tar xvf ${VER_LIBP11}.tar.gz && \
    cd ${VER_LIBP11} && \
    export PKG_CONFIG_PATH=/usr/local/lib64/pkgconfig && \
    ./configure && make && make install && \
    cd .. && \
    rm -rf ${VER_LIBP11}

# Install sqlite3 from https://www.sqlite.org/amalgamation.html
# https://www3.sqlite.org/src/dir?ci=tip&name=autoconf
RUN cd /tmp && \
    wget https://www.sqlite.org/${VER_SQLITE_YEAR}/sqlite-autoconf-${VER_SQLITE}.tar.gz && \
    tar xvf sqlite-autoconf-${VER_SQLITE}.tar.gz && \
    cd sqlite-autoconf-${VER_SQLITE} && \
    ./configure && make && make install && \
    cd .. && \
    rm -rf sqlite-autoconf-${VER_SQLITE}

###########################################################################

# Install KArchive
RUN \
    cd /tmp && \
    git clone https://invent.kde.org/frameworks/extra-cmake-modules.git --depth 1 -b v${VER_KFRAMEWORKS} && \
    cd extra-cmake-modules && \
    mkdir build && \
    cd build && \
    cmake -G Ninja -DCMAKE_BUILD_TYPE=Release -DQT_MAJOR_VERSION=6 .. && \
    ninja install && \
    cd .. && \
    rm -rf extra-cmake-modules \
    ;

RUN \
    cd /tmp && \
    git clone https://invent.kde.org/frameworks/karchive.git --depth 1 -b v${VER_KFRAMEWORKS} && \
    cd karchive && \
    mkdir build && \
    cd build && \
    cmake -G Ninja -DCMAKE_BUILD_TYPE=Release -DQT_MAJOR_VERSION=6 .. && \
    ninja install && \
    cd .. && \
    rm -rf karchive \
    ;
