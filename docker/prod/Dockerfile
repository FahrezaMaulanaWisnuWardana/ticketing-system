# syntax=docker/dockerfile:1.7
FROM php:8.3-fpm-alpine AS base

RUN apk add --no-cache \
        bash su-exec curl git unzip postgresql-client postgresql-dev icu-dev libzip-dev libpng-dev libjpeg-turbo-dev freetype-dev \
        $PHPIZE_DEPS \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" pdo_pgsql intl gd zip bcmath opcache pcntl \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apk del $PHPIZE_DEPS

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
WORKDIR /var/www

FROM node:22-alpine AS assets
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci --no-audit --no-fund
COPY resources ./resources
COPY vite.config.js postcss.config.js tailwind.config.js ./
RUN npm run build

FROM base AS app-build
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --prefer-dist --no-progress --no-scripts --optimize-autoloader
COPY . .
COPY --from=assets /app/public/build ./public/build
RUN composer dump-autoload -o --no-dev \
    && php artisan package:discover --ansi \
    && php artisan route:cache \
    && php artisan view:cache \
    && php artisan event:cache
# config:cache is intentionally deferred to deploy time, when the real env
# variables are available; caching at build time would bake in empty secrets.

FROM base AS runtime
COPY --from=app-build --chown=www-data:www-data /var/www /var/www
COPY docker/prod/php.ini /usr/local/etc/php/conf.d/99-ticketing.ini
COPY docker/prod/www.conf /usr/local/etc/php-fpm.d/zz-ticketing.conf
COPY docker/prod/entrypoint.sh /usr/local/bin/entrypoint
RUN mkdir -p /var/www/storage/framework/{cache,sessions,views} /var/www/bootstrap/cache \
    && chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache \
    && chmod +x /usr/local/bin/entrypoint
EXPOSE 9000
HEALTHCHECK --interval=30s --timeout=5s --retries=3 \
    CMD php artisan --version >/dev/null 2>&1 || exit 1
ENTRYPOINT ["/usr/local/bin/entrypoint"]
CMD ["php-fpm"]
