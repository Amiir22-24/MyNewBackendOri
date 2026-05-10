FROM php:8.2-apache

# Installation des extensions PHP nécessaires pour Laravel
RUN apt-get update && apt-get install -y --no-install-recommends \
    libpq-dev libzip-dev zip unzip git \
    && docker-php-ext-install pdo_mysql pdo_pgsql zip

# Activation du mod_rewrite d'Apache (nécessaire pour les routes Laravel)
RUN a2enmod rewrite

# Copie du projet
COPY . /var/www/html

# Installation de Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
RUN composer install --no-dev --optimize-autoloader
RUN php artisan config:clear

# Ajustement des permissions pour Laravel
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

# Port par défaut
EXPOSE 80