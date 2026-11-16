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
 && php artisan filament:assets \
 && chmod -R u+rwX,go+rX,go-w /app
# Readable (not writable) for any user, so the app also works when the start script
# switches www-data to the host's PUID/PGID. The image contains no secrets.

# --- Stage 2: frontend assets (Vite) ---
# The Filament theme imports CSS from vendor/filament, so vendor comes from stage 1.
FROM node:22-alpine AS assets
WORKDIR /app
COPY package*.json ./
RUN npm ci
COPY . .
COPY --from=vendor /app/vendor ./vendor
RUN npm run build \
 && chmod -R u+rwX,go+rX,go-w public/build

# --- Stage 3: runtime ---
FROM base
# Everything environment-specific (APP_KEY, database, mail) comes from Coolify.
ENV AUTORUN_ENABLED=true \
    AUTORUN_LARAVEL_MIGRATION=true \
    AUTORUN_LARAVEL_MIGRATION_SEED=true \
    PHP_OPCACHE_ENABLE=1
COPY --chown=www-data:www-data --from=vendor /app /var/www/html
COPY --chown=www-data:www-data --from=assets /app/public/build /var/www/html/public/build
# COPY creates public/build itself, without the stage's permissions. storage/app/data
# holds product photos (on the app-storage volume).
RUN mkdir -p /var/www/html/storage/app/data \
 && chmod 755 /var/www/html/public/build
