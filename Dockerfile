# syntax=docker/dockerfile:1

# ==========================================
# Composer Vendor Stage
# ==========================================
FROM composer:2 AS vendor

WORKDIR /app

COPY composer.json composer.lock symfony.lock ./

RUN composer install \
    --no-dev \
    --prefer-dist \
    --no-interaction \
    --no-progress \
    --optimize-autoloader \
    --no-scripts


# ==========================================
# Assets Build Stage
# ==========================================
FROM node:22-alpine AS assets

WORKDIR /app

COPY package.json package-lock.json webpack.config.js ./
COPY assets ./assets

RUN npm ci
RUN npm run build


# ==========================================
# Runtime Stage
# ==========================================
FROM php:8.2-cli-alpine AS runtime

RUN apk add --no-cache \
    icu-dev \
    libzip-dev \
    oniguruma-dev \
    && docker-php-ext-configure intl \
    && docker-php-ext-install -j"$(nproc)" \
        intl \
        opcache \
        pdo_mysql \
        zip

WORKDIR /app

# Add Composer to runtime because we use composer dump-autoload below
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

COPY . .
COPY --from=vendor /app/vendor ./vendor
COPY --from=assets /app/public/build ./public/build
RUN printf '%s\n' \
    'APP_ENV=prod' \
    'APP_DEBUG=0' \
    'APP_SECRET=' \
    'DATABASE_URL=' \
    'JWT_PASSPHRASE=' \
    'JWT_SECRET_KEY=' \
    'JWT_PUBLIC_KEY=' \
    > .env

RUN composer dump-autoload --classmap-authoritative --no-dev \
    && mkdir -p var/cache var/log public/uploads/products config/jwt \
    && chmod +x docker/entrypoint.sh bin/console

ENV PORT=8080

EXPOSE 8080

ENTRYPOINT ["/app/docker/entrypoint.sh"]