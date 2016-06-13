FROM debian
RUN apt update && apt install -y nodejs npm
RUN ln -s /usr/bin/nodejs /usr/bin/node # https://github.com/Medium/phantomjs/issues/157
ENV PHANTOMJS_BIN ./build/node_modules/phantomjs-prebuilt/bin/phantomjs
