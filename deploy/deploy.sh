#!/usr/bin/env bash
# Nexio CRM — update an already provisioned server to the latest code.
#
#   sudo bash /var/www/nexio/deploy/deploy.sh
#
# Env overrides: APP_DIR (default /var/www/nexio), BRANCH (main), PHP_VERSION (8.4),
# SKIP_PULL=1 to deploy the working tree as is.
set -euo pipefail

APP_DIR="${APP_DIR:-/var/www/nexio}"
BRANCH="${BRANCH:-main}"
PHP_VERSION="${PHP_VERSION:-8.4}"
PHP="php${PHP_VERSION}"
export COMPOSER_ALLOW_SUPERUSER=1

log() { printf '\n\033[1;34m==> %s\033[0m\n' "$*"; }

[[ $EUID -eq 0 ]] || { echo "Root sifatida ishga tushiring: sudo $0" >&2; exit 1; }
cd "$APP_DIR"

if [[ "${SKIP_PULL:-0}" != "1" ]]; then
    log "Kod yangilanmoqda ($BRANCH)"
    git fetch --prune origin
    git checkout "$BRANCH"
    git pull --ff-only origin "$BRANCH"
fi

log "Composer bog'liqliklari"
cd "$APP_DIR/backend"
composer install --no-dev --optimize-autoloader --no-interaction --no-progress

# Bring the API back up even if a later step fails.
"$PHP" artisan down --retry=15 || true
trap '"$PHP" artisan up || true' EXIT

log "Migratsiyalar"
"$PHP" artisan migrate --force

log "Kesh"
"$PHP" artisan optimize:clear
"$PHP" artisan optimize

chown -R www-data:www-data storage bootstrap/cache
chmod -R ug+rwX storage bootstrap/cache

log "Frontend build"
cd "$APP_DIR"
npm ci --no-audit --no-fund
# Build next to the live bundle and swap, so users never hit an empty dist/.
rm -rf dist.new
VITE_API_BASE_URL= npm run build -- --outDir dist.new --emptyOutDir
rm -rf dist.old
[[ -d dist ]] && mv dist dist.old
mv dist.new dist
rm -rf dist.old

log "Servislar qayta yuklanmoqda"
"$PHP" "$APP_DIR/backend/artisan" up
trap - EXIT
systemctl reload "php${PHP_VERSION}-fpm"
if systemctl is-enabled --quiet nexio-queue 2>/dev/null; then
    "$PHP" "$APP_DIR/backend/artisan" queue:restart
    systemctl restart nexio-queue
fi

log "Tayyor: $(git -C "$APP_DIR" log -1 --format='%h %s')"
