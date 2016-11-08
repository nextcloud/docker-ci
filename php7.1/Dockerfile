FROM centos

RUN yum install -y \
      https://dl.fedoraproject.org/pub/epel/epel-release-latest-7.noarch.rpm \
      http://rpms.remirepo.net/enterprise/remi-release-7.rpm \
      yum-utils \
    && rm -rf /var/cache/yum/* \
    && yum clean all \
    && yum-config-manager --enable remi-php71 \
    && yum -y update \
    && yum install -y \
      php71.x86_64 php71-php-intl.x86_64 php71-php-gd.x86_64 \
      php71-php-cli.x86_64 php71-php-pecl-imagick.x86_64 \
      php71-php-pgsql.x86_64 php71-php-mcrypt.x86_64 \
      php71-php-ldap.x86_64 php71-php-pecl-apcu.x86_64 \
      php71-php-pecl-redis.x86_64  php71-php-pecl-mysql.x86_64 \
      php71-php-pecl-zip.x86_64 php71-php-xml.x86_64 \
      php71-php-mbstring.x86_64 php71-php-process.x86_64 \
      php71-php-pecl-xdebug.x86_64 git curl which make \
    && rm -rf /var/cache/yum/* \
    && yum clean all
RUN ln -s /usr/bin/php71 /usr/bin/php
RUN curl -O -L https://phar.phpunit.de/phpunit-5.5.4.phar \
    && chmod +x phpunit-5.5.4.phar \
    && mv phpunit-5.5.4.phar /usr/local/bin/phpunit
RUN curl -O -L https://getcomposer.org/download/1.1.2/composer.phar \
    && chmod +x composer.phar \
    && mv composer.phar /usr/local/bin/composer

ADD nextcloud.ini /etc/opt/remi/php71/php.d/nextcloud.ini
