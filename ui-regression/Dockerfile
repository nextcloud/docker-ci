FROM alekzonder/puppeteer:latest

USER root

RUN apt-get update && apt-get install -y sudo git curl && \
    apt-get autoremove -y && apt-get autoclean && apt-get clean && \
    rm -rf /tmp/* /var/tmp/* /var/lib/apt/lists/*

