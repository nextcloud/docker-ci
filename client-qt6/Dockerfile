FROM debian:trixie

MAINTAINER Desktop Team <desktop@nextcloud.com>

RUN \
    apt-get update && \
    apt-get install -y apt-transport-https ca-certificates gnupg wget && \
    apt-get update && \
    DEBIAN_FRONTEND=noninteractive apt-get install -q -y \
        git \
        ninja-build \
        curl \
        jq \
        perl \
        python3 \
        python3-pip \
        build-essential \
        mesa-common-dev \
        pkg-config \
        ninja-build \
        gcc \
        g++ \
        clang \
        clang-format \
        clang-tidy \
        libclang-dev \
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
        unzip \
        libsecret-1-dev \
        libssl-dev \
        libp11-dev \
        qt6-websockets-dev \
        qt6-svg-dev \
        libkf6archive-dev \
        extra-cmake-modules \
        qtkeychain-qt6-dev \
        libsqlite3-dev \
        qt6-5compat-dev \
# For cmocka based csync tests
        libcmocka-dev \
# generate png images from svg
        inkscape \
# SonarCloud
        openjdk-21-jdk \
# install xvfb to enable graphical tests
        xvfb \
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
# Cleaning up
        apt-get clean && \
        rm -rf /var/lib/apt/lists/*

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
