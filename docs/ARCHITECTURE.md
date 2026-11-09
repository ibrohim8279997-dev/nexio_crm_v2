# Nexio CRM — Architecture

## Overview

| Layer | Technology | Location |
|-------|-----------|----------|
| SPA | React 18, TypeScript, Vite, Tailwind 4, TanStack Query | `src/` (`.tsx`/`.ts` are the sources; tracked `.js` twins are stale compiler output) |
| API (production) | Laravel (PHP), JSON under `/api/v1` | `backend/` |
| Legacy API | Cloudflare Worker + D1 | `api/`, `wrangler.toml`, `migrations/` — not used in production |
| Plugins | Manifest-driven runtime (see [PLUGIN_ARCHITECTURE.md](PLUGIN_ARCHITECTURE.md)) | `backend/app/Plugins`, `backend/plugins` |

## Request lifecycle (backend)

1. `ApiContext` middleware (`backend/app/Http/Middleware/ApiContext.php`) runs first on every `/api` request. It checks same-origin on mutations, applies the per-IP auth limiter, resolves the caller from an API key (`Bearer nxk_…`) or the `nexio_session` cookie into a `Ctx`, confines portal users to `/api/v1/portal`, and stamps the security headers on the response.
2. Routes live in `backend/routes/api_v1.php` as `[Controller::class, 'method']` pairs (kept serializable for `route:cache`).
3. A controller method receives the `Ctx` (and, where the payload is non-trivial, a `FormRequest`). It calls `$ctx->auth()`, `H::requireTenant()`, `H::requirePermission()` and then queries with an explicit `tenant_id`.
4. `ApiException` is rendered as `{ "error": { "code", "message", "details"? } }` (`bootstrap/app.php`). Unexpected errors become a generic 500 without a stack trace.

## Backend building blocks

| Concern | Where | Notes |
|---------|-------|-------|
| Request context | `app/Support/Ctx.php` | user, tenant, role, session, API-key scopes |
| Helpers | `app/Support/H.php` | JSON responses, validation (`str`, `num`, `oneOf`), tenant references (`tenantRef`, `memberRef`), pagination, audit |
| RBAC | `app/Support/Rbac.php` | role → permission matrix; API keys carry explicit scopes instead |
| Controllers | `app/Http/Controllers/Api/V1/*` | one controller per domain (Auth, Crm, Sales, Ops, Commercial, ProjectCollab, Finance, Ai, AiExtras, …) |
| Form requests | `app/Http/Requests/*` | shape validation reported in the standard error envelope (`StoreQuoteRequest`) |
| Policies | `app/Policies/*` | state rules that depend on the record (`QuotePolicy`: who may do which transition when); registered as Gate abilities in `AppServiceProvider` |
| Events / listeners | `app/Events`, `app/Listeners` | `QuoteStatusChanged` → `RecordQuoteActivity` (timeline entry + webhook). Listeners are auto-discovered |
| Jobs | `app/Jobs` | `ExpireStaleQuotes`, scheduled daily in `routes/console.php` |
| Services | `app/Services/*` | webhooks, notifications, Telegram, Payme, Groq, CBU rates, TOTP |
| Schedule | `routes/console.php` | `nexio:billing:renew` hourly, `nexio:webhooks:retry` every minute, `ExpireStaleQuotes` daily 01:00 |

Primary keys are application-generated strings (`H::newId('qte')` → `qte_<16 hex>`); timestamps are UTC `Y-m-d H:i:s` strings.

## Frontend

- Routing in `src/App.tsx`; pages are lazy-loaded from `src/pages/app/*`.
- Server state: TanStack Query via `useApi` / `useApiMutation` (`src/lib/hooks.ts`), which also turns server errors (including 402/403) into toasts.
- Client state: React context for auth/theme/i18n; Zustand for small persisted UI state (`src/lib/uiStore.ts`).
- UI kit: `src/components/ui/*`. New screens use the Radix-based `Dialog` and `ActionMenu`, the TanStack-Table-based `DataGrid`, and React Hook Form for forms; older pages still use the legacy `Modal`/`DataTable`.
- Design tokens: `src/styles/index.css`. `--color-*` drive Tailwind; `--nx-*` are aliases of the same values. Inter is self-hosted through `@fontsource-variable/inter`.

## Testing

| Suite | Command | Scope |
|-------|---------|-------|
| Backend (PHPUnit) | `cd backend && php artisan test` | feature tests against a real schema, two seeded companies (A/B) |
| Frontend unit/component | `npm test` | Vitest + React Testing Library (jsdom opt-in per file) |
| End-to-end smoke | `npm run test:e2e` | Playwright against the Vite dev server with the API stubbed |

## Known limitations

- Controllers are still large (several exceed 400 lines); the Policy/FormRequest/Event/Job layers exist and are used by quotes, but older domains have not been migrated onto them.
- Real-time voice (ASR/TTS/SIP) is not implemented: AI calls are sandbox simulations and provider-backed calls are finalized through `POST /ai/calls/{id}/finalize`.
