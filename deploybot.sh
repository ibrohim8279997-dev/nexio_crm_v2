#!/usr/bin/env bash
# Nexio CRM — Telegram botni ALOHIDA ishga tushirish/qayta ishga tushirish (sayt bilan bir serverda, bir xil baza).
#
#   cd ~/htdocs/crmb.nexiolab.uz && ./deploybot.sh
#
# deploy.sh saytni yangilaydi va botga tegmaydi; bu skript esa faqat bot jarayonini boshqaradi
# (php artisan nexio:telegram:poll, PM2 ostida). Kod yangilanishi uchun avval ./deploy.sh.
# Bot sayt bilan BIR XIL bazani ishlatadi; failover bo'lsa, qayta ishga tushirish botni ham yangi bazaga o'tkazadi.
#
# Env: PHP (php), BOT_APP (nexio-bot, PM2 nomi), BOT_RESTART_CMD (PM2 o'rniga o'z buyrug'ingiz,
#      masalan "supervisorctl restart nexio-telegram"), STOP=1 (botni to'xtatish).
set -euo pipefail

APP_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PHP="${PHP:-php}"
BOT_APP="${BOT_APP:-nexio-bot}"

log() { printf '\n\033[1;34m==> %s\033[0m\n' "$*"; }
die() { printf '\033[1;31m%s\033[0m\n' "$*" >&2; exit 1; }

cd "$APP_DIR/backend"
[[ -f vendor/autoload.php ]] || die "backend/vendor yo'q — avval ./deploy.sh."
[[ -f .env ]] || die "backend/.env yo'q."

if [[ "${STOP:-0}" == "1" ]]; then
    log "Bot to'xtatilmoqda"
    pm2 delete "$BOT_APP" 2>/dev/null || echo "PM2'da '$BOT_APP' yo'q."
    exit 0
fi

env_value() { grep -E "^$1=" .env | tail -n1 | cut -d= -f2- | tr -d "\"' " || true; }
[[ -n "$(env_value TELEGRAM_BOT_TOKEN)" ]] || die ".env: TELEGRAM_BOT_TOKEN to'ldirilmagan."

# Bot ham asosiy bazani ishlatadi: o'lik bazaga ulanib, bo'sh aylanib yurmasin.
log "Baza holati"
set +e; "$PHP" artisan nexio:db:status; DB_CODE=$?; set -e
case "$DB_CODE" in
    1) die "Asosiy baza ishlamayapti. Bot ishga tushirilmadi. Zaxiraga o'tish: $PHP artisan nexio:db:failover, so'ng yana ./deploybot.sh" ;;
    4) die "Ikkala baza ham javob bermayapti. Bot ishga tushirilmadi." ;;
esac

# Config keshi eskirgan bo'lsa (.env o'zgargan), bot eski sozlamani o'qimasin.
"$PHP" artisan config:cache >/dev/null

log "Telegram tinglovchisi (nexio:telegram:poll)"
if [[ -n "${BOT_RESTART_CMD:-}" ]]; then
    bash -c "$BOT_RESTART_CMD"
else
    command -v pm2 >/dev/null 2>&1 || die "PM2 topilmadi. O'rnating yoki BOT_RESTART_CMD bering (supervisor/systemd)."
    if pm2 describe "$BOT_APP" >/dev/null 2>&1; then
        pm2 restart "$BOT_APP" --update-env
    else
        # --drop-webhook: polling va webhook birga ishlamaydi (Telegram 409 qaytaradi).
        pm2 start "$PHP" --name "$BOT_APP" --interpreter none --cwd "$APP_DIR/backend" -- artisan nexio:telegram:poll --drop-webhook
    fi
    pm2 save >/dev/null
fi
log "Tayyor. Holat: pm2 logs $BOT_APP"
