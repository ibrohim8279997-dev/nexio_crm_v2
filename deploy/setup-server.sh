#!/usr/bin/env bash
# Nexio CRM — one-time provisioning of a fresh Ubuntu 22.04/24.04 server.
#
#   curl -fsSLO <raw url of this file>   # or scp it to the server
#   sudo DOMAIN=crm.example.uz \
#        REPO_URL=https://github.com/<owner>/nexio_crm_v2.git \
#        ADMIN_EMAIL=admin@example.uz \
#        CERTBOT_EMAIL=admin@example.uz \
#        bash setup-server.sh
#
# Required: DOMAIN, REPO_URL.
# Optional: BRANCH (main), APP_DIR (/var/www/nexio), PHP_VERSION (8.4),
#           DB_NAME (nexio_crm), DB_USER (nexio), DB_PASS (generated),
#           ADMIN_EMAIL (creates the platform Super Admin, asks for a password),
#           CERTBOT_EMAIL (enables HTTPS via Let's Encrypt; DNS must already point here).
#
# Safe to re-run: existing database, .env and secrets are kept. Later updates: deploy.sh.
set -euo pipefail

: "${DOMAIN:?DOMAIN kerak, masalan DOMAIN=crm.example.uz}"
: "${REPO_URL:?REPO_URL kerak (git repozitoriya manzili)}"
BRANCH="${BRANCH:-main}"
APP_DIR="${APP_DIR:-/var/www/nexio}"
PHP_VERSION="${PHP_VERSION:-8.4}"
PHP="php${PHP_VERSION}"
DB_NAME="${DB_NAME:-nexio_crm}"
DB_USER="${DB_USER:-nexio}"
DB_PASS="${DB_PASS:-}"
ADMIN_EMAIL="${ADMIN_EMAIL:-}"
CERTBOT_EMAIL="${CERTBOT_EMAIL:-}"
export DEBIAN_FRONTEND=noninteractive COMPOSER_ALLOW_SUPERUSER=1

log() { printf '\n\033[1;34m==> %s\033[0m\n' "$*"; }
[[ $EUID -eq 0 ]] || { echo "Root sifatida ishga tushiring: sudo bash $0" >&2; exit 1; }

# ── 1. Packages ──────────────────────────────────────────────────────────────────────
log "Paketlar o'rnatilmoqda"
apt-get update -y
apt-get install -y software-properties-common ca-certificates curl git unzip gnupg \
    nginx postgresql ufw
if ! apt-cache policy | grep -q ondrej/php; then
    add-apt-repository -y ppa:ondrej/php
    apt-get update -y
fi
apt-get install -y "$PHP-fpm" "$PHP-cli" "$PHP-pgsql" "$PHP-sqlite3" "$PHP-mbstring" \
    "$PHP-xml" "$PHP-curl" "$PHP-zip" "$PHP-bcmath" "$PHP-intl" "$PHP-opcache"

if ! command -v composer >/dev/null; then
    curl -fsSL https://getcomposer.org/installer | "$PHP" -- --install-dir=/usr/local/bin --filename=composer
fi
if ! command -v node >/dev/null || [[ "$(node -v | cut -d. -f1 | tr -d v)" -lt 20 ]]; then
    curl -fsSL https://deb.nodesource.com/setup_20.x | bash -
    apt-get install -y nodejs
fi

# ── 2. Firewall ──────────────────────────────────────────────────────────────────────
log "Firewall"
ufw allow OpenSSH
ufw allow 'Nginx Full'
ufw --force enable

# ── 3. PostgreSQL ────────────────────────────────────────────────────────────────────
log "PostgreSQL: $DB_NAME / $DB_USER"
systemctl enable --now postgresql
if ! sudo -u postgres psql -tAc "SELECT 1 FROM pg_roles WHERE rolname='$DB_USER'" | grep -q 1; then
    [[ -n "$DB_PASS" ]] || DB_PASS="$(openssl rand -hex 24)"
    sudo -u postgres psql -c "CREATE USER \"$DB_USER\" WITH PASSWORD '$DB_PASS';"
    NEW_DB_PASS=1
fi
if ! sudo -u postgres psql -tAc "SELECT 1 FROM pg_database WHERE datname='$DB_NAME'" | grep -q 1; then
    sudo -u postgres psql -c "CREATE DATABASE \"$DB_NAME\" OWNER \"$DB_USER\";"
fi

# ── 4. Code ──────────────────────────────────────────────────────────────────────────
log "Kod: $APP_DIR"
if [[ ! -d "$APP_DIR/.git" ]]; then
    mkdir -p "$(dirname "$APP_DIR")"
    git clone --branch "$BRANCH" "$REPO_URL" "$APP_DIR"
fi
git config --global --add safe.directory "$APP_DIR"

# ── 5. backend/.env ──────────────────────────────────────────────────────────────────
ENV_FILE="$APP_DIR/backend/.env"
set_env() {  # set_env KEY VALUE — replace or append, value written verbatim
    local key="$1" value="$2"
    if grep -qE "^${key}=" "$ENV_FILE"; then
        KEY="$key" VALUE="$value" perl -pi -e 's/^\Q$ENV{KEY}\E=.*/$ENV{KEY}=$ENV{VALUE}/' "$ENV_FILE"
    else
        printf '%s=%s\n' "$key" "$value" >> "$ENV_FILE"
    fi
}

FIRST_INSTALL=0
if [[ ! -f "$ENV_FILE" ]]; then
    log ".env yaratilmoqda"
    FIRST_INSTALL=1
    if [[ -z "$DB_PASS" ]]; then
        echo "DB foydalanuvchisi '$DB_USER' allaqachon bor — DB_PASS=... bilan parolini bering." >&2
        exit 1
    fi
    cp "$APP_DIR/backend/.env.example" "$ENV_FILE"
    set_env APP_NAME '"Nexio CRM"'
    set_env APP_ENV production
    set_env APP_DEBUG false
    set_env LOG_LEVEL warning
    set_env APP_URL "https://$DOMAIN"
    set_env FRONTEND_URL "https://$DOMAIN"
    set_env SEED_DEMO_DATA false
    set_env DB_CONNECTION pgsql
    set_env DB_HOST 127.0.0.1
    set_env DB_PORT 5432
    set_env DB_DATABASE "$DB_NAME"
    set_env DB_USERNAME "$DB_USER"
    set_env DB_PASSWORD "$DB_PASS"
    set_env CLICK_RETURN_URL "https://$DOMAIN/app/billing"
    set_env TELEGRAM_WEBHOOK_URL "https://$DOMAIN/api/webhooks/telegram"
    set_env PLUGIN_SECRETS_KEY "base64:$(openssl rand -base64 32)"
fi
chown root:www-data "$ENV_FILE"
chmod 640 "$ENV_FILE"

# ── 6. Nginx + queue service (installed before the first deploy) ─────────────────────
log "Nginx"
sed -e "s#__DOMAIN__#$DOMAIN#g" -e "s#__APP_DIR__#$APP_DIR#g" -e "s#__PHP_VERSION__#$PHP_VERSION#g" \
    "$APP_DIR/deploy/nginx.conf.template" > /etc/nginx/sites-available/nexio
ln -sf /etc/nginx/sites-available/nexio /etc/nginx/sites-enabled/nexio
rm -f /etc/nginx/sites-enabled/default

sed -e "s#__APP_DIR__#$APP_DIR#g" -e "s#__PHP_VERSION__#$PHP_VERSION#g" \
    "$APP_DIR/deploy/nexio-queue.service.template" > /etc/systemd/system/nexio-queue.service
systemctl daemon-reload

# ── 7. First build + migrations ──────────────────────────────────────────────────────
log "Birinchi deploy"
cd "$APP_DIR/backend"
composer install --no-dev --optimize-autoloader --no-interaction --no-progress
grep -qE '^APP_KEY=.+' "$ENV_FILE" || "$PHP" artisan key:generate --force
chmod +x "$APP_DIR/deploy/"*.sh
APP_DIR="$APP_DIR" BRANCH="$BRANCH" PHP_VERSION="$PHP_VERSION" SKIP_PULL=1 "$APP_DIR/deploy/deploy.sh"

if [[ "$FIRST_INSTALL" == "1" ]]; then
    log "Boshlang'ich ma'lumotlar (plaginlar katalogi)"
    "$PHP" artisan db:seed --force
fi

nginx -t
systemctl reload nginx

# ── 8. Scheduler + queue ─────────────────────────────────────────────────────────────
log "Cron va queue worker"
echo "* * * * * www-data cd $APP_DIR/backend && $PHP artisan schedule:run >> /dev/null 2>&1" \
    > /etc/cron.d/nexio
chmod 644 /etc/cron.d/nexio
systemctl enable --now nexio-queue

# ── 9. HTTPS ─────────────────────────────────────────────────────────────────────────
if [[ -n "$CERTBOT_EMAIL" ]]; then
    log "SSL sertifikat (Let's Encrypt)"
    apt-get install -y certbot python3-certbot-nginx
    certbot --nginx --non-interactive --agree-tos --redirect -m "$CERTBOT_EMAIL" -d "$DOMAIN"
else
    echo "CERTBOT_EMAIL berilmadi — HTTPS o'rnatilmadi. Keyinroq: certbot --nginx -d $DOMAIN"
fi

# ── 10. Super Admin ──────────────────────────────────────────────────────────────────
if [[ -n "$ADMIN_EMAIL" ]]; then
    log "Super Admin: $ADMIN_EMAIL"
    "$PHP" artisan nexio:admin:create "$ADMIN_EMAIL" --name="Admin"
fi

log "Tayyor!"
echo "  Sayt:       https://$DOMAIN"
echo "  Tekshiruv:  curl https://$DOMAIN/api/health"
echo "  .env:       $ENV_FILE  (Click, Telegram, SMTP, GROQ_API_KEY ni to'ldiring,"
echo "              keyin: cd $APP_DIR/backend && $PHP artisan optimize)"
echo "  Yangilash:  sudo bash $APP_DIR/deploy/deploy.sh"
if [[ "${NEW_DB_PASS:-0}" == "1" ]]; then
    echo "  DB paroli .env ichida saqlandi (DB_PASSWORD)."
fi
echo "  MUHIM: backend/.env dagi PLUGIN_SECRETS_KEY va APP_KEY ni xavfsiz joyda zaxiralang."
