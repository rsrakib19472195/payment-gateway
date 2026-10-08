FROM php:8.3-apache

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
       libsqlite3-dev \
       pkg-config \
       git \
       unzip \
       libzip-dev \
       autoconf \
       gcc \
       g++ \
       make \
    && docker-php-ext-install pdo_sqlite \
    && pecl install grpc \
    && docker-php-ext-enable grpc \
    && rm -rf /var/lib/apt/lists/*

RUN a2enmod rewrite headers

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

COPY . /var/www/html/

WORKDIR /var/www/html

RUN composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction

RUN mkdir -p /var/www/html/data \
    && chown -R www-data:www-data /var/www/html/data \
    && chmod -R 775 /var/www/html/data

EXPOSE 80

CMD ["apache2-foreground"]
