# Nexio CRM — Multi-tenancy

One database, shared tables. Isolation is enforced in application code, so every rule below is something a new endpoint must follow.

## Rules

1. **Tenant identity comes from the session, never from the request.** `ApiContext` loads the session (or API key) and sets `Ctx::$tenantId`. A `tenant_id` sent in a body or query string is ignored.
2. **Every tenant table has a `tenant_id` column** and every query filters on it: `->where('tenant_id', $ctx->tenantId)`. Fetching by primary key alone is not enough.
3. **Another company's record looks like a missing one.** Reads and writes of a foreign id answer `404`, not `403`, so ids cannot be probed.
4. **References in input are re-verified.** Use `H::tenantRef($ctx, $id, 'table', 'label')` for any `*_id` that points at another tenant table and `H::memberRef()` for users. A foreign id yields `422 invalid_reference`. (Order lines are the one deliberate exception: an unknown/foreign `product_id` is dropped and the line is stored as free text.)
5. **Child rows carry `tenant_id` too** (items, comments, assignments), so a child can be queried safely on its own.
6. **Platform operators** (`is_platform_admin`) pass permission checks but still need a tenant for tenant records (`H::requireTenant`).
7. **Client-portal users** are confined to `/api/v1/portal` by `ApiContext` before routing.
8. **API keys** are bound to one tenant and a fixed set of scopes (`ApiContext::KEY_RESOURCES`); auth, team, settings, billing, plugins and key management are never reachable with a key.
9. **Uniqueness is per tenant** (`unique(tenant_id, number)`, `unique(tenant_id, name)`); the same tag name or quote number may exist in two companies.
10. **Concurrency:** state changes use compare-and-set (`WHERE status = <old>`) or unique keys so two requests cannot both win (quote transitions, quote→order conversion, AI provider event ids).

## How it is tested

`backend/tests/Feature/PluginTestCase.php` seeds two companies, **Alpha** (`tnt_a`) and **Beta** (`tnt_b`), each with an owner, and offers `actingAsAlpha()` / `actingAsBeta()`.

- `CrmTenantIsolationTest` — A creates organizations, contacts, leads, projects, tasks and products; B must get `404` on read/update/delete, empty lists and searches, `422` on foreign references, and its own `tenant_id` on every insert even if the body claims another. Unauthenticated calls get `401`.
- `CrmExtrasTest`, `CommercialCollabTest`, `AiExtrasTest`, `FinanceTest`, `WorkspaceTest`, `PluginTenantIsolationTest` — the same A/B pattern per feature.

Note: the acting-as cookie lives on the test instance. Create fixtures for both companies before asserting, or reset `$this->defaultCookies = []` for unauthenticated assertions.

## Checklist for a new endpoint

- [ ] `$ctx->auth()`, `H::requireTenant($ctx)`, `H::requirePermission($ctx, '…')`
- [ ] every query has `tenant_id`; children inserted with `tenant_id`
- [ ] every `*_id` from input goes through `tenantRef` / `memberRef`
- [ ] a Beta-vs-Alpha test exists (read, write, delete, list, foreign reference)
