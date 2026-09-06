FROM mcr.microsoft.com/windows:ltsc2019

MAINTAINER Desktop Team <desktop@nextcloud.com>

ENV CRAFT_TARGET windows-msvc2022_64-cl

COPY craftmaster.ini c:\Nextcloud

RUN \
    mkdir \Nextcloud; \
    cd \Nextcloud; \
    git clone https://invent.kde.org/packaging/craftmaster "C:/Craft/craftmaster/"; \

