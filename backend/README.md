# Nexio CRM — Laravel backend

Laravel port of the Cloudflare Worker API (`/api/v1/*`). Same database schema, same
seed data, same request/response contract — the React SPA in the repository root works
against either backend without changes.

## Stack

| | |
|---|---|
| Framework | Laravel 13 (`backend/`) |
| Language | PHP 8.4 |
| Database | SQLite via Eloquent + query builder (`pdo_sqlite`) |
| Session | own `sessions` table + HttpOnly cookie `nexio_session` (not Laravel sessions) |
| Cache | file store (used for the auth rate limiter) |

## Install

```bash
# 1. PHP 8.2+ with pdo_sqlite, mbstring, dom, openssl, tokenizer, curl, zip + Composer
#    (no root needed — a static build works fine, e.g. static-php-cli)
# 2. install dependencies
composer install
# 3. app key + environment
cp .env.example .env
php artisan key:generate
```

## Run

```bash
php artisan migrate --force     # 43 tables from database/schema/0001_init.sql
php artisan db:seed --force     # deterministic dataset from database/schema/seed.sql
php artisan serve --host=0.0.0.0 --port=8000
```

Health probe: `GET /api/health` → `{"status":"ok",...}`

Point the frontend at it:

```bash
VITE_API_TARGET=http://127.0.0.1:8000 npm run dev   # from the repository root
```

## Demo accounts

Password `nexio123` for all of them.

| Email | Role |
|---|---|
| `admin@acmecorp.uz` | tenant owner (Acme Corp) |
| `owner@globex.uz` | tenant owner (Globex Industries) |
| `client@acmecorp.uz` | client portal user (Fergana Textile) |
| `owner@nexio.uz` | platform operator (Nexio Lab) |

## Structure

```
app/Support/            ApiException, Ctx, Rbac (role→permission matrix), H (helpers)
app/Http/Middleware/    ApiContext — session→tenant resolution, CSRF, rate limit, portal scope
app/Http/Controllers/Api/V1/
                        Auth, Dashboard, Crm, Sales, Ops, Modules, Team, Billing, Ai, Portal, Platform
routes/api.php          health probe
routes/api_v1.php       102 routes under /api/v1
database/schema/        0001_init.sql (DDL) + seed.sql (data), shared with the Worker build
database/migrations/    single migration that applies the DDL
database/seeders/       NexioSeeder loads seed.sql
```

## Security invariants (ported 1:1)

- Tenant context is derived from the verified session, never from client input.
- `client_portal_user` sessions are confined to `/api/v1/portal` and `/api/v1/auth`.
- Mutations with a foreign `Origin` are rejected (`403 csrf`).
- Passwords: PBKDF2-SHA256, 120 000 iterations, constant-time comparison.
- Every mutating action writes to `audit_logs`; exports are audited with row counts.
- Errors never leak stack traces (`500` returns a generic message).

## Differences from the Worker build

- **`invitations.tenant_id` is nullable.** Password-reset tokens are tenant-less; the D1
  DDL declared the column `NOT NULL`, which made `forgot-password` fail. Fixed here.
- Rate limiting uses Laravel's file cache instead of a per-isolate in-memory bucket —
  it now applies across processes rather than per Worker isolate.

## Not connected (sandbox mode, same as the Worker build)

AI providers, telephony, payments and email have no live credentials. Calls, charges and
messages are recorded but not executed, and the UI states this explicitly.
