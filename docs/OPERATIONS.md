# Nexio CRM — Operations

## Deploy

On the server, as the site user:

```bash
cd ~/htdocs/crmb.nexiolab.uz && ./deploy.sh
```

`deploy.sh` pulls, diffs against the last successful deploy (`.last-deploy`) and runs only what changed:

| Changed | Step |
|---------|------|
| `backend/composer.*` | `composer install` |
| `backend/database/`, `plugins/` | maintenance mode + `php artisan migrate` |
| `backend/**` | rebuild caches + `queue:restart` |
| `src/`, `package*.json`, `vite*` | frontend build + PM2 restart |

Options: `FORCE=1`, `SKIP_PULL=1`, `SKIP_FRONTEND=1`, `BRANCH`, `PHP`, `PM2_APP`. An interrupted deploy is re-run in full next time.

## Scheduler

One cron entry drives everything: `* * * * * php artisan schedule:run`.

| Task | Frequency | Purpose |
|------|-----------|---------|
| `nexio:billing:renew` | hourly | renew subscriptions |
| `nexio:webhooks:retry` | every minute | redeliver due webhooks |
| `ExpireStaleQuotes` job | daily 01:00 UTC | expire overdue draft/sent quotes |

A queue worker (`php artisan queue:work`) must run if the queue connection is not `sync`; `deploy.sh` restarts it.

## Database

- Migrations are in `backend/database/migrations`, idempotent where possible (`Schema::hasTable` guards).
- Take a backup before any deploy that includes migrations.
- Rollback of a single migration: `php artisan migrate:rollback --step=1` (only for migrations that define `down()`; the newest ones do).

## Health and monitoring

- `GET /up` — framework health check.
- Webhook health: `webhook_deliveries` rows with `status = 'dead'` need attention (endpoint down for the whole back-off window).
- Audit trail: `audit_logs`; AI blocks appear as `ai.call_blocked`, `ai.call_blocked_budget`, `ai.order_blocked`.
- Logs: `backend/storage/logs/`.

## Health, alerts, backup (runbook)

- `GET /api/v1/platform/health` (platform admin; 503 when degraded) checks: database latency, scheduler heartbeat (stale > 5 min = cron not running), queue backlog / failed jobs, dead webhooks (24 h), free disk, last backup age (> 36 h = failed).
- `php artisan nexio:health:alert` (every 5 min via scheduler) logs `critical` and POSTs `{"text": "..."}` to `ALERT_WEBHOOK_URL` (https only) once per incident, plus one "recovered" notice.
- **Backup**: `php artisan nexio:backup` (daily 02:00) writes `storage/app/backups/nexio-<UTC>.{sql|sqlite}.gz` (pg_dump / mysqldump / file copy), keeps the newest 14 and records `last.json`. Needs `pg_dump` (or `mysqldump`) on the server PATH. Copy the folder off-server (rsync/S3) — a backup on the same disk is not a disaster-recovery plan.
- **Restore (PostgreSQL)**: put the app in maintenance (`php artisan down`), then `gunzip -c nexio-XXXX.sql.gz | psql -h HOST -U USER DBNAME`, `php artisan migrate --force`, `php artisan up`. Rehearse on a staging DB first.
- **Sentry**: set `SENTRY_LARAVEL_DSN` (and optionally `SENTRY_TRACES_SAMPLE_RATE`); unset = disabled.

## Redis, Horizon, S3 (optional, env-driven)

Defaults stay `database` queue/cache and local files. To switch:

| Goal | Env |
|------|-----|
| Redis cache + queue | `CACHE_STORE=redis`, `QUEUE_CONNECTION=redis`, `REDIS_HOST`, `REDIS_PASSWORD` (client: predis, no PHP extension needed) |
| Horizon supervisor | run `php artisan horizon` instead of `queue:work` (needs `ext-pcntl`, Linux); dashboard at `/horizon` is gated by `HORIZON_ALLOWED_EMAILS` and the CRM uses its own sessions, so rely on `php artisan horizon:status` / metrics unless you add an auth bridge |
| S3 attachments | `ATTACHMENTS_DISK=s3`, `AWS_ACCESS_KEY_ID`, `AWS_SECRET_ACCESS_KEY`, `AWS_DEFAULT_REGION`, `AWS_BUCKET` (+ `AWS_ENDPOINT`, `AWS_USE_PATH_STYLE_ENDPOINT=true` for MinIO/R2). Only new uploads move; each row stores its `disk`, old files stay readable. |

## Docker and CI

- `docker compose up --build` starts Postgres 16, Redis 7, the API (`backend/Dockerfile`: php-fpm + nginx + queue worker + scheduler under supervisord, migrates on start) and the SPA (`Dockerfile`: Vite build behind nginx, `/api` proxied). Put secrets in an untracked `.env.docker`. The Docker files are not exercised by the current production deploy (`deploy.sh` on CloudPanel).
- `.github/workflows/ci.yml`: backend `php artisan test` + `nexio:openapi --check`, frontend build + Vitest, Playwright smoke, and image builds.

## Standby database and Telegram bot (one server)

### Standby database
A second, independent database on the same server (own name/file, ideally its own DB user) that is kept identical to the main one. It is for **recovery from a dead, damaged or tampered main database**, not for load balancing.

`.env`: `REPLICA_ENABLED=true`, `REPLICA_DB_DATABASE=<other db name or sqlite file>` (+ `REPLICA_DB_HOST/PORT/USERNAME/PASSWORD` if they differ; `REPLICA_DB_CONNECTION` defaults to `DB_CONNECTION`). The standby must not be the main database (the app refuses to copy a database into itself). Create the empty database and its user once, then `./deploy.sh` creates the schema (`migrate --database=replica`) and does the first copy.

| What | Command |
|---|---|
| Copy changed tables (runs every 5 min by the scheduler) | `php artisan nexio:db:replicate` |
| Row-by-row check of all tables (nightly 03:30, catches in-place edits) | `php artisan nexio:db:replicate --verify` |
| Is the main / standby database alive? (exit 0 ok, 1 main down, 2 standby down, 4 both down, 5 running on standby) | `php artisan nexio:db:status` |
| **Main DB is down or compromised → run the app on the standby** | `php artisan nexio:db:failover` (add `--force` if the main DB still answers) |
| Main DB recreated/repaired → copy standby back into it | `php artisan nexio:db:restore --yes` (then) `php artisan nexio:db:failback` |

- `deploy.sh` runs `nexio:db:status` on **every** deploy: if the main database is down it stops before migrating and prints the failover command; otherwise it migrates the standby and copies to it.
- `/api/v1/platform/health` and `nexio:health:alert` include a `replica` check (alive, last copy < 15 min, no copy errors, not failed over).
- After `failover`/`failback` restart long-running processes (PHP-FPM, PM2, queue workers): `queue:restart` is issued automatically, `./deploybot.sh` restarts the bot. While failed over, copying is suspended so the (dead) main database can never overwrite the standby.
- Data written after the last copy (≤ 5 min) is lost on failover. For a fully consistent history keep the nightly `nexio:backup` dumps as well.
- **Same server = same blast radius.** The standby protects against a database crash, a bad migration or a corrupted/emptied database. It does **not** protect against loss, theft or full compromise of the server itself (an attacker with the server has both databases, and the standby has the same data). For that, point `REPLICA_DB_HOST` at a different host, or copy `storage/app/backups` off the server.

### Telegram bot
The bot runs as its own process on the same server and uses the same database. It is started and restarted by `./deploybot.sh` (PM2 app `nexio-bot`, `php artisan nexio:telegram:poll`), never by `deploy.sh`. Run `./deploy.sh` first to update the code, then `./deploybot.sh`; `STOP=1 ./deploybot.sh` stops it. Needs `TELEGRAM_BOT_TOKEN` in `backend/.env`. One poller per bot token; never set a webhook for the same bot.

## Integrations surface

- **OpenAPI 3.1** `docs/openapi.json` and Postman collection `docs/nexio.postman_collection.json` are generated from the route table: `php artisan nexio:openapi` (CI fails when stale). Request/response bodies are described generically.
- **OAuth2 client_credentials**: `POST /api/oauth/token` with `grant_type=client_credentials`, `client_id` = API key id, `client_secret` = the key token (body or HTTP Basic), optional narrower `scope`. Returns a 1 h Bearer token usable like an API key; revoking the key revokes its tokens. Authorization-code flow for third-party apps is not implemented.
- **Lead-capture widget**: create a form via `POST /api/v1/lead-forms` `{name, allowed_origins:["https://site.uz"]}`, then embed `<script src="https://API_HOST/nexio-widget.js" data-key="lfk_…" async></script>` (optional `data-lang="uz|ru|en"`, mount point `<div id="nexio-form">`). Public endpoint is origin-allowlisted, 5 submissions/min/IP, honeypot-protected; leads get `source = web_form`. There is no admin UI yet — manage forms through the API.

## Tests before release

```bash
cd backend && php artisan test      # backend
npm test                            # frontend unit + component
npm run test:e2e                    # browser smoke (needs `npx playwright install chromium` once)
npm run build                       # type-check + production bundle
```

Known pre-existing failures at the time of writing: 7 plugin tests (`PluginLifecycleTest`, `PluginMarketplaceTest`, `PluginSecurityTest`) that assert a plugin version of `1.0.0` where the seeded catalogue now publishes `1.0.2`, and a developer-profile precondition. They are unrelated to the CRM/AI features.

## Incident quick reference

| Symptom | First check |
|---------|-------------|
| Users locked out of login | per-account limiter: 8 failures / 15 min / email+IP; clear the cache key `login:<sha1(email|ip)>` |
| AI calls refused | `audit_logs` for `ai.call_blocked*`; agent `budget_used` vs `budget_limit`; policy `daily_budget` / `monthly_budget`; campaign `budget_used` |
| Webhooks not arriving | endpoint `is_active`, subscribed `events`, `webhook_deliveries.error`, scheduler running |
| `403 csrf` from the SPA | frontend origin missing from `config/cors.php` |
