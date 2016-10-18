FROM debian
RUN apt update && apt install -y curl libfontconfig1
RUN curl -sL https://deb.nodesource.com/setup_6.x | sudo -E bash -
RUN apt-get install -y nodejs
RUN ln -s /usr/bin/nodejs /usr/bin/node # https://github.com/Medium/phantomjs/issues/157
ENV PHANTOMJS_BIN ./build/node_modules/phantomjs-prebuilt/bin/phantomjs
