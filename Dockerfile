FROM php:8.2-apache

# 1. Installation des dépendances système et extensions PHP
RUN apt-get update && apt-get install -y --no-install-recommends \
    libpq-dev \
    libzip-dev \
    zip \
    unzip \
    git \
    && docker-php-ext-install pdo_mysql pdo_pgsql zip

# 2. Activation du mod_rewrite pour les routes Laravel
RUN a2enmod rewrite

# 3. Configuration d'Apache pour le port dynamique de Render
# Remplace le port 80 par la variable $PORT dans toute la configuration
RUN sed -i 's/80/${PORT}/g' /etc/apache2/sites-available/000-default.conf /etc/apache2/ports.conf

# 4. Pointer le DocumentRoot vers le dossier /public de Laravel
RUN sed -i 's|/var/www/html|/var/www/html/public|g' /etc/apache2/sites-available/000-default.conf
RUN sed -i 's|/var/www/html|/var/www/html/public|g' /etc/apache2/apache2.conf

# 5. Copie des fichiers du projet
COPY . /var/www/html

# 6. Installation de Composer et des dépendances PHP
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
ENV COMPOSER_ALLOW_SUPERUSER=1
RUN composer install --no-dev --optimize-autoloader

# 7. Gestion des permissions pour le stockage et le cache
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

# 8. Nettoyage de la configuration
RUN php artisan config:clear

# 9. Exposition du port (informatif, Render utilise la variable $PORT)
EXPOSE ${PORT}

# 10. COMMANDE FINALE : Exécute les migrations puis lance Apache
# Le "&&" est crucial : il lance le serveur seulement si les migrations ne plantent pas.
# "apache2-foreground" maintient le conteneur allumé.
CMD php artisan migrate --force --seed ; apache2-foreground