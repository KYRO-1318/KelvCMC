# Build frontend assets with the same Node toolchain used by the project.
FROM node:22-alpine AS frontend
WORKDIR /var/www/html
COPY package*.json ./
RUN npm ci
COPY . .
RUN npm run build

# Run Laravel with PHP 8.4 and MariaDB support.
FROM php:8.4-cli-alpine
WORKDIR /var/www/html

RUN apk add --no-cache \
        icu-dev \
        libxml2-dev \
        libzip-dev \
        oniguruma-dev \
        sqlite-dev \
        unzip \
    && docker-php-ext-install bcmath intl mbstring pdo_mysql pdo_sqlite xml zip

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

COPY composer.json ./
RUN composer install \
        --no-dev \
        --prefer-dist \
        --no-interaction \
        --no-progress \
        --no-scripts

COPY . .
COPY --from=frontend /var/www/html/public/build ./public/build
COPY docker/entrypoint.sh /usr/local/bin/kelvcmc-entrypoint
RUN chmod +x /usr/local/bin/kelvcmc-entrypoint \
    && mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs storage/app/private storage/app/public bootstrap/cache \
    && composer dump-autoload --optimize --no-interaction

ENV APP_ENV=production
ENV APP_DEBUG=false
EXPOSE 8000

ENTRYPOINT ["kelvcmc-entrypoint"]
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]
