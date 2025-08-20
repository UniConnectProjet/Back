FROM composer:2 AS vendor
WORKDIR /app

# Copier uniquement les fichiers de dépendances pour profiter du cache Docker
COPY composer.json composer.lock ./
# Installe les vendors (prod)
RUN composer install --no-dev --prefer-dist --no-interaction --no-progress

# Copier le reste du code (pour éventuellement générer l'autoload optimisé)
COPY . .
RUN composer dump-autoload --optimize --classmap-authoritative

FROM php:8.3-fpm
RUN apt-get update && apt-get install -y \
    git unzip libzip-dev curl zip libicu-dev \
  && docker-php-ext-install pdo_mysql zip opcache \
  && docker-php-ext-configure intl \
  && docker-php-ext-install intl \
  && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/html

# Copier les clés générées dans ton repo (ex: config/jwt)
COPY --chown=symfony:symfony config/jwt /etc/jwt
RUN chmod 640 /etc/jwt/*.pem

# Copier le code + vendors depuis le stage builder
COPY --from=vendor /app /var/www/html

# Préparer les dossiers var/
RUN mkdir -p var/cache var/log \
 && chown -R symfony:symfony var \
 && chmod -R 775 var \
 && chmod -R 777 var/cache var/log

# Variables d'env prod (ajuste selon ton besoin)
ENV APP_ENV=prod \
    APP_DEBUG=0

USER symfony
CMD ["php-fpm"]