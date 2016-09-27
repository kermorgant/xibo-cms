FROM xibosignage/xibo-cms-dev:latest
MAINTAINER Mikael Kermorgant <mikael@kgtech.fi>

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

RUN curl -sL https://deb.nodesource.com/setup_4.x | sudo -E bash -

RUN apt-get update && apt-get install -y \
    nodejs \
    && rm -rf /var/lib/apt/lists/*