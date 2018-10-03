FROM nextcloudci/translations:translations-31

MAINTAINER Morris Jobke <hey@morrisjobke.de>

RUN apk update && \
    apk add php7-dom && \
    rm -rf /var/cache/apk/*

ADD handleChangelogServerTranslations.sh /handleTranslations.sh
ADD translationtool-whatsnew/ /translationtool

