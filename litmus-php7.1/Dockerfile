FROM nextcloudci/php7.1:php7.1-16
RUN apt-get update && apt-get install -y gcc realpath make python && \
    mkdir -p /tmp/litmus && \
    wget -O /tmp/litmus/litmus-0.13.tar.gz http://www.webdav.org/neon/litmus/litmus-0.13.tar.gz && \
    cd /tmp/litmus && tar -xzf litmus-0.13.tar.gz && \
    cd /tmp/litmus/litmus-0.13 && ./configure && make && rm -f /tmp/litmus-0.13.tar.gz && \
    apt-get clean
