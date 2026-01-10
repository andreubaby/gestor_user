FROM php:8.5.2RC1-cli-alpine3.22

# Instalar dependencias del sistema (sin cron)
RUN apt-get update && apt-get install -y \
    build-essential \
    libpng-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    zip \
    unzip \
    curl \
    git \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install \
        pdo_mysql \
        mbstring \
        zip \
        exif \
        pcntl \
        gd \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Instalar Composer (desde imagen oficial)
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Directorio de trabajo
WORKDIR /var/www/html

# Copiar archivos del proyecto
COPY ../.. .

# Instalar dependencias de Laravel
RUN composer install --optimize-autoloader --no-dev

# Permisos correctos para Laravel
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Exponer el puerto PHP-FPM
EXPOSE 9000

# Comando por defecto (php-fpm)
CMD ["php-fpm"]
