# ubuntu:17.10 instead of debian because of the dependency to PHP 7.1
FROM ubuntu:17.10
RUN apt-get update && apt-get install -y python-pil python-sphinx \
    python-pip rst2pdf texlive-fonts-recommended \
    texlive-latex-extra texlive-latex-recommended make php-cli composer \
    nodejs npm

RUN pip2 install sphinxcontrib-phpdomain
