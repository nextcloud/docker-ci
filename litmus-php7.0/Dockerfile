FROM nextcloudci/php7.0:php7.0-2
RUN apt-get update && apt-get install -y gcc realpath make
RUN mkdir -p /tmp/litmus
RUN wget -O /tmp/litmus/litmus-0.13.tar.gz http://www.webdav.org/neon/litmus/litmus-0.13.tar.gz
RUN cd /tmp/litmus && tar -xzf litmus-0.13.tar.gz
RUN cd /tmp/litmus/litmus-0.13 && ./configure && make
