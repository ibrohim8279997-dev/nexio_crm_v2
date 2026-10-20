# Nexio CRM Plugin Development Guide

This document describes the plugin system that is actually implemented in this repository. It is the
working contract for adding or maintaining a plugin; it is not a proposal for a second plugin system.

## 1. System purpose

Plugins extend a tenant's CRM without copying CRM navigation, authentication, billing, audit, or
tenant-isolation logic. The existing Plugin Manager owns installation and lifecycle actions. The
frontend renders server-provided availability and capability state, while the backend remains the
authority for permissions, plan access, limits, secrets, and data ownership.

The primary backend is `backend/` (Laravel). `api/` is the legacy Worker backend and is not extended
by the current plugin implementation. The frontend is the Vite/React application in `src/`.

## 2. Plugin UI architecture

The tenant UI is integrated into the existing CRM shell:

- `/app/marketplace` lists published plugins and their server-computed state.
- `/app/plugins` is the existing Plugin Manager for installed plugins.
- `/app/plugins/:id` is the shared plugin workspace for health, operations, settings, connections,
  usage, and lifecycle status.
- `/app/inventory` is the dedicated workspace for the official Inventory plugin and uses the same
  guarded operation API.
- `src/components/layout/AppShell.tsx` contributes the Marketplace and Plugin Manager entries to the
  existing sidebar; no second sidebar or navigation shell is introduced.

Routes are declared in `src/App.tsx`. Backend routes are declared in `backend/routes/api_v1.php` and
use the existing `Ctx`, `ApiContext`, `H`, `Rbac`, and `ApiException` conventions.

The frontend must treat these fields as authoritative rather than reconstructing policy locally:
`state`, `state_reason`, `entitlement`, `allowed`, `code`, `reason`, `limit`, `remaining`, and
`reset_at`. A hidden or disabled button is only a usability aid; every backend operation repeats the
same checks.

## 3. Existing Plugin Manager

`PluginManagerController` delegates lifecycle work to `App\Plugins\PluginLifecycle` and runtime work
to `App\Plugins\PluginRuntime`. The available lifecycle endpoints are:

```text
GET    /api/v1/plugins/installed
POST   /api/v1/plugins/{id}/install
POST   /api/v1/plugins/{id}/enable
POST   /api/v1/plugins/{id}/disable
POST   /api/v1/plugins/{id}/update
POST   /api/v1/plugins/{id}/rollback
DELETE /api/v1/plugins/{id}
GET    /api/v1/plugins/{id}
GET    /api/v1/plugins/{id}/capabilities
GET    /api/v1/plugins/{id}/health
GET/PUT /api/v1/plugins/{id}/settings
GET    /api/v1/plugins/{id}/usage
GET    /api/v1/plugins/{id}/limits
GET/POST /api/v1/plugins/{id}/connections
```

Use the existing `plugin_*` tables and `audit_logs` store. Plugin audit actions use the `plugin.*`
prefix and are redacted before persistence. Do not create a second manager, plan table, audit table,
or tenant identity mechanism.

## 4. Registering an official plugin

Official plugins live under an allowlisted directory in `backend/plugins/<plugin-id>/`. They require:

1. A valid `manifest.json`.
2. A PHP entry class implementing `App\Plugins\Contracts\OfficialPlugin`.
3. A stable `id()` matching the manifest id.
4. Registration in the database through the existing `PluginSeeder`/catalog flow.
5. Entitlement rows in `plugin_entitlements`; feature and usage rules belong in their existing tables.
6. Tests for tenant isolation, permissions, limits, lifecycle, and plugin-specific behavior.

The manifest is validated by `App\Plugins\Manifest` and loaded only by
`App\Plugins\PluginLoader`. The loader scans only `config/nexio.php` plugin paths, checks real-path
containment, validates syntax, verifies the entry class, and can verify the published directory
checksum. Uploaded packages are never PHP-included.

Example, adapted to the repository's real contract:

```json
{
  "id": "nexio.example",
  "name": "Nexio Example",
  "version": "1.0.0",
  "type": "official",
  "author": "Nexio Lab",
  "category": "operations",
  "api_version": "1.0",
  "min_nexio_version": "2.0.0",
  "permissions": ["crm.read"],
  "dependencies": [],
  "usage_metrics": [],
  "settings_schema": {},
  "runtime": {"entry": "Nexio\\Example\\Plugin@Plugin.php"}
}
```

The PHP class declares its public operations in `operations()`, executes only those operation names
in `execute()`, and implements `health()`, `onEvent()`, and `settingsSchema()`. The runtime rejects
undeclared operations; controllers must not call plugin classes directly.

## 5. Routes, operations, and API use

Generic plugin operations use:

```text
POST /api/v1/plugins/{plugin-id}/operations/{operation}
```

`PluginRuntime::invoke()` enforces the complete pipeline:

```text
authentication → tenant context → installation/enabled state → permission
→ entitlement/feature → atomic usage limit → operation → usage commit → audit → event
```

Use parameterized SQL through `DB`. Every plugin-owned read and write must include the tenant id
from `Ctx`; never accept a tenant id from request input. Return a 404 for a foreign tenant object so
its existence is not disclosed.

## 6. Plans, permissions, settings, and secrets

- `EntitlementEngine` reads plugin access from `plugin_entitlements`, `plugin_feature_rules`, and
  `plugin_limits`; plugin plan rules must not be hardcoded in PHP.
- `PermissionEngine` distinguishes declared permissions from platform grants and rechecks grants at
  runtime.
- Non-secret settings use `GET/PUT /api/v1/plugins/{id}/settings` and must be declared in the
  manifest schema.
- Credentials use the connection secrets endpoints only. `SecretsVault` encrypts values with the
  environment-provided plugin secrets key. Plaintext secrets are never returned, logged, or placed
  in audit metadata.
- A missing limit means unlimited; a limit of `0` means the operation is forbidden. These meanings
  must remain distinct.

## 7. Installation, activation, and lifecycle

The server computes one of the states in `PluginRegistry::STATES`, including `install`, `installed`,
`configure`, `update_available`, `disabled`, `limit_reached`, `dependency_required`,
`sandbox_unavailable`, `not_configured`, and `blocked`. The first failing gate supplies the reason.

Official plugins run in-process after integrity checks. Third-party executable plugins are currently
not installable: `sandbox_available` is false and installation returns `sandbox_unavailable`. The
marketplace/catalog and review pipeline may still represent third-party packages, but packages are
treated as inert data until a real isolation runtime is available.

## 8. Security rules

- Do not load PHP or execute code from an uploaded package.
- Keep provider hosts behind the official allowlist; reject arbitrary user-supplied hosts.
- Validate archive paths, sizes, entry counts, compression ratios, and checksums in `PackageStore`.
- Keep plugin tables tenant-scoped and use the manifest's validated table list for purge operations.
- Never weaken core CRM permissions to make a plugin UI convenient.
- Record lifecycle, settings, connection, secret, usage, and operation changes in the existing audit
  store without secret values.

## 9. UI/UX conventions

Use the existing primitives in `src/components/ui/` and the existing `AppShell` layout. Every plugin
workspace should provide loading, empty, disabled, error, and success states; explain server-provided
reasons; preserve keyboard/focus behavior of the shared modal and form components; and show sandbox or
not-configured status instead of implying a real provider success.

Plugin-specific screens may be added when a plugin has a meaningful workflow (for example Inventory,
Delivery, or Telephony), but shared lifecycle/settings/connection/usage behavior belongs in the
existing Plugin Manager/workspace and must not be duplicated per plugin.

## 10. Testing and diagnostics

Frontend checks:

```text
npm.cmd test -- --run
npx.cmd tsc -p tsconfig.app.json --noEmit
npm.cmd run build
```

Backend checks (from `backend/`, when PHP and Composer are installed):

```text
php artisan test
php artisan test --filter=Plugin
php artisan migrate --force
```

At minimum, test catalog visibility, install/enable/disable, dependency and entitlement failures,
tenant isolation, secret redaction, permission revocation, usage-limit concurrency/idempotency,
third-party sandbox blocking, and the Inventory operation flow. Do not claim a test passed unless it
was actually executed. In environments without PHP/Composer, report that limitation explicitly.

## 11. Known limitations

- This repository contains both a Laravel backend and a legacy Worker backend; plugin changes target
  Laravel only unless a task explicitly requires parity.
- Real Yandex, Uzum, and telephony provider behavior requires configured credentials and approved
  provider endpoints. Sandbox/not-configured states are intentional and must not be represented as
  completed external operations.
- PHP/Composer are required for backend tests and migrations.
- The frontend has dedicated UI for the currently implemented official workflows; new plugins should
  add a focused workspace only when their capabilities cannot be represented by the shared workspace.
