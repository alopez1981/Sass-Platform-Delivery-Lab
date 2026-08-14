FROM php:8.4-fpm

RUN apt-get update && apt-get install -y --no-install-recommends \
        libzip-dev unzip git \
    && docker-php-ext-install pdo_mysql pcntl bcmath sockets \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY docker/php-fpm-logging.conf /usr/local/etc/php-fpm.d/zz-logging.conf

WORKDIR /var/www/html

COPY composer.json composer.lock ./
RUN composer install --no-interaction --no-scripts --no-autoloader

COPY . .
RUN composer dump-autoload --optimize

EXPOSE 9000
