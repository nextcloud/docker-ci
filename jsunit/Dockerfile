FROM debian
RUN apt update && apt install -y curl libfontconfig1 bzip2
RUN curl -sL https://deb.nodesource.com/setup_6.x | bash -
RUN apt-get install -y nodejs
ENV PHANTOMJS_BIN ./build/node_modules/phantomjs-prebuilt/bin/phantomjs
