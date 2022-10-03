FROM ubuntu:22.04

MAINTAINER Desktop Team <desktop@nextcloud.com>

# Run 'docker build' with '--build-arg BUILD_QT=1' to build Qt from source (default: not set)
ARG BUILD_QT

ENV VER_QT kde-5.15
ENV VER_QT_DATE 2022-10-03
ENV VER_OPENSSL 3.0.5
ENV VER_KFRAMEWORKS 5.98.0

ENV QT_ROOT /opt/qt${VER_QT}

# https://askubuntu.com/questions/158871/how-do-i-enable-the-source-code-repositories
RUN sed -i '/deb-src/s/^# //' /etc/apt/sources.list && \
    apt-get update && \
    apt-get install -y apt-transport-https ca-certificates gnupg software-properties-common wget && \
    DEBIAN_FRONTEND=noninteractive apt-get install -q -y software-properties-common && \
    rm -rf /var/lib/apt/lists/* && \
    # Ubuntus version of inkscape is outdated
    add-apt-repository -y ppa:inkscape.dev/stable && \
    apt-get update && \
    DEBIAN_FRONTEND=noninteractive apt-get install -q -y \
        libsqlite3-dev \
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
        clazy \
        cmake \
        zlib1g-dev \
        xz-utils \
# For cmocka based csync tests
        libcmocka-dev \
# Add libsecret for qtkeychain
        libsecret-1-dev \
# Add Qt build dependencies
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
        libclang-14-dev \
        llvm-14 \
# generate png images from svg
        inkscape \
# SonarCloud
        openjdk-11-jdk \
# install xvfb to enable graphical tests
		xvfb \
# html documentation generation
        python3-sphinx \
# Qt Web Engine
        nodejs \
        libxkbfile-dev && \
# https://wiki.qt.io/Building_Qt_5_from_Git
    apt-get build-dep -y libqt5core5a libqt5webengine5 && \
# Cleaning up
    DEBIAN_FRONTEND=noninteractive apt-get remove -q -y --purge libssl-dev && \
    apt-get clean && \
    rm -rf /var/lib/apt/lists/*

###########################################################################

COPY openssl-deprecation.patch /tmp/openssl-deprecation.patch

# Install openssl
RUN cd /tmp && \
    wget https://www.openssl.org/source/openssl-${VER_OPENSSL}.tar.gz && \
    tar -xvf openssl-${VER_OPENSSL}.tar.gz && \
    cd openssl-${VER_OPENSSL} && \
    patch -p 0 < ../openssl-deprecation.patch && \
    ./config && \
    make -j$(nproc) && \
    make -j$(nproc) install && \
    cd .. && \
    rm -rf openssl*

###########################################################################

# Download Qt sources
RUN if [ "$BUILD_QT" = "1" ] ; then echo Build Qt from source. && \
    git clone https://invent.kde.org/qt/qt/qt5.git && \
    cd qt5 && \
    git checkout kde/5.15 && \
    ./init-repository --module-subset=default,-qt3d,-qtlocation \
    ; fi

COPY python2.patch /tmp/python2.patch
RUN if [ "$BUILD_QT" = "1" ] ; then \
    cd /qt5/qtwebengine && \
    patch -p 1 < /tmp/python2.patch && \
    cd / \
    ; fi

# Build Qt
RUN if [ "$BUILD_QT" = "1" ] ; then \
    mkdir qt5-${VER_QT}-linux-release && \
    cd qt5-${VER_QT}-linux-release && \
     OPENSSL_LIBS='-L/usr/local/lib -lssl -lcrypto' ../qt5/configure -release -prefix ${QT_ROOT} -nomake examples -nomake tests -opensource -confirm-license -openssl-linked -skip qt3d && \
    make -j$(nproc) && \
    make -j$(nproc) install \
    ; fi

# Build Qt
RUN if [ "$BUILD_QT" = "1" ] ; then \
    # Do not require matching versions of Qt dependencies for WebEngine modules
    sed -i "s/5.15.11 /5.15.6 /" ${QT_ROOT}/lib/cmake/*/*Config.cmake \
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
    DEBIAN_FRONTEND=noninteractive apt-get remove -q -y --purge qtbase5-dev \
    ; fi

###########################################################################

ENV QTDIR ${QT_ROOT}
ENV PATH ${QT_ROOT}/bin:${PATH}
ENV LD_LIBRARY_PATH ${QT_ROOT}/lib/x86_64-linux-gnu:${QT_ROOT}/lib:/usr/local/lib:${LD_LIBRARY_PATH}
ENV PKG_CONFIG_PATH ${QT_ROOT}/lib/pkgconfig:${PKG_CONFIG_PATH}

# Install QtKeychain
RUN if [ "$BUILD_QT" != "1" ] ; then \
    cd /tmp && \
    git clone https://github.com/frankosterfeld/qtkeychain.git --depth 1 -b v0.12.0 && \
    cd qtkeychain && \
    mkdir build && \
    cd build && \
    cmake -G Ninja -DCMAKE_BUILD_TYPE=Release .. && \
    ninja install && \
    cd .. && \
    rm -rf qtkeychain \
    ; fi

# Install KArchive
RUN if [ "$BUILD_QT" != "1" ] ; then \
    pip3 install sphinxcontrib-qthelp && \
    cd /tmp && \
    git clone https://invent.kde.org/frameworks/extra-cmake-modules.git --depth 1 -b v${VER_KFRAMEWORKS} && \
    cd extra-cmake-modules && \
    mkdir build && \
    cd build && \
    cmake -G Ninja -DCMAKE_BUILD_TYPE=Release .. && \
    ninja install && \
    cd .. && \
    rm -rf extra-cmake-modules \
    ; fi

RUN if [ "$BUILD_QT" != "1" ] ; then \
    cd /tmp && \
    git clone https://invent.kde.org/frameworks/karchive.git --depth 1 -b v${VER_KFRAMEWORKS} && \
    cd karchive && \
    mkdir build && \
    cd build && \
    cmake -G Ninja -DCMAKE_BUILD_TYPE=Release .. && \
    ninja install && \
    cd .. && \
    rm -rf karchive \
    ; fi

# Setup sonar-scanner
ENV SONAR_SCANNER_VERSION 4.7.0.2747
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
