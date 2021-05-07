FROM php:7.3-apache

# General setup
RUN apt-get update && apt-get install -y \
    autoconf zlib1g-dev \
    unzip libzip-dev \
    && rm -rf /var/lib/apt/lists/*

#RUN pecl install grpc
#RUN pecl install protobuf

RUN docker-php-ext-install zip gettext mysqli

COPY ./php-prod.ini /usr/local/etc/php/php.ini

# Install composer and pull down packages
RUN php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
RUN php composer-setup.php --version 2.0.13
RUN php -r "unlink('composer-setup.php');"

COPY composer.json /var/www/html/
COPY composer.lock /var/www/html/

#RUN php composer.phar require google/cloud-firestore
#RUN php composer.phar require knplabs/knp-snappy

# Copy in code
COPY ./ /var/www/html/

# Translations
RUN chmod +x /var/www/html/translation/compile.sh
