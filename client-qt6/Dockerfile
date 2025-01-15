FROM ubuntu:22.04

MAINTAINER Desktop Team <desktop@nextcloud.com>

# Run 'docker build' with '--build-arg BUILD_QT=1' to build Qt from source (default: not set)
ARG BUILD_QT

ENV VER_QT 6.8.0
ENV VER_QT_DATE 2024-12-02
ENV VER_OPENSSL 3.3.2
ENV VER_LIBP11 libp11-0.4.12
ENV VER_KFRAMEWORKS 6.7.0
ENV VER_SQLITE 3460100
ENV VER_SQLITE_YEAR 2024
ENV VER_QTKEYCHAIN 0.14.3

ENV QT_ROOT /opt/qt

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
        clang-15 \
        clang-format-15 \
        clang-tidy-15 \
        libclang-15-dev \
        cmake \
        zlib1g-dev \
        xz-utils \
        liblzma-dev \
        libzstd-dev \
        libbz2-dev \
        libfontconfig1-dev \
        libfreetype6-dev \
        libx11-dev \
        libx11-xcb-dev \
        libxext-dev \
        libxfixes-dev \
        libxi-dev \
        libxrender-dev \
        libxcb1-dev \
        libxcb-cursor-dev \
        libxcb-glx0-dev \
        libxcb-keysyms1-dev \
        libxcb-image0-dev \
        libxcb-shm0-dev \
        libxcb-icccm4-dev \
        libxcb-sync-dev \
        libxcb-xfixes0-dev \
        libxcb-shape0-dev \
        libxcb-randr0-dev \
        libxcb-render-util0-dev \
        libxcb-util-dev \
        libxcb-xinerama0-dev \
        libxcb-xkb-dev \
        libxkbcommon-dev \
        libxkbcommon-x11-dev \
        python3-sphinxcontrib.serializinghtml \
        python3-sphinxcontrib.qthelp \
        python3-sphinx \
        unzip \
        libsecret-1-dev \
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
        apt-get build-dep -q -y \
        qt6-base-dev \
        qt6-base-dev-tools \
        qt6-l10n-tools \
        qt6-declarative-dev-tools \
        qt6-connectivity-dev \
        qt6-multimedia-dev \
        qt6-webengine-dev \
        qt6-webengine-dev-tools \
        qt6-tools-dev \
        qt6-tools-dev-tools \
        qt6-declarative-dev \
        qt6-l10n-tools \
        qt6-quick3d-dev \
        qt6-translations-l10n \
        libqt6sql6-sqlite && \
        apt-get remove -y -q --purge libqt6core6 nodejs && \
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

# Download Qt sources
RUN if [ "$BUILD_QT" = "1" ] ; then echo Build Qt from source. && \
    git clone --depth=1 --recurse-submodules --shallow-submodules --branch=v${VER_QT} https://code.qt.io/qt/qt5.git qt6 \
    ; fi

RUN if [ "$BUILD_QT" = "1" ] ; then \
    wget https://nodejs.org/dist/v18.20.1/node-v18.20.1-linux-x64.tar.xz && \
    tar xf node-v18.20.1-linux-x64.tar.xz \
    ; fi

# Build Qt
RUN if [ "$BUILD_QT" = "1" ] ; then \
    mkdir qt6-${VER_QT}-linux-release && \
    cd qt6-${VER_QT}-linux-release && \
    export PATH=${PATH}:/node-v18.20.1-linux-x64/bin && \
    /qt6/configure -release -nomake examples -nomake tests -opensource -confirm-license -openssl-linked -skip qt3d -- \
        -DCMAKE_INSTALL_PREFIX=${QT_ROOT} -DOPENSSL_ROOT_DIR=/usr/local/lib64 -DQT_BUILD_TESTS=OFF -DQT_BUILD_EXAMPLES=ON -DQT_FEATURE_thread=ON -DQT_FEATURE_xcb=ON -DQT_FEATURE_openssl_runtime=OFF -DQT_FEATURE_openssl_linked=ON && \
    cmake --build . --parallel && \
    cmake --install . \
    ; fi

# Build Qt
RUN if [ "$BUILD_QT" = "1" ] ; then \
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
#   docker build . -t client-5.15 . --build-arg BUILD_QT=1
#

# Download Qt precompiled
ENV QT_TARBALL qt-bin-${VER_QT}-openssl-${VER_OPENSSL}-linux-x86_64-${VER_QT_DATE}.tar.xz

RUN if [ "$BUILD_QT" != "1" ] ; then \
    wget https://download.nextcloud.com/desktop/development/qt/${QT_TARBALL} && \
    tar -xvf ${QT_TARBALL} && \
    rm ${QT_TARBALL} \
    ; fi

RUN if [ "$BUILD_QT" != "1" ] ; then \
    DEBIAN_FRONTEND=noninteractive apt-get remove -q -y --purge libsqlite3-dev \
    ; fi

###########################################################################

# Install QtKeychain
RUN if [ "$BUILD_QT" != "1" ] ; then \
    cd /tmp && \
    git clone https://github.com/frankosterfeld/qtkeychain.git --depth 1 -b ${VER_QTKEYCHAIN} && \
    cd qtkeychain && \
    mkdir build && \
    cd build && \
    cmake -G Ninja -DCMAKE_BUILD_TYPE=Release -DBUILD_WITH_QT6=ON -DCMAKE_PREFIX_PATH=${QT_ROOT} .. && \
    cmake --build . --parallel && \
    cmake --install . && \
    cd .. && \
    rm -rf qtkeychain \
    ; fi

# Install extra-cmake-modules
RUN \
    cd /tmp && \
    git clone https://invent.kde.org/frameworks/extra-cmake-modules.git --depth 1 -b v${VER_KFRAMEWORKS} && \
    cd extra-cmake-modules && \
    mkdir build && \
    cd build && \
    cmake -G Ninja -DCMAKE_BUILD_TYPE=Release -DQT_MAJOR_VERSION=6 -DCMAKE_PREFIX_PATH=${QT_ROOT} .. && \
    cmake --build . --parallel && \
    cmake --install . && \
    cd .. && \
    rm -rf extra-cmake-modules \
    ;

# Install KArchive
RUN \
    cd /tmp && \
    git clone https://invent.kde.org/frameworks/karchive.git --depth 1 -b v${VER_KFRAMEWORKS} && \
    cd karchive && \
    mkdir build && \
    cd build && \
    cmake -G Ninja -DCMAKE_BUILD_TYPE=Release -DQT_MAJOR_VERSION=6 -DCMAKE_PREFIX_PATH=${QT_ROOT} .. && \
    cmake --build . --parallel && \
    cmake --install . && \
    cd .. && \
    rm -rf karchive \
    ;

# Setup sonar-scanner
ENV SONAR_SCANNER_VERSION 5.0.1.3006
ENV SONAR_SERVER_URL "https://sonarcloud.io"
ENV SONAR_SCANNER_DOWNLOAD_URL https://binaries.sonarsource.com/Distribution/sonar-scanner-cli/sonar-scanner-cli-${SONAR_SCANNER_VERSION}-linux.zip

RUN mkdir -p /opt/sonar && \
    curl -sSLo /opt/sonar/sonar-scanner.zip ${SONAR_SCANNER_DOWNLOAD_URL} && \
    unzip -o /opt/sonar/sonar-scanner.zip -d /opt/sonar/

ENV PATH=/opt/sonar/sonar-scanner-${SONAR_SCANNER_VERSION}-linux/bin:${PATH}

# Setup build-wrapper
ENV BUILD_WRAPPER_DOWNLOAD_URL ${SONAR_SERVER_URL}/static/cpp/build-wrapper-linux-x86.zip

RUN curl -sSLo /opt/sonar/build-wrapper-linux-x86.zip ${BUILD_WRAPPER_DOWNLOAD_URL} && \
          unzip -o /opt/sonar/build-wrapper-linux-x86.zip -d /opt/sonar/

ENV PATH=/opt/sonar/build-wrapper-linux-x86:${PATH}

# Install Python properly (e.g. for clang-tidy-pr-comments)
ENV AGENT_TOOLSDIRECTORY=/opt/hostedtoolcache
ENV RUNNER_TOOL_CACHE=/opt/hostedtoolcache
RUN mkdir -p "${AGENT_TOOLSDIRECTORY}"
