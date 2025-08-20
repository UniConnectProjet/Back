# ---- Construction base PHP ----
FROM php:8.2-fpm-alpine AS base

RUN apk add --no-cache \
    bash git unzip icu-dev oniguruma-dev libzip-dev \
    && docker-php-ext-install intl opcache pdo pdo_mysql zip

# Installer Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Créer utilisateur symfony
RUN addgroup -g 1000 symfony && adduser -G symfony -g symfony -s /bin/sh -D symfony

WORKDIR /var/www/html

# ---- Builder ----
FROM base AS build

COPY --chown=symfony:symfony . .

RUN composer install --no-dev --optimize-autoloader --no-interaction --no-progress

# ---- Runtime ----
FROM base AS runtime

WORKDIR /var/www/html

COPY --from=build /var/www/html /var/www/html

# Préparer dossier JWT (les clés seront montées au runtime)
RUN mkdir -p /etc/jwt && chown symfony:symfony /etc/jwt

# Droits pour symfony
RUN chown -R symfony:symfony /var/www/html

USER symfony

CMD ["php-fpm"]
