# syntax=docker/dockerfile:1.7
FROM php:8.3-fpm-alpine AS base

RUN apk add --no-cache \
        bash curl git unzip postgresql-client icu-dev libzip-dev libpng-dev libjpeg-turbo-dev freetype-dev \
        $PHPIZE_DEPS \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" pdo_pgsql intl gd zip bcmath opcache pcntl \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apk del $PHPIZE_DEPS

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
WORKDIR /var/www

FROM base AS build
COPY package.json package-lock.json ./
RUN npm ci
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --prefer-dist --no-scripts --optimize-autoloader
COPY . .
RUN npm run build \
    && composer dump-autoload -o --no-dev \
    && php artisan config:cache && php artisan route:cache && php artisan view:cache && php artisan event:cache

FROM base AS runtime
COPY --from=build --chown=www-data:www-data /var/www /var/www
COPY docker/prod/php.ini /usr/local/etc/php/conf.d/99-ticketing.ini
RUN mkdir -p /var/www/storage/framework/{cache,sessions,views} /var/www/bootstrap/cache \
    && chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache
USER www-data
EXPOSE 9000
HEALTHCHECK --interval=30s --timeout=5s --retries=3 \
    CMD php artisan --version >/dev/null 2>&1 || exit 1
CMD ["php-fpm"]
