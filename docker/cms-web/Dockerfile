<<<<<<< HEAD
FROM xibosignage/xibo-cms-dev:latest
=======
FROM xibo-cms-dev:latest
>>>>>>> 84469b9... first step in experiment to bypass vagrant and use docker directly
MAINTAINER Mikael Kermorgant <mikael@kgtech.fi>

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

RUN curl -sL https://deb.nodesource.com/setup_4.x | sudo -E bash -

RUN apt-get update && apt-get install -y \
<<<<<<< HEAD
    nodejs \
    && rm -rf /var/lib/apt/lists/*
=======
    curl \
    nodejs

RUN cd /data/web && composer install

RUN npm install  --global gulp-cli
>>>>>>> 84469b9... first step in experiment to bypass vagrant and use docker directly
