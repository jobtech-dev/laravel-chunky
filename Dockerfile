FROM php:8.3-fpm-bookworm

COPY --from=composer:2.2 /usr/bin/composer /usr/bin/composer

RUN apt update && apt install -y libzip-dev zip libpng-dev \
    && docker-php-ext-install zip gd
