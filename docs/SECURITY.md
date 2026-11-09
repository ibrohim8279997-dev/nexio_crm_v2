# Nexio CRM — Security

## Authentication

| Mechanism | Detail |
|-----------|--------|
| Passwords | PBKDF2-SHA256, 120 000 iterations, per-user random salt (`H::hashPassword`) |
| Sessions | Opaque id in the `nexio_session` cookie, stored server-side with expiry (14 days) and IP/user-agent; users can list and revoke sessions |
| MFA | TOTP (RFC 6238) with single-use recovery codes. A password alone never creates a session for an MFA-enabled account. A used TOTP step cannot be replayed |
| API keys | `Bearer nxk_…`, stored as SHA-256 hash, tenant-bound, scoped, revocable, optional expiry. Never valid for auth, team, settings, billing, plugin or key-management endpoints |
| Email | Verification token on registration; resend is throttled |

## Abuse protection

| Control | Limit |
|---------|-------|
| Per-IP on login / register / forgot-password | 20 requests per minute (`ApiContext`) |
| Per-account failed logins | 8 failures per email + IP, then `429 too_many_attempts` for 15 minutes; cleared on success |
| MFA code attempts | 5 per 5 minutes per user |
| File uploads | 60 per minute; 10 MB; extension allow-list; stored under generated names |
| Webhook delivery | HMAC-SHA256 signature over `timestamp.body`; exponential back-off then `dead` |

## HTTP hardening

Every `/api` response carries (set in `ApiContext`, also by `H::json`):

- `Content-Security-Policy: default-src 'none'; frame-ancestors 'none'; base-uri 'none'` — the API serves JSON and downloads only
- `Strict-Transport-Security: max-age=31536000; includeSubDomains`
- `X-Content-Type-Options: nosniff`, `X-Frame-Options: DENY`, `Referrer-Policy`, `Permissions-Policy`
- `Cache-Control: no-store`

CSRF: mutations whose `Origin` is neither the API origin nor an origin listed in `config/cors.php` are rejected with `403 csrf`.

The SPA's own CSP and HSTS are served by the web server / reverse proxy in front of `dist/`; configure them there (the API headers above do not apply to static assets).

## Authorization

- Role → permission matrix in `app/Support/Rbac.php`; `H::requirePermission()` on every mutating endpoint.
- Record-dependent rules live in policies (`QuotePolicy`), e.g. only drafts are editable and only legal status transitions are allowed.
- Tenant isolation: see [TENANCY.md](TENANCY.md).
- Every sensitive action writes an `audit_logs` row (actor, action, target, IP).

## Secrets and data

- Plugin secrets are encrypted at rest in the vault; the key is configuration, never committed.
- Never commit `.env` files, API tokens or provider credentials.
- Call recordings and transcripts are readable only by owner/admin/sales manager/team lead/analyst roles.

## Reporting

Report vulnerabilities privately to the Nexio Lab maintainers; do not open a public issue.
