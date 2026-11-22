FROM php:8.3-fpm-alpine

RUN apk add --no-cache \
        bash git curl unzip postgresql-client postgresql-dev icu-dev libzip-dev libpng-dev libjpeg-turbo-dev freetype-dev \
        $PHPIZE_DEPS \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" pdo_pgsql intl gd zip bcmath opcache pcntl \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apk del $PHPIZE_DEPS

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www
COPY composer.json composer.lock ./
RUN composer install --no-interaction --prefer-dist --no-progress --no-scripts

COPY . .
RUN php artisan package:discover --ansi

COPY docker/dev/php.ini /usr/local/etc/php/conf.d/99-ticketing.ini
COPY docker/dev/entrypoint.sh /usr/local/bin/entrypoint
RUN chmod +x /usr/local/bin/entrypoint

USER www-data
EXPOSE 9000
ENTRYPOINT ["/usr/local/bin/entrypoint"]
CMD ["php-fpm"]
