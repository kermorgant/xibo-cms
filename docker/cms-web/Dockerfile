FROM xibosignage/xibo-cms-dev:latest

MAINTAINER Mikael Kermorgant <mikael@kgtech.fi>

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

RUN curl -sL https://deb.nodesource.com/setup_4.x | sudo -E bash -

RUN apt-get update && apt-get install -y \
    curl \
    nodejs \
    && rm -rf /var/lib/apt/lists/*

RUN cd /var/www/cms && composer install

RUN npm install  --global gulp-cli
    nodejs \
    && rm -rf /var/lib/apt/lists/*
