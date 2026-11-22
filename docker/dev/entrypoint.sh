#!/usr/bin/env bash
set -euo pipefail

cd /var/www

if [ ! -f .env ]; then
    cp .env.example .env
    php artisan key:generate --ansi
fi

until php -r "new PDO('pgsql:host='.getenv('DB_HOST').';port='.getenv('DB_PORT').';dbname='.getenv('DB_DATABASE'), getenv('DB_USERNAME'), getenv('DB_PASSWORD'));" >/dev/null 2>&1; do
    echo "Waiting for Postgres..."
    sleep 2
done

if [ "${SKIP_MIGRATE:-0}" != "1" ]; then
    php artisan migrate --force
fi
php artisan config:clear

exec "$@"
