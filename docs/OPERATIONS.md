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
