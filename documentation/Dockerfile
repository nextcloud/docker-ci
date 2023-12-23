FROM debian:bookworm-slim
RUN apt-get update && apt-get install -y python3-pil \
    python3-pip texlive-fonts-recommended latexmk \
    texlive-latex-extra texlive-latex-recommended make php-cli composer \
    nodejs npm libgbm1 libasound2 \
    && rm -rf /var/lib/apt/lists/*
