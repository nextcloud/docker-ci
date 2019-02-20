FROM nextcloudci/translations:translations-31

MAINTAINER Morris Jobke <hey@morrisjobke.de>

# Install awk
RUN apk update && \
    apk add gawk && \
    rm -rf /var/cache/apk/*

ADD handlePlainTranslations.sh /handleTranslations.sh
