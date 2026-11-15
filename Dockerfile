# PHP version: always the latest stable branch (as of 2026-11-14: 8.5 -> 8.5.12).
# Pinned to the minor version, not the patch, so every build pulls the latest patch.
ARG PHP_VERSION=8.5

# --- Base: PHP runtime with all extensions (also used for local development) ---
FROM serversideup/php:${PHP_VERSION}-fpm-nginx-alpine AS base
USER root
RUN install-php-extensions intl gd zip bcmath pdo_pgsql pdo_mysql
USER www-data
WORKDIR /var/www/html

# --- Stage 1: Composer (same image and extensions as the runtime) ---
# Built on `base` instead of the plain cli image: Filament needs ext-intl,
# which the stock serversideup image does not ship.
FROM base AS vendor
USER root
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --no-interaction
COPY . .
RUN composer dump-autoload --optimize --no-dev \
 && php artisan filament:assets

# --- Stage 2: frontend assets (Vite) ---
# The Filament theme imports CSS from vendor/filament, so vendor comes from stage 1.
FROM node:22-alpine AS assets
WORKDIR /app
COPY package*.json ./
RUN npm ci
COPY . .
COPY --from=vendor /app/vendor ./vendor
RUN npm run build

# --- Stage 3: runtime ---
FROM base
# Defaults for the home-network setup: SQLite in the data volume, no queue
# worker, logs to the container output. All can be overridden in the compose file.
ENV AUTORUN_ENABLED=true \
    AUTORUN_LARAVEL_MIGRATION=true \
    PHP_OPCACHE_ENABLE=1 \
    APP_ENV=production \
    APP_DEBUG=false \
    LOG_CHANNEL=stderr \
    DB_CONNECTION=sqlite \
    DB_DATABASE=/var/www/html/storage/app/data/holdout.sqlite \
    SESSION_DRIVER=database \
    CACHE_STORE=database \
    QUEUE_CONNECTION=sync
USER root
COPY --chmod=755 docker/entrypoint.d/ /etc/entrypoint.d/
USER www-data
COPY --chown=www-data:www-data --from=vendor /app /var/www/html
COPY --chown=www-data:www-data --from=assets /app/public/build /var/www/html/public/build
RUN mkdir -p /var/www/html/storage/app/data
