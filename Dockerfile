FROM php:8.2-apache

# Installation des extensions PHP nécessaires
RUN apt-get update && apt-get install -y --no-install-recommends \
    libpq-dev libzip-dev zip unzip git \
    && docker-php-ext-install pdo_mysql pdo_pgsql zip

# Activation du mod_rewrite d'Apache
RUN a2enmod rewrite

# --- AJOUT POUR RENDER : Configurer Apache pour écouter sur le port 10000 ---
RUN sed -i 's/80/${PORT}/g' /etc/apache2/sites-available/000-default.conf /etc/apache2/ports.conf

# Copie du projet
COPY . /var/www/html

# Installation de Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
ENV COMPOSER_ALLOW_SUPERUSER=1
RUN composer install --no-dev --optimize-autoloader

# Ajustement des permissions
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

# Nettoyage et optimisation
RUN php artisan config:clear

# Pointer le DocumentRoot vers le dossier public de Laravel
RUN sed -i 's|/var/www/html|/var/www/html/public|g' /etc/apache2/sites-available/000-default.conf
RUN sed -i 's|/var/www/html|/var/www/html/public|g' /etc/apache2/apache2.conf

# Render utilise la variable $PORT (souvent 10000)
EXPOSE ${PORT}

# Lancement d'Apache en premier plan
# CMD ["apache2-foreground"]
# Remplace CMD ["apache2-foreground"] par :
CMD php artisan serve --host=0.0.0.0 --port=10000