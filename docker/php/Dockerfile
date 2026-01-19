FROM php:8.4-fpm

RUN apt-get update && apt-get install -y \
    git unzip zip curl \
    pdftk-java \
    libpng-dev libjpeg62-turbo-dev libfreetype6-dev \
    libonig-dev libxml2-dev libzip-dev \
 && docker-php-ext-configure gd --with-freetype --with-jpeg \
 && docker-php-ext-install pdo_mysql mbstring zip exif pcntl gd \
 && rm -rf /var/lib/apt/lists/*

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html
COPY . .

RUN git config --global --add safe.directory /var/www/html \
 && chown -R www-data:www-data /var/www/html

RUN composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist \
 && test -f /var/www/html/vendor/autoload.php

RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache \
 && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

EXPOSE 9000
CMD ["php-fpm"]
