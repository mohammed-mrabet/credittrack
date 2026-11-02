# --- API Symfony 7 (PHP 8.3) ---
FROM php:8.3-cli-alpine

# Extension PostgreSQL pour Doctrine
RUN apk add --no-cache libpq \
 && apk add --no-cache --virtual .build-deps postgresql-dev \
 && docker-php-ext-install pdo_pgsql \
 && apk del .build-deps

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

# Les dépendances d'abord : cette couche reste en cache tant que composer.json ne change pas.
COPY composer.json composer.lock* ./
RUN composer install --no-dev --no-scripts --no-interaction --prefer-dist --no-progress

COPY . .
RUN composer dump-autoload --optimize --no-dev \
 && mkdir -p var && chown -R www-data:www-data var

ENV APP_ENV=prod \
    APP_DEBUG=0

USER www-data
EXPOSE 8000

CMD ["sh", "docker/entrypoint.sh"]
