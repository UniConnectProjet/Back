FROM composer:2 AS vendor
WORKDIR /app

ENV COMPOSER_ALLOW_SUPERUSER=1 \
    COMPOSER_MEMORY_LIMIT=-1

COPY composer.json composer.lock ./

RUN composer install --no-dev --prefer-dist --no-interaction --no-progress --no-scripts

COPY . .

RUN composer dump-autoload --optimize --classmap-authoritative --no-interaction


FROM php:8.3-fpm

RUN apt-get update && apt-get install -y --no-install-recommends \
      git unzip libzip-dev zlib1g-dev libicu-dev \
  && docker-php-ext-install -j"$(nproc)" zip pdo_mysql opcache \
  && docker-php-ext-install intl \
  && rm -rf /var/lib/apt/lists/*

RUN useradd -m -u 1000 symfony

WORKDIR /var/www/html

COPY --from=vendor --chown=symfony:symfony /app ./

RUN mkdir -p var/cache var/log \
 && chown -R symfony:symfony var \
 && chmod -R 775 var

# Env prod
ENV APP_ENV=prod \
    APP_DEBUG=0

USER symfony

EXPOSE 9000

CMD ["php-fpm"]
