#!/usr/bin/env bash
# Nexio CRM — update a CloudPanel site (code in ~/htdocs/<domain>, SPA served from frontend/).
# Run as the site user, never as root:
#
#   bash ~/htdocs/crmb.nexiolab.uz/deploy/deploy-cloudpanel.sh
#
# The API origin baked into the SPA comes from VITE_API_BASE_URL, taken from the
# environment or from .env.production.local in the repository root.
# Env overrides: BRANCH (main), PHP (php), SKIP_PULL=1, SKIP_FRONTEND=1.
set -euo pipefail

APP_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
BRANCH="${BRANCH:-main}"
PHP="${PHP:-php}"

log() { printf '\n\033[1;34m==> %s\033[0m\n' "$*"; }

[[ $EUID -ne 0 ]] || { echo "Root emas, sayt foydalanuvchisi nomidan ishga tushiring (su - <site-user>)." >&2; exit 1; }
cd "$APP_DIR"

if [[ "${SKIP_PULL:-0}" != "1" ]]; then
    log "Kod yangilanmoqda ($BRANCH)"
    if [[ -n "$(git status --porcelain --untracked-files=no)" ]]; then
        echo "Serverda commit qilinmagan o'zgarishlar bor — avval tekshiring:" >&2
        git status --short --untracked-files=no >&2
        exit 1
    fi
    git pull --ff-only origin "$BRANCH"
fi

log "Backend: composer"
cd "$APP_DIR/backend"
composer install --no-dev --optimize-autoloader --no-interaction --no-progress

# Bring the API back up even if a later step fails.
"$PHP" artisan down --retry=15 || true
trap '"$PHP" "$APP_DIR/backend/artisan" up || true' EXIT

log "Backend: migratsiyalar"
"$PHP" artisan migrate --force

log "Backend: kesh"
"$PHP" artisan optimize:clear
"$PHP" artisan optimize
"$PHP" artisan queue:restart

"$PHP" artisan up
trap - EXIT

if [[ "${SKIP_FRONTEND:-0}" != "1" ]]; then
    log "Frontend build"
    cd "$APP_DIR"
    if [[ -z "${VITE_API_BASE_URL+x}" && ! -f .env.production.local ]]; then
        echo "VITE_API_BASE_URL berilmagan va .env.production.local yo'q." >&2
        echo "Masalan: echo 'VITE_API_BASE_URL=https://crmb.nexiolab.uz' > .env.production.local" >&2
        exit 1
    fi
    npm ci --no-audit --no-fund --loglevel=error
    rm -rf dist.new
    npm run build -- --outDir dist.new --emptyOutDir
    # Swap only after a successful build; keep the previous bundle for rollback.
    rm -rf frontend.bak
    [[ -d frontend ]] && mv frontend frontend.bak
    mv dist.new frontend
fi

log "Tayyor: $(git -C "$APP_DIR" log -1 --format='%h %s')"
echo "Frontend'ni orqaga qaytarish: cd $APP_DIR && rm -rf frontend && mv frontend.bak frontend"
