FROM php:8.3-apache

# System dependencies
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libzip-dev \
    $PHPIZE_DEPS \
    && docker-php-ext-install zip \
    && pecl install grpc \
    && docker-php-ext-enable grpc \
    && rm -rf /var/lib/apt/lists/* /tmp/pear

# Apache rewrite
RUN a2enmod rewrite

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Composer files first for Docker cache
COPY composer.json composer.lock* ./

# Install dependencies
RUN composer install \
    --no-dev \
    --prefer-dist \
    --no-interaction \
    --no-progress \
    --optimize-autoloader

# Application files
COPY . .

RUN chown -R www-data:www-data /var/www/html

EXPOSE 80

CMD ["apache2-foreground"]
