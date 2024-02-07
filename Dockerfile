FROM php:8.1-fpm

COPY --from=composer:2.2.22 /usr/bin/composer /usr/bin/composer

RUN apt update && apt install -y libzip-dev zip libpng-dev \
    && docker-php-ext-install zip gd
