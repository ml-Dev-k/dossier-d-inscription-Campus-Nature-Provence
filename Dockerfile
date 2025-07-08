FROM php:8.2-apache

# Augmentation de la limite mémoire pour Composer
ENV COMPOSER_MEMORY_LIMIT=-1

# Installation des dépendances système
RUN apt-get update && apt-get install -y \
    unzip \
    git \
    libzip-dev \
    zip \
    libxml2-dev \
    && docker-php-ext-install zip xml \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Installation de Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copie des fichiers de configuration
COPY composer.json composer.lock /var/www/html/

WORKDIR /var/www/html

# Installation avec options pour Render
RUN composer install --no-dev --optimize-autoloader --no-scripts --prefer-dist

# Copie du reste des fichiers
COPY . /var/www/html

# Permissions
RUN chown -R www-data:www-data /var/www/html

EXPOSE 80