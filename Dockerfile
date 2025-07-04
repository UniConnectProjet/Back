# Utilise PHP 8.2 avec FPM
FROM php:8.2-fpm

# Installer les dépendances nécessaires pour MySQL
RUN apt-get update && apt-get install -y \
    git unzip libzip-dev curl zip \
    && docker-php-ext-install pdo_mysql zip opcache

# Installer Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Créer un utilisateur non-root
RUN useradd -m -u 1000 symfony

# Définir le dossier de travail
WORKDIR /var/www/html

# Créer les dossiers nécessaires (var/cache et var/log)
RUN mkdir -p var/cache var/log && chown -R symfony:symfony var && chmod -R 775 var && chmod -R 777 var/cache var/log

# Copier les fichiers de l'application
COPY . .

# Donner les droits au bon utilisateur pour le dossier entier
RUN chown -R symfony:symfony /var/www/html

# Fixer les permissions sur les dossiers de cache et log
RUN chmod -R 775 var && chmod -R 777 var/cache var/log

# Définir l'utilisateur par défaut
USER symfony

# Lancer le serveur PHP natif en exposant le dossier public
CMD ["php-fpm"]