# Nexio Plugin System + Marketplace — Architecture & Change Plan

Status: **approved plan** — implementation follows this document.
Owner: Nexio Lab. This document is the single source of truth shared by every work stream.

---

## 1. Project map (verified against the repository, not assumed)

Two interchangeable backends serve the same contract. Both were verified by reading the code.

| Layer | Location | Stack |
|---|---|---|
| Backend **A** (primary for this work) | `backend/` | Laravel 13 / PHP 8.3+ / SQLite (D1-compatible SQL) |
| Backend **B** (legacy) | `api/` | Cloudflare Worker + TypeScript + D1 |
| Frontend | `src/` | Vite 6 + React 18 + TypeScript + Tailwind 4 + TanStack Query |
| Schema | `backend/database/schema/0001_init.sql` (`migrations/0001_init.sql` identical) | 43 tables |
| Seed | `backend/database/schema/seed.sql` | deterministic fixture data |

`backend/` is the Laravel port committed in `e288c4e`; it reproduces the 102 `/api/v1/*`
endpoints of the Worker. **This work extends `backend/` only.** The Worker is left untouched.

### Verified conventions that the new code must follow

| Concern | Existing convention (verified) |
|---|---|
| Routing | `backend/routes/api_v1.php`, wrapped by the `$h(Controller::class,'method')` macro that injects `Ctx` |
| Request context | `App\Support\Ctx` — singleton bound by `App\Http\Middleware\ApiContext`; `tenantId`, `role`, `user`, `params` |
| DB access | `Illuminate\Support\Facades\DB`, **raw parameterised SQL**, string IDs with typed prefixes (`org_`, `aud_`, …). No Eloquent models. |
| Helpers | `App\Support\H` — `json`, `body`, `str`, `num`, `oneOf`, `paginate`, `parseJson`, `audit`, `requirePermission`, `requirePlatform` |
| Errors | `App\Support\ApiException(status, message, code)` → `{error:{code,message}}` |
| Auth | session cookie `nexio_session` → `sessions` + `tenant_memberships`; PBKDF2-SHA256 120k |
| RBAC | `App\Support\Rbac` — 13 roles × 25 permissions |
| Tenant scoping | every query carries `WHERE tenant_id = ?`; ID lookups use `... WHERE id = ? AND tenant_id = ?` |
| Audit | `audit_logs` via `H::audit($ctx, $action, $target, $targetId, $meta)` |
| Plans | `plans` (+ `plan_features`), `subscriptions`, `feature_flags`, `usage_records` |
| Super Admin | `tenants.plan_id`, `users.is_platform_admin`, `platform_role`; `PlatformController` under `/platform/*` |
| Frontend | `src/App.tsx` routes, `src/lib/api.ts` fetch client, `src/lib/hooks.ts` `useApi`/`useApiMutation`, `src/components/ui/*` primitives, `AppShell` sidebar `GROUPS`, `PlatformShell` for superadmin |

### Components reused (no duplicates created)

- `plans` / `plan_features` / `subscriptions` / `tenants.plan_id` — **the** plan system. No second plan table.
- `audit_logs` — **the** audit store. Plugin actions are written here with a `plugin.` action prefix.
- `integrations` — tenant integration records for the telephony/delivery providers.
- `feature_flags` — tenant-level on/off switches.
- `users`, `tenants`, `tenant_memberships` — identity and tenancy.
- `ApiException`, `Ctx`, `H`, `Rbac`, `ApiContext` middleware, `PlatformController`, `PlatformShell`.

---

## 2. Contradictions found in the brief → recorded decisions

The brief requires that contradictions are not silently "fixed". These are the ones found.

| # | Brief says | Repository reality | Decision taken |
|---|---|---|---|
| C1 | Create `plugin_audit_logs` | `audit_logs` already exists with `tenant_id, actor_id, action, target, target_id, meta, ip, created_at` and is already used everywhere | **Reuse `audit_logs`.** Plugin actions use `action = plugin.*`, `target = 'plugin'`. `result` and `request_id` (which `audit_logs` lacks) are stored inside the existing `meta` JSON blob. No duplicate audit table. |
| C2 | "Do not hardcode plan rules" | `BillingController::PLAN_MODULES` is a hardcoded PHP map (lines 18–24) | Left **untouched** (out of scope, working code). The new `EntitlementEngine` reads **all** plugin rules from `plugin_entitlements` / `plugin_feature_rules` / `plugin_limits` in the DB. **No plugin plan rule is ever hardcoded in PHP.** |
| C3 | Third-party sandbox | Repo has no container/process isolation, and the runtime is a PHP-FPM-style request lifecycle | Executable third-party plugins are **not installable**. The catalog, submission, review and publishing pipeline for third-party plugins **is** built; `install` on a `third_party` plugin returns a hard `403 sandbox_unavailable` until an isolation runtime exists. Official (Nexio Lab) plugins run in-process. This is stated, not hidden. |
| C4 | 60 parallel agents | This session exposes exactly one subagent tool: web research | Work is executed sequentially with a single owner per file. Parallelism is used only for external research. Reported honestly. |
| C5 | "Nexio Telephony works" | No SIP provider, no credentials, no trunk | Implemented as a **provider abstraction** with a `sandbox` provider that never claims to place a real call. With no configured credentials, status is `sandbox` and calls are recorded as simulated, never as `completed`-by-a-carrier. |
| C6 | Uzum / Yandex endpoints | No official API documentation or credentials available | Adapters implement a **declared interface** and are registered as `not_configured`. No invented endpoints. See §8. |

---

## 3. Architecture

```
Nexio Core (existing, untouched)
  Auth · Tenants · Users · Roles · Plans · Subscriptions · Audit · Core API

Nexio Plugin System (new — app/Plugins/*)
  Manifest        validate/normalise + sign check
  PluginRegistry  catalog read model, availability state machine
  EntitlementEngine  plan → access / feature / limit rules   (DB-driven)
  UsageMeter      atomic counters, period windows, idempotency, enforcement
  PermissionEngine   effective permission set, revocation with generation counter
  DependencyResolver
  VersionManager  semver, constraints, checksums, update/rollback
  PluginLifecycle install → enable → disable → update → rollback → uninstall
  EventBus        tenant-scoped events, retries, dead-letter
  SecretsVault    AES-256-GCM (Laravel Crypt) with a key outside the DB
  PackageStore    zip inspection, traversal/size guards, checksums. NEVER executes.

Marketplace (new)        Plugin Manager (new)      Developer Portal (new)
  catalog / details        install/enable/…         create plugin / upload version
  categories               settings / limits        submit / review status
  official / third-party   connections / usage
  updates

Official plugins (backend/plugins/*)  — trusted, in-process, allowlisted by path
  nexio.telephony · nexio.delivery · nexio.uzum.delivery · nexio.yandex.delivery
```

### Why official plugins are on-disk directories

An official plugin is a directory containing `manifest.json` and a PHP entry class.
Only directories listed in `config/nexio.php → plugins.paths` are ever autoloaded, and only
if the manifest declares `type: official`, `author: Nexio Lab`, and the resolved path stays
inside the allowlisted directory (realpath check). **Uploaded packages are never code-loaded.**
`PackageStore` treats them as inert archives.

---

## 4. Data model (18 new tables, all additive)

Every tenant-scoped table carries `tenant_id`. Unique keys are chosen so that concurrent
installs/meters cannot create duplicates.

| Table | Purpose | Key constraints |
|---|---|---|
| `plugin_categories` | marketplace taxonomy, managed by Super Admin | `code` UNIQUE |
| `plugin_developers` | publisher identity, approval status | `email` UNIQUE |
| `plugins` | catalog entry | `id` PK (plugin id = slug), `developer_id`, `category_id` |
| `plugin_versions` | immutable published artefacts | `(plugin_id, version)` UNIQUE |
| `plugin_reviews` | review decisions per version | `version_id` |
| `plugin_installations` | one row per company per plugin | `(tenant_id, plugin_id)` UNIQUE |
| `plugin_connections` | multiple connections per installation (e.g. 2 SIP trunks) | `(installation_id, name)` UNIQUE |
| `plugin_settings` | non-secret settings only | `(installation_id, key)` UNIQUE |
| `plugin_secrets` | encrypted credentials, never returned | `(connection_id, key)` UNIQUE |
| `plugin_permissions` | platform-level grant of a declared permission | `(plugin_id, permission)` UNIQUE |
| `plugin_dependencies` | declared dependencies | `(plugin_id, depends_on_plugin_id)` UNIQUE |
| `plugin_entitlements` | plan → may install / may use | `(plugin_id, plan_id)` UNIQUE |
| `plugin_feature_rules` | plan → named feature on/off | `(plugin_id, plan_id, feature_key)` UNIQUE |
| `plugin_limits` | plan → metric limit + policy | `(plugin_id, plan_id, metric, period)` UNIQUE |
| `plugin_usage` | append-only usage ledger | `(tenant_id, plugin_id, metric, idempotency_key)` UNIQUE |
| `plugin_usage_counters` | atomic counter per (tenant, plugin, metric, period) | `(tenant_id, plugin_id, metric, period, period_start)` UNIQUE |
| `plugin_events` | tenant-scoped event outbox | `(tenant_id, plugin_id, event, idempotency_key)` UNIQUE |
| `plugin_subscriptions` | plugin subscribes to an event | `(installation_id, event)` UNIQUE |

`limit_value IS NULL` = no limit configured. `limit_value = 0` = operation forbidden.
These are **never** conflated (§8.2).

Rollback: migration `down()` drops exactly these 18 tables in dependency order and touches
no existing table. Verified by `php artisan migrate:rollback` in a scratch database.

---

## 5. Availability state machine (computed on the server)

`install | installed | configure | update_available | upgrade_required | unavailable |
dependency_required | limit_reached | disabled | sandbox_unavailable | not_configured`

The frontend renders the value returned by the API. It never computes availability.
Evaluation order: published? → plan access → sandbox gate → installed? → dependencies →
installation limit → feature rules. The first failing gate wins and its reason is returned
as `reason` + machine code so the UI can explain *why* without leaking other tenants' data.

---

## 6. Enforcement order (every plugin operation)

```
auth → tenant context → plugin install state → permission → entitlement/feature
     → usage limit (atomic, pre-operation) → operation → usage commit → audit → event
```

A caller cannot reach a core service through an alternative controller: the checks live in
`PluginRuntime`, not in the controllers, and every plugin endpoint goes through it.

---

## 7. Usage metering rules

- Counters are incremented inside a transaction with a conditional `UPDATE ... WHERE used < ?`
  so **concurrent requests cannot exceed a limit** (`changes()` is checked).
- `idempotency_key` is UNIQUE per (tenant, plugin, metric) — a replayed webhook or client
  retry is charged once. Absent key ⇒ a random key is generated (charged each time).
- Period windows are computed in the **company's timezone** (`tenants.timezone`): `daily`,
  `weekly` (ISO Monday), `monthly`, `total`, `per_user`, `per_company`, `per_connection`.
- Failed/cancelled operations are **not** charged (commit happens after success).
- Policies: `BLOCK` (402 before the operation), `WARN` (proceed, flag `overage` true),
  `ALLOW_OVERAGE` (proceed, recorded as overage). No automatic money is moved.
- `BLOCK` responses carry `metric`, `used`, `limit`, `remaining`, `reset_at`.

---

## 8. Security model

### 8.1 Tenant isolation
- `tenant_id` is **only** taken from the session (`Ctx->tenantId`), never from the body.
- Every read/write of an installation, settings row, connection, secret, usage row and event
  is filtered by `tenant_id`, and 404 (not 403) is returned for foreign objects so object
  existence is not disclosed.
- Development-time tests create tenants A and B and assert A cannot read/modify/measure B.

### 8.2 Secrets
- `plugin_secrets.ciphertext` = Laravel `Crypt` (AES-256-GCM), key from `PLUGIN_SECRETS_KEY`.
  The key lives in the environment, **never** in the database and never in the repo.
- Secrets are write-only through the API: `PUT` stores, `GET` returns `••••••••` plus
  `has_value` / `updated_at`. No endpoint returns a plaintext secret.
- Secret values are excluded from `meta` in audit rows, from logs and from error messages.
- Rotation = overwrite. Revocation = delete + `enabled_at` invalidation.

### 8.3 Untrusted code
- Uploads: extension allowlist, zip-bomb guards (entry count, uncompressed size, ratio),
  `..`/absolute-path/symlink rejection, entry checksum verification, no execution.
- SSRF: outbound provider calls are restricted to an allowlist of hosts per official plugin;
  user-supplied URLs are rejected for the adapter hosts.
- Revocation propagation: `plugin_permissions.generation` bumps on grant/revoke; the
  `PluginRuntime` re-reads permissions on every call, and long-lived event handlers
  re-check before each sensitive operation (no cached permission decisions).

### 8.4 Logging
`H::audit` writes `action/target/target_id/meta`. A redaction helper strips
`password|secret|token|api_key|authorization|ciphertext` keys from any meta before insert.

---

## 9. Risk register

| Risk | Impact | Mitigation |
|---|---|---|
| Breaking existing CRM | High | All changes are additive: new tables, new routes, new files. No existing file's behaviour is changed except adding route groups and one sidebar group. Verified by re-running the existing `/api/v1/*` suite. |
| Migration damaans the wrong DB | High | Migration only `CREATE TABLE`. No `DROP` of existing tables, no `migrate:fresh`, no data deletion. Verified with a scratch copy before running on the dev DB. |
| Hardcoded plan rules | High | Rules live in `plugin_entitlements` / `plugin_feature_rules` / `plugin_limits` only. |
| Limit bypass by concurrency | High | Conditional atomic `UPDATE`, verified by a concurrent-request test. |
| Secret leak | Critical | Write-only API, redacted audit, encrypted at rest, key outside DB. |
| Untrusted code execution | Critical | Third-party install blocked until an isolation runtime exists. |
| Silent fake integrations | High | Every unconfigured provider reports `not_configured` / `sandbox` and never a success state. |
| Super Admin data over-reach | High | Marketplace admin endpoints expose catalog/metadata only. Per-tenant plugin data is not returned to platform operators without an explicit tenant-scoped, audited query. |

---

## 10. Delivery order (matches the brief's stages)

0. Research ✅ (this document) → 1. Contracts/security ✅ → 2. DB + Plugin Core →
3. Permissions/Entitlements + tests → 4. Plugin Manager → 5. Marketplace →
6. Super Admin → 7. Developer Portal → 8. Four official plugins → 9. Security audit →
10. Regression tests → 11. Report.

## 11. Deployment & rollback

Deploy: `git pull` → `composer install --no-dev` → set `PLUGIN_SECRETS_KEY` →
`php artisan migrate --force` (additive) → `php artisan config:cache` →
smoke-check `/api/health`, `/api/v1/marketplace/plugins`.

Rollback: `php artisan migrate:rollback --step=1` removes the 18 plugin tables only.
Core CRM tables and data are unaffected. There is **no** automatic data rollback for plugin
settings/secrets — export them before rolling back if they must be kept.

## 12. Manual verification

Log in as `owner@nexio.uz` (platform) → `/platform/marketplace`.
Log in as `admin@acmecorp.uz` (tenant) → `/app/marketplace` → install Nexio Delivery.
Steps and expected results are listed in §13 of the final report.
