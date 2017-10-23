FROM debian
RUN apt-get update && apt-get install -y python-pil python-sphinx \ 
    python-pip rst2pdf texlive-fonts-recommended \
    texlive-latex-extra texlive-latex-recommended make php-cli composer

RUN pip2 install sphinxcontrib-phpdomain