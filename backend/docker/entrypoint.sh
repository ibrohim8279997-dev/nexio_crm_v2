#!/bin/sh
set -e
cd /var/www/html
# Cache config/routes for production, then migrate (idempotent). RUN_MIGRATIONS=0 skips it.
php artisan config:cache
php artisan route:cache
if [ "${RUN_MIGRATIONS:-1}" = "1" ]; then php artisan migrate --force; fi
php artisan nexio:telegram:webhook --ensure || true
exec "$@"
