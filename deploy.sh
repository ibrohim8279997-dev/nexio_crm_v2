#!/usr/bin/env bash
# Nexio CRM — bitta buyruq bilan serverni yangilash (CloudPanel, sayt foydalanuvchisi nomidan):
#
#   cd ~/htdocs/crmb.nexiolab.uz && ./deploy.sh
#
# git pull qiladi, oxirgi muvaffaqiyatli deploy'dan beri o'zgargan fayllarni ko'rsatadi va
# faqat kerakli qadamlarni bajaradi:
#   backend/composer.*           -> composer install
#   backend/database/, plugins/  -> maintenance rejimi + migrate
#   backend/**                   -> kesh qayta qurish + queue:restart
#   src/, package*.json, vite... -> frontend build + PM2 restart
# Oxirgi muvaffaqiyatli deploy commit'i .last-deploy faylida saqlanadi, shuning uchun yarim
# yo'lda to'xtagan deploy keyingi safar to'liq qaytadan bajariladi.
#
# Env: FORCE=1 (hamma qadamlar), SKIP_PULL=1, SKIP_FRONTEND=1, BRANCH (main), PHP (php),
#      PM2_APP (nexio-crm).
set -euo pipefail

APP_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
BRANCH="${BRANCH:-main}"
PHP="${PHP:-php}"
PM2_APP="${PM2_APP:-nexio-crm}"
STATE_FILE="$APP_DIR/.last-deploy"

log()  { printf '\n\033[1;34m==> %s\033[0m\n' "$*"; }
skip() { printf '\033[0;90m    o'\''tkazildi: %s\033[0m\n' "$*"; }
die()  { printf '\033[1;31m%s\033[0m\n' "$*" >&2; exit 1; }

[[ $EUID -ne 0 ]] || die "Root emas, sayt foydalanuvchisi nomidan ishga tushiring (su - <site-user>)."
cd "$APP_DIR"

# ── 1. Kod ──────────────────────────────────────────────────────────────────────────────
if [[ "${SKIP_PULL:-0}" != "1" && "${DEPLOY_REEXEC:-0}" != "1" ]]; then
    log "Kod yangilanmoqda ($BRANCH)"
    if [[ -n "$(git status --porcelain --untracked-files=no)" ]]; then
        git status --short --untracked-files=no >&2
        die "Serverda commit qilinmagan o'zgarishlar bor — avval tekshiring (git diff)."
    fi
    git fetch --prune origin "$BRANCH"
    git checkout -q "$BRANCH"
    git merge --ff-only "origin/$BRANCH"

    # deploy.sh o'zi yangilangan bo'lsa, yangi versiyasini ishga tushiramiz.
    exec env DEPLOY_REEXEC=1 bash "$APP_DIR/deploy.sh"
fi

NEW_REV="$(git rev-parse HEAD)"
OLD_REV=""
if [[ -f "$STATE_FILE" ]]; then
    OLD_REV="$(tr -d '[:space:]' < "$STATE_FILE")"
    git cat-file -e "${OLD_REV}^{commit}" 2>/dev/null || OLD_REV=""
fi

# ── 2. Nima o'zgardi ────────────────────────────────────────────────────────────────────
FULL=0
[[ "${FORCE:-0}" == "1" || -z "$OLD_REV" ]] && FULL=1

if [[ "$FULL" == "1" ]]; then
    log "To'liq deploy ($([[ -z "$OLD_REV" ]] && echo 'oldingi deploy topilmadi' || echo 'FORCE=1'))"
    CHANGED="$(git ls-files)"
elif [[ "$OLD_REV" == "$NEW_REV" ]]; then
    log "Yangi o'zgarish yo'q ($(git log -1 --format='%h %s')). Hammasini qayta bajarish: FORCE=1 ./deploy.sh"
    exit 0
else
    log "Yangi commitlar"
    git --no-pager log --oneline --no-decorate "$OLD_REV..$NEW_REV"
    log "O'zgargan fayllar"
    git --no-pager diff --stat=120 "$OLD_REV" "$NEW_REV" | tail -n 40
    CHANGED="$(git diff --name-only "$OLD_REV" "$NEW_REV")"
fi

changed() { grep -Eq "$1" <<< "$CHANGED"; }

NEED_COMPOSER=0; NEED_MIGRATE=0; NEED_BACKEND=0; NEED_NPM=0; NEED_FRONTEND=0
{ changed '^backend/composer\.(json|lock)$' || [[ ! -f backend/vendor/autoload.php ]]; } && NEED_COMPOSER=1
changed '^backend/(database/|plugins/[^/]+/(migrations|database)/)' && NEED_MIGRATE=1
changed '^backend/' && NEED_BACKEND=1
{ changed '^package(-lock)?\.json$' || [[ ! -d node_modules ]]; } && NEED_NPM=1
{ changed '^(src/|public/|index\.html$|package(-lock)?\.json$|vite\.config\.|tsconfig)' || [[ ! -d dist ]]; } && NEED_FRONTEND=1
[[ "${SKIP_FRONTEND:-0}" == "1" ]] && NEED_NPM=0 && NEED_FRONTEND=0

# ── 3. Backend ──────────────────────────────────────────────────────────────────────────
cd "$APP_DIR/backend"

log "Backend: composer"
if [[ "$NEED_COMPOSER" == "1" ]]; then
    composer install --no-dev --optimize-autoloader --no-interaction --no-progress
else
    skip "composer.json/lock o'zgarmagan"
fi

log "Backend: migratsiyalar"
if [[ "$NEED_MIGRATE" == "1" ]]; then
    # Migratsiya paytida API'ni to'xtatamiz; xato bo'lsa ham qayta yoqiladi.
    "$PHP" artisan down --retry=15 || true
    trap '"$PHP" "$APP_DIR/backend/artisan" up || true' EXIT
    "$PHP" artisan migrate --force
else
    # Yangi fayl bo'lmasa ham, qolib ketgan migratsiya bo'lsa bajariladi (bo'lmasa — hech narsa qilmaydi).
    "$PHP" artisan migrate --force
fi

log "Backend: kesh"
if [[ "$NEED_BACKEND" == "1" ]]; then
    "$PHP" artisan optimize:clear
    "$PHP" artisan optimize
    "$PHP" artisan queue:restart
else
    skip "backend/ o'zgarmagan"
fi

if [[ "$NEED_MIGRATE" == "1" ]]; then
    "$PHP" artisan up
    trap - EXIT
fi

# ── 4. Frontend ─────────────────────────────────────────────────────────────────────────
cd "$APP_DIR"
log "Frontend"
if [[ "$NEED_FRONTEND" == "1" ]]; then
    if [[ -z "${VITE_API_BASE_URL+x}" && ! -f .env.production.local ]]; then
        echo "VITE_API_BASE_URL berilmagan va .env.production.local yo'q." >&2
        die "Masalan: echo 'VITE_API_BASE_URL=https://crmb.nexiolab.uz' > .env.production.local"
    fi
    if [[ "$NEED_NPM" == "1" ]]; then
        npm ci --no-audit --no-fund --loglevel=error
    else
        skip "npm ci (package-lock.json o'zgarmagan)"
    fi
    rm -rf dist.new
    npm run build -- --outDir dist.new --emptyOutDir
    # Faqat muvaffaqiyatli build'dan keyin almashtiramiz; eskisi dist.bak'da qoladi.
    rm -rf dist.bak
    [[ -d dist ]] && mv dist dist.bak
    mv dist.new dist
    if command -v pm2 >/dev/null && pm2 describe "$PM2_APP" >/dev/null 2>&1; then
        pm2 restart "$PM2_APP" --update-env
        pm2 save
    else
        echo "PM2 jarayoni '$PM2_APP' topilmadi — frontend'ni beradigan jarayonni qo'lda qayta ishga tushiring." >&2
    fi
else
    skip "frontend fayllari o'zgarmagan"
fi

echo "$NEW_REV" > "$STATE_FILE"
log "Tayyor: $(git log -1 --format='%h %s')"
[[ -d dist.bak ]] && echo "Frontend'ni orqaga qaytarish: rm -rf dist && mv dist.bak dist && pm2 restart $PM2_APP"
exit 0
