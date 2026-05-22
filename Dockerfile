# syntax=docker/dockerfile:1

FROM composer:2 AS vendor
WORKDIR /app

COPY composer.json composer.lock symfony.lock ./

ENV APP_ENV=prod

ENV DATABASE_URL="mysql://build:build@127.0.0.1:3306/build?serverVersion=8.0.32&charset=utf8mb4"

RUN composer install \
    --no-dev \
    --prefer-dist \
    --no-interaction \
    --no-progress \
    --optimize-autoloader \
    --no-scripts

FROM node:22-alpine AS assets
WORKDIR /app

COPY package.json package-lock.json webpack.config.js ./
COPY assets ./assets

ENV NODE_ENV=production
RUN npm ci && npm run build

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

COPY . .
COPY --from=vendor /app/vendor ./vendor
COPY --from=assets /app/public/build ./public/build

RUN composer dump-autoload --classmap-authoritative --no-dev \
    && mkdir -p var/cache var/log public/uploads/products \
    && chmod +x docker/entrypoint.sh bin/console

ENV APP_ENV=prod
ENV APP_DEBUG=0

EXPOSE 8080

ENTRYPOINT ["/app/docker/entrypoint.sh"]
