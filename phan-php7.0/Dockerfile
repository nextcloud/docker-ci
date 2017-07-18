FROM nextcloudci/php7.0:php7.0-8
RUN apt-get update && apt-get -y install gcc wget unzip && apt-get autoremove -y && apt-get autoclean && apt-get clean && rm -rf /tmp/* /var/tmp/* /var/lib/apt/lists/*
RUN cd /tmp/ && wget https://github.com/nikic/php-ast/archive/master.zip && unzip master.zip
RUN cd /tmp/php-ast-master/ && phpize && ./configure && make && make install
RUN echo "extension=ast.so" >> /etc/php/7.0/cli/conf.d/20-ast.ini

