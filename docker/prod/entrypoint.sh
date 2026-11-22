#!/usr/bin/env bash
set -euo pipefail

# Runtime volume mounts may be owned by root. Fix ownership before dropping
# privileges, then execute the container command as www-data.
mkdir -p /var/www/storage/framework/{cache,sessions,views} /var/www/bootstrap/cache
chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache

exec su-exec www-data "$@"
