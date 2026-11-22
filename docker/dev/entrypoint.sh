#!/usr/bin/env bash
set -euo pipefail

cd /var/www

if [ ! -f .env ]; then
    cp .env.example .env
fi
if ! grep -qE '^APP_KEY=.+' .env; then
    php artisan key:generate --ansi
fi
# Export the key so this container process uses the freshly generated value
# even though Dotenv will not overwrite an existing environment variable.
export APP_KEY="$(grep '^APP_KEY=' .env | head -n 1 | cut -d= -f2-)"

until php -r "new PDO('pgsql:host='.getenv('DB_HOST').';port='.getenv('DB_PORT').';dbname='.getenv('DB_DATABASE'), getenv('DB_USERNAME'), getenv('DB_PASSWORD'));" >/dev/null 2>&1; do
    echo "Waiting for Postgres..."
    sleep 2
done

if [ "${SKIP_MIGRATE:-0}" != "1" ]; then
    php artisan migrate --force
fi
php artisan config:clear

exec "$@"
