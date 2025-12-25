FROM php:8.2-fpm

RUN apt-get update && apt-get install -y \
    libpq-dev \
    libxml2-dev \
    && docker-php-ext-install pdo pdo_pgsql xml simplexml

WORKDIR /var/www/html