#!/usr/bin/env bash
# Nexio CRM — BOT serverini yangilash (sayt serveridan alohida). Sayt uchun: deploy.sh.
#
#   cd ~/nexio-bot && ./deploybot.sh
#
# git pull qiladi, kerakli qadamlarni bajaradi va Telegram tinglovchisini (nexio:telegram:poll)
# qayta ishga tushiradi. Frontend (npm/PM2) YO'Q: bu serverda faqat backend/ kerak.
# Bot serveri o'z bazasiga ega: sayt bazasining nusxasi (BOTS_ROLE=bot), sayt o'zgarganda o'zi yangilanadi.
#
# Birinchi marta: backend/.env ni to'ldiring (docs/OPERATIONS.md, "Telegram bot (separate server)"),
# keyin ./deploybot.sh — migratsiya + to'liq sinxronizatsiyani o'zi bajaradi.
#
# Env: FORCE=1 (hamma qadamlar), SKIP_PULL=1, BRANCH (main), PHP (php), ALLOW_ROOT=1,
#      BOT_RESTART_CMD (masalan "systemctl restart nexio-telegram"; bo'sh bo'lsa supervisor:
#      "supervisorctl restart $BOT_SERVICE"), BOT_SERVICE (nexio-telegram), SKIP_SYNC=1.
set -euo pipefail

APP_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
BRANCH="${BRANCH:-main}"
PHP="${PHP:-php}"
BOT_SERVICE="${BOT_SERVICE:-nexio-telegram}"
STATE_FILE="$APP_DIR/.last-deploy-bot"

log()  { printf '\n\033[1;34m==> %s\033[0m\n' "$*"; }
skip() { printf '\033[0;90m    o'\''tkazildi: %s\033[0m\n' "$*"; }
die()  { printf '\033[1;31m%s\033[0m\n' "$*" >&2; exit 1; }

[[ "${ALLOW_ROOT:-0}" == "1" || $EUID -ne 0 ]] || die "Root emas, oddiy foydalanuvchi nomidan ishga tushiring (yoki ALLOW_ROOT=1)."
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
    exec env DEPLOY_REEXEC=1 bash "$APP_DIR/deploybot.sh"
fi

NEW_REV="$(git rev-parse HEAD)"
OLD_REV=""
if [[ -f "$STATE_FILE" ]]; then
    OLD_REV="$(tr -d '[:space:]' < "$STATE_FILE")"
    git cat-file -e "${OLD_REV}^{commit}" 2>/dev/null || OLD_REV=""
fi
FULL=0
[[ "${FORCE:-0}" == "1" || -z "$OLD_REV" ]] && FULL=1
if [[ "$FULL" == "0" && "$OLD_REV" == "$NEW_REV" ]]; then
    log "Yangi o'zgarish yo'q ($(git log -1 --format='%h %s')). Hammasini qayta bajarish: FORCE=1 ./deploybot.sh"
    exit 0
fi
if [[ "$FULL" == "1" ]]; then CHANGED="$(git ls-files)"; else CHANGED="$(git diff --name-only "$OLD_REV" "$NEW_REV")"; fi
changed() { grep -Eq "$1" <<< "$CHANGED"; }

cd "$APP_DIR/backend"

# ── 2. Sozlamalar tekshiruvi ────────────────────────────────────────────────────────────
[[ -f .env ]] || die "backend/.env yo'q. .env.example dan nusxa oling va BOTS_*, TELEGRAM_*, DB_* ni to'ldiring."
env_value() { grep -E "^$1=" .env | tail -n1 | cut -d= -f2- | tr -d "\"' " || true; }
[[ "$(env_value BOTS_ROLE)" == "bot" ]] || die ".env: BOTS_ROLE=bot bo'lishi kerak (bu bot serveri)."
for key in BOTS_SECRET BOTS_SITE_URL TELEGRAM_BOT_TOKEN APP_KEY; do
    [[ -n "$(env_value "$key")" ]] || die ".env: $key to'ldirilmagan."
done

# ── 3. Backend ──────────────────────────────────────────────────────────────────────────
log "Backend: composer"
if { changed '^backend/composer\.(json|lock)$' || [[ ! -f vendor/autoload.php ]]; }; then
    composer install --no-dev --optimize-autoloader --no-interaction --no-progress
else
    skip "composer.json/lock o'zgarmagan"
fi

log "Backend: migratsiyalar (bot bazasi sxemasi saytnikiga teng bo'lishi shart)"
"$PHP" artisan migrate --force

log "Backend: kesh"
"$PHP" artisan optimize:clear
"$PHP" artisan optimize

# ── 4. Ma'lumotlar nusxasi ──────────────────────────────────────────────────────────────
if [[ "${SKIP_SYNC:-0}" == "1" ]]; then
    skip "SKIP_SYNC=1"
elif [[ "$FULL" == "1" ]]; then
    log "Sayt bazasi bilan to'liq sinxronizatsiya"
    "$PHP" artisan nexio:bots:sync
else
    log "Sayt bazasi bilan farqlar"
    "$PHP" artisan nexio:bots:sync --reconcile
fi

# ── 5. Tinglovchi ───────────────────────────────────────────────────────────────────────
log "Telegram tinglovchisi qayta ishga tushirilmoqda"
if [[ -n "${BOT_RESTART_CMD:-}" ]]; then
    bash -c "$BOT_RESTART_CMD"
elif command -v supervisorctl >/dev/null 2>&1; then
    supervisorctl restart "$BOT_SERVICE" || echo "supervisor'da '$BOT_SERVICE' topilmadi — docs/OPERATIONS.md dagi konfigni qo'shing." >&2
else
    echo "Tinglovchini qo'lda qayta ishga tushiring (php artisan nexio:telegram:poll) yoki BOT_RESTART_CMD bering." >&2
fi

printf '%s\n' "$NEW_REV" > "$STATE_FILE"
log "Tayyor: $(git log -1 --format='%h %s')"
