FROM php:8.2-apache

ENV COMPOSER_MEMORY_LIMIT=-1

RUN apt-get update && apt-get install -y \
    libonig-dev \
    unzip \
    git \
    libzip-dev \
    zip \
    libxml2-dev \
    && docker-php-ext-install zip xml mbstring \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*


COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

COPY composer.json /var/www/html/
# COPY composer.lock /var/www/html/  # décommentez si vous avez ce fichier

WORKDIR /var/www/html

RUN composer install --no-interaction --prefer-dist --optimize-autoloader

COPY . /var/www/html

RUN chown -R www-data:www-data /var/www/html

EXPOSE 80
