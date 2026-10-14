-- =====================================================================================
-- Nexio Plugin System — DDL (additive only)
--
-- Every statement here is CREATE TABLE / CREATE INDEX. No existing table is altered or
-- dropped, so applying this migration cannot damage the running CRM.
-- SQL is SQLite/D1 compatible (no ALTER, no engine clauses).
--
-- Reused core tables (never duplicated): tenants, users, tenant_memberships, plans,
-- subscriptions, feature_flags, integrations, audit_logs.
--
-- Limit semantics: limit_value IS NULL -> no limit configured for that metric.
--                  limit_value = 0    -> the operation is forbidden.
-- These two states are never conflated.
-- =====================================================================================

-- ── marketplace taxonomy (managed by Super Admin) ───────────────────────────────────
CREATE TABLE plugin_categories (
  id          TEXT PRIMARY KEY,
  code        TEXT NOT NULL UNIQUE,
  name        TEXT NOT NULL,
  description TEXT,
  icon        TEXT,
  position    INTEGER NOT NULL DEFAULT 0,
  is_active   INTEGER NOT NULL DEFAULT 1,
  created_at  TEXT NOT NULL,
  updated_at  TEXT NOT NULL
);

-- ── publisher identity (developer portal accounts) ──────────────────────────────────
CREATE TABLE plugin_developers (
  id              TEXT PRIMARY KEY,
  user_id         TEXT REFERENCES users(id) ON DELETE SET NULL,
  display_name    TEXT NOT NULL,
  email           TEXT NOT NULL UNIQUE COLLATE NOCASE,
  website         TEXT,
  status          TEXT NOT NULL DEFAULT 'pending',  -- pending | approved | suspended
  is_official     INTEGER NOT NULL DEFAULT 0,
  approved_by     TEXT REFERENCES users(id),
  approved_at     TEXT,
  created_at      TEXT NOT NULL,
  updated_at      TEXT NOT NULL
);

-- ── catalog entry (one row per plugin, independent of versions) ────────────────────
CREATE TABLE plugins (
  id                TEXT PRIMARY KEY,               -- plugin id (= manifest id, e.g. nexio.delivery)
  name              TEXT NOT NULL,
  tagline           TEXT,
  description       TEXT,
  type              TEXT NOT NULL DEFAULT 'third_party',  -- official | third_party
  author            TEXT NOT NULL DEFAULT 'Nexio Lab',
  developer_id      TEXT REFERENCES plugin_developers(id) ON DELETE SET NULL,
  category_id       TEXT REFERENCES plugin_categories(id) ON DELETE SET NULL,
  icon              TEXT,
  homepage          TEXT,
  pricing_model     TEXT NOT NULL DEFAULT 'included',     -- included | paid | free
  price_monthly     REAL NOT NULL DEFAULT 0,
  currency          TEXT NOT NULL DEFAULT 'USD',
  visibility        TEXT NOT NULL DEFAULT 'public',  -- public | hidden | unlisted
  is_published      INTEGER NOT NULL DEFAULT 0,      -- at least one PUBLISHED version
  is_deprecated     INTEGER NOT NULL DEFAULT 0,
  is_blocked        INTEGER NOT NULL DEFAULT 0,      -- compromised: hard stop for all tenants
  blocked_reason    TEXT,
  latest_version_id TEXT,
  install_count     INTEGER NOT NULL DEFAULT 0,
  source_path       TEXT,                            -- allowlisted on-disk dir for official plugins
  created_at        TEXT NOT NULL,
  updated_at        TEXT NOT NULL
);
CREATE INDEX idx_plugins_category ON plugins(category_id);
CREATE INDEX idx_plugins_published ON plugins(is_published, visibility);

-- ── immutable version artefacts ────────────────────────────────────────────────────
CREATE TABLE plugin_versions (
  id                TEXT PRIMARY KEY,
  plugin_id         TEXT NOT NULL REFERENCES plugins(id) ON DELETE CASCADE,
  version           TEXT NOT NULL,
  status            TEXT NOT NULL DEFAULT 'DRAFT',
                    -- DRAFT | SUBMITTED | UNDER_REVIEW | TESTING | APPROVED | PUBLISHED | REJECTED | DEPRECATED | RETIRED
  changelog         TEXT,
  manifest          TEXT NOT NULL DEFAULT '{}',      -- validated manifest JSON
  permissions       TEXT NOT NULL DEFAULT '[]',      -- declared permissions, JSON array
  dependencies      TEXT NOT NULL DEFAULT '[]',      -- [{plugin_id, constraint, optional}]
  usage_metrics     TEXT NOT NULL DEFAULT '[]',      -- declared metrics, JSON array
  hooks             TEXT NOT NULL DEFAULT '[]',
  settings_schema   TEXT NOT NULL DEFAULT '{}',
  min_nexio_version TEXT NOT NULL DEFAULT '1.0.0',
  api_version       TEXT NOT NULL DEFAULT '1.0',
  checksum          TEXT,                            -- sha256 of the package
  package_name      TEXT,
  package_size      INTEGER NOT NULL DEFAULT 0,
  package_path      TEXT,                            -- private storage path; never web-served
  has_migrations    INTEGER NOT NULL DEFAULT 0,
  migration_plan    TEXT,
  submitted_by      TEXT REFERENCES users(id),
  submitted_at      TEXT,
  reviewed_by       TEXT REFERENCES users(id),
  reviewed_at       TEXT,
  review_notes      TEXT,
  published_at      TEXT,
  retired_at        TEXT,
  created_by        TEXT REFERENCES users(id),
  created_at        TEXT NOT NULL,
  updated_at        TEXT NOT NULL
);
CREATE UNIQUE INDEX idx_plugin_versions_unique ON plugin_versions(plugin_id, version);
CREATE INDEX idx_plugin_versions_status ON plugin_versions(status);

-- ── review records (one per decision) ──────────────────────────────────────────────
CREATE TABLE plugin_reviews (
  id          TEXT PRIMARY KEY,
  plugin_id   TEXT NOT NULL REFERENCES plugins(id) ON DELETE CASCADE,
  version_id  TEXT NOT NULL REFERENCES plugin_versions(id) ON DELETE CASCADE,
  reviewer_id TEXT REFERENCES users(id),
  decision    TEXT NOT NULL,                         -- testing | approve | reject | request_changes
  notes       TEXT,
  checklist   TEXT NOT NULL DEFAULT '{}',            -- automated check results, JSON
  created_at  TEXT NOT NULL
);

-- ── installation: exactly one per (company, plugin) ────────────────────────────────
CREATE TABLE plugin_installations (
  id                  TEXT PRIMARY KEY,
  tenant_id           TEXT NOT NULL REFERENCES tenants(id) ON DELETE CASCADE,
  plugin_id           TEXT NOT NULL REFERENCES plugins(id) ON DELETE CASCADE,
  version_id          TEXT REFERENCES plugin_versions(id) ON DELETE SET NULL,
  previous_version_id TEXT REFERENCES plugin_versions(id) ON DELETE SET NULL,
  status              TEXT NOT NULL DEFAULT 'installed',
                      -- installed | enabled | disabled | uninstalled (archived: data retained)
                      -- 'uninstalled' keeps the row so the "retain" data policy can actually preserve
                      -- settings/connections, which cascade from this row. Every consumer must filter it out.
  runtime_mode        TEXT NOT NULL DEFAULT 'in_process',-- in_process | sandbox
  installed_by        TEXT REFERENCES users(id),
  installed_at        TEXT NOT NULL,
  enabled_at          TEXT,
  disabled_at         TEXT,
  update_available    INTEGER NOT NULL DEFAULT 0,
  last_error          TEXT,
  created_at          TEXT NOT NULL,
  updated_at          TEXT NOT NULL
);
CREATE UNIQUE INDEX idx_plugin_installations_unique ON plugin_installations(tenant_id, plugin_id);
CREATE INDEX idx_plugin_installations_tenant ON plugin_installations(tenant_id, status);

-- ── connections: a plugin may hold several accounts (e.g. two SIP trunks) ──────────
CREATE TABLE plugin_connections (
  id              TEXT PRIMARY KEY,
  tenant_id       TEXT NOT NULL REFERENCES tenants(id) ON DELETE CASCADE,
  plugin_id       TEXT NOT NULL REFERENCES plugins(id) ON DELETE CASCADE,
  installation_id TEXT NOT NULL REFERENCES plugin_installations(id) ON DELETE CASCADE,
  name            TEXT NOT NULL,
  provider        TEXT NOT NULL,                     -- e.g. sandbox | uzum | yandex
  status          TEXT NOT NULL DEFAULT 'not_configured',
                  -- not_configured | sandbox | connected | error | revoked
  mode            TEXT NOT NULL DEFAULT 'sandbox',   -- sandbox | live
  config          TEXT NOT NULL DEFAULT '{}',        -- NON-secret configuration only
  last_error      TEXT,
  last_checked_at TEXT,
  connected_at    TEXT,
  created_by      TEXT REFERENCES users(id),
  created_at      TEXT NOT NULL,
  updated_at      TEXT NOT NULL
);
CREATE UNIQUE INDEX idx_plugin_connections_unique ON plugin_connections(installation_id, name);
CREATE INDEX idx_plugin_connections_tenant ON plugin_connections(tenant_id, plugin_id);

-- ── non-secret per-installation settings ───────────────────────────────────────────
CREATE TABLE plugin_settings (
  id              TEXT PRIMARY KEY,
  tenant_id       TEXT NOT NULL REFERENCES tenants(id) ON DELETE CASCADE,
  plugin_id       TEXT NOT NULL REFERENCES plugins(id) ON DELETE CASCADE,
  installation_id TEXT NOT NULL REFERENCES plugin_installations(id) ON DELETE CASCADE,
  connection_id   TEXT REFERENCES plugin_connections(id) ON DELETE CASCADE,
  key             TEXT NOT NULL,
  value           TEXT,
  value_type      TEXT NOT NULL DEFAULT 'string',    -- string | number | boolean | json
  updated_by      TEXT REFERENCES users(id),
  created_at      TEXT NOT NULL,
  updated_at      TEXT NOT NULL
);
CREATE UNIQUE INDEX idx_plugin_settings_key ON plugin_settings(installation_id, key);

-- ── encrypted credentials (ciphertext only; plaintext never stored or returned) ────
CREATE TABLE plugin_secrets (
  id            TEXT PRIMARY KEY,
  tenant_id     TEXT NOT NULL REFERENCES tenants(id) ON DELETE CASCADE,
  plugin_id     TEXT NOT NULL REFERENCES plugins(id) ON DELETE CASCADE,
  connection_id TEXT REFERENCES plugin_connections(id) ON DELETE CASCADE,
  key           TEXT NOT NULL,
  ciphertext    TEXT NOT NULL,                       -- Laravel Crypt (AES-256-GCM)
  key_version   INTEGER NOT NULL DEFAULT 1,
  fingerprint   TEXT,                                -- sha256 of value, for rotation checks
  created_by    TEXT REFERENCES users(id),
  rotated_at    TEXT,
  created_at    TEXT NOT NULL,
  updated_at    TEXT NOT NULL
);
CREATE UNIQUE INDEX idx_plugin_secrets_key
  ON plugin_secrets(plugin_id, COALESCE(connection_id,''), key);
CREATE INDEX idx_plugin_secrets_tenant ON plugin_secrets(tenant_id, plugin_id);

-- ── platform-level permission grant (Super Admin approves declared permissions) ────
CREATE TABLE plugin_permissions (
  id            TEXT PRIMARY KEY,
  plugin_id     TEXT NOT NULL REFERENCES plugins(id) ON DELETE CASCADE,
  permission    TEXT NOT NULL,
  scope         TEXT NOT NULL DEFAULT 'tenant',      -- tenant | installation
  is_declared   INTEGER NOT NULL DEFAULT 1,          -- declared in manifest
  is_granted    INTEGER NOT NULL DEFAULT 0,          -- approved by Super Admin
  is_sensitive  INTEGER NOT NULL DEFAULT 0,          -- e.g. export.data, crm.*.read bulk
  generation    INTEGER NOT NULL DEFAULT 1,          -- bumped on change -> invalidates caches
  granted_by    TEXT REFERENCES users(id),
  granted_at    TEXT,
  revoked_at    TEXT,
  created_at    TEXT NOT NULL,
  updated_at    TEXT NOT NULL
);
CREATE UNIQUE INDEX idx_plugin_permissions_unique ON plugin_permissions(plugin_id, permission);

-- ── declared dependencies between plugins ─────────────────────────────────────────
CREATE TABLE plugin_dependencies (
  id                  TEXT PRIMARY KEY,
  plugin_id           TEXT NOT NULL REFERENCES plugins(id) ON DELETE CASCADE,
  depends_on_plugin_id TEXT NOT NULL REFERENCES plugins(id) ON DELETE CASCADE,
  version_constraint  TEXT NOT NULL DEFAULT '*',
  is_optional         INTEGER NOT NULL DEFAULT 0,
  created_at          TEXT NOT NULL
);
CREATE UNIQUE INDEX idx_plugin_dependencies_unique ON plugin_dependencies(plugin_id, depends_on_plugin_id);

-- ── plan access rules (NO plan logic is hardcoded in PHP) ──────────────────────────
-- plan_id IS NULL = platform default policy, applied when no plan-specific row exists.
CREATE TABLE plugin_entitlements (
  id                  TEXT PRIMARY KEY,
  plugin_id           TEXT REFERENCES plugins(id) ON DELETE CASCADE, -- NULL = applies to all plugins
  plan_id             TEXT REFERENCES plans(id) ON DELETE CASCADE,   -- NULL = default policy
  can_install         INTEGER,                       -- 1 allow, 0 deny, NULL = fall through
  can_use             INTEGER,
  max_installations   INTEGER,                       -- NULL = unlimited, 0 = none
  max_connections     INTEGER,
  trial_allowed       INTEGER NOT NULL DEFAULT 1,
  is_enabled          INTEGER NOT NULL DEFAULT 1,
  notes               TEXT,
  updated_by          TEXT REFERENCES users(id),
  created_at          TEXT NOT NULL,
  updated_at          TEXT NOT NULL
);
-- Expression index: SQLite treats NULLs as distinct in UNIQUE indexes, which would allow
-- duplicate "default policy" rows (plan_id IS NULL). COALESCE makes the constraint real.
CREATE UNIQUE INDEX idx_plugin_entitlements_unique
  ON plugin_entitlements(COALESCE(plugin_id,''), COALESCE(plan_id,''));

-- ── named feature rules per plan ───────────────────────────────────────────────────
CREATE TABLE plugin_feature_rules (
  id          TEXT PRIMARY KEY,
  plugin_id   TEXT NOT NULL REFERENCES plugins(id) ON DELETE CASCADE,
  plan_id     TEXT REFERENCES plans(id) ON DELETE CASCADE,
  feature_key TEXT NOT NULL,
  is_enabled  INTEGER NOT NULL DEFAULT 0,
  config      TEXT NOT NULL DEFAULT '{}',
  updated_by  TEXT REFERENCES users(id),
  created_at  TEXT NOT NULL,
  updated_at  TEXT NOT NULL
);
CREATE UNIQUE INDEX idx_plugin_feature_rules_unique
  ON plugin_feature_rules(plugin_id, COALESCE(plan_id,''), feature_key);

-- ── usage limits per plan / metric / period, with enforcement policy ───────────────
CREATE TABLE plugin_limits (
  id                 TEXT PRIMARY KEY,
  plugin_id          TEXT NOT NULL REFERENCES plugins(id) ON DELETE CASCADE,
  plan_id            TEXT REFERENCES plans(id) ON DELETE CASCADE, -- NULL = default policy
  metric             TEXT NOT NULL,
  period             TEXT NOT NULL DEFAULT 'monthly',
                     -- daily | weekly | monthly | total | custom
                     -- (per_user / per_connection / per_company are accepted as shorthands and
                     --  normalised into scope=user|connection|company with period=total)
  scope              TEXT NOT NULL DEFAULT 'company',  -- company | user | connection
  period_days        INTEGER,                        -- only for period='custom'
  limit_value        INTEGER,                        -- NULL = no limit, 0 = forbidden
  enforcement_policy TEXT NOT NULL DEFAULT 'BLOCK',  -- BLOCK | WARN | ALLOW_OVERAGE
  is_enabled         INTEGER NOT NULL DEFAULT 1,
  unit               TEXT,
  notes              TEXT,                        -- operator-facing rationale for this rule
  updated_by         TEXT REFERENCES users(id),
  created_at         TEXT NOT NULL,
  updated_at         TEXT NOT NULL
);
CREATE UNIQUE INDEX idx_plugin_limits_unique
  ON plugin_limits(plugin_id, COALESCE(plan_id,''), metric, period, scope);

-- ── append-only usage ledger (idempotency guaranteed by unique key) ────────────────
CREATE TABLE plugin_usage (
  id              TEXT PRIMARY KEY,
  tenant_id       TEXT NOT NULL REFERENCES tenants(id) ON DELETE CASCADE,
  plugin_id       TEXT NOT NULL REFERENCES plugins(id) ON DELETE CASCADE,
  installation_id TEXT REFERENCES plugin_installations(id) ON DELETE SET NULL,
  connection_id   TEXT REFERENCES plugin_connections(id) ON DELETE SET NULL,
  metric          TEXT NOT NULL,
  amount          REAL NOT NULL DEFAULT 0,
  period          TEXT NOT NULL,
  dimension_key   TEXT NOT NULL DEFAULT '',        -- user id / connection id for per_user, per_connection
  period_start    TEXT NOT NULL,
  period_end      TEXT NOT NULL,
  actor_id        TEXT REFERENCES users(id),
  idempotency_key TEXT NOT NULL,
  status          TEXT NOT NULL DEFAULT 'confirmed', -- pending (reserved) | confirmed | released
  is_overage      INTEGER NOT NULL DEFAULT 0,
  metadata        TEXT,
  result          TEXT,                                -- redacted result of a successful call, replayed on a retry
  created_at      TEXT NOT NULL
);
CREATE UNIQUE INDEX idx_plugin_usage_idem ON plugin_usage(tenant_id, plugin_id, metric, idempotency_key);
CREATE INDEX idx_plugin_usage_period ON plugin_usage(tenant_id, plugin_id, metric, period_start);

-- ── atomic counters, updated with a conditional UPDATE so concurrency cannot bypass ─
CREATE TABLE plugin_usage_counters (
  id              TEXT PRIMARY KEY,
  tenant_id       TEXT NOT NULL REFERENCES tenants(id) ON DELETE CASCADE,
  plugin_id       TEXT NOT NULL REFERENCES plugins(id) ON DELETE CASCADE,
  installation_id TEXT REFERENCES plugin_installations(id) ON DELETE SET NULL,
  metric          TEXT NOT NULL,
  period          TEXT NOT NULL,
  dimension_key   TEXT NOT NULL DEFAULT '',        -- '' for company-wide; user/connection id otherwise
  period_start    TEXT NOT NULL,
  period_end      TEXT NOT NULL,
  used            REAL NOT NULL DEFAULT 0,
  overage         REAL NOT NULL DEFAULT 0,
  updated_at      TEXT NOT NULL
);
CREATE UNIQUE INDEX idx_plugin_usage_counters_unique
  ON plugin_usage_counters(tenant_id, plugin_id, metric, period, dimension_key, period_start);

-- ── event outbox: tenant-scoped, retried, dead-lettered ────────────────────────────
CREATE TABLE plugin_events (
  id              TEXT PRIMARY KEY,
  tenant_id       TEXT NOT NULL REFERENCES tenants(id) ON DELETE CASCADE,
  plugin_id       TEXT NOT NULL REFERENCES plugins(id) ON DELETE CASCADE,
  installation_id TEXT REFERENCES plugin_installations(id) ON DELETE CASCADE,
  event           TEXT NOT NULL,
  payload         TEXT NOT NULL DEFAULT '{}',        -- minimised, redacted
  status          TEXT NOT NULL DEFAULT 'pending',   -- pending | delivered | failed | dead
  attempts        INTEGER NOT NULL DEFAULT 0,
  max_attempts    INTEGER NOT NULL DEFAULT 5,
  available_at    TEXT,
  last_error      TEXT,
  idempotency_key TEXT NOT NULL,
  created_at      TEXT NOT NULL,
  processed_at    TEXT
);
CREATE UNIQUE INDEX idx_plugin_events_idem ON plugin_events(tenant_id, plugin_id, event, idempotency_key);
CREATE INDEX idx_plugin_events_pending ON plugin_events(status, available_at);

-- ── plugin event subscriptions ────────────────────────────────────────────────────
CREATE TABLE plugin_subscriptions (
  id              TEXT PRIMARY KEY,
  tenant_id       TEXT NOT NULL REFERENCES tenants(id) ON DELETE CASCADE,
  plugin_id       TEXT NOT NULL REFERENCES plugins(id) ON DELETE CASCADE,
  installation_id TEXT NOT NULL REFERENCES plugin_installations(id) ON DELETE CASCADE,
  event           TEXT NOT NULL,
  is_enabled      INTEGER NOT NULL DEFAULT 1,
  last_error      TEXT,
  created_at      TEXT NOT NULL,
  updated_at      TEXT NOT NULL
);
CREATE UNIQUE INDEX idx_plugin_subscriptions_unique ON plugin_subscriptions(installation_id, event);
CREATE INDEX idx_plugin_subscriptions_event ON plugin_subscriptions(event, is_enabled);
