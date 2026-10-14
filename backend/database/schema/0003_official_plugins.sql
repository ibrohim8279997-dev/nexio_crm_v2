-- =====================================================================================
-- Nexio Plugin System — data tables owned by the OFFICIAL plugins.
--
-- Additive only. These tables belong to the telephony and delivery plugins; Nexio Core never
-- reads them for its own business logic, so removing the plugins (with data policy = purge)
-- cannot affect orders, clients or any core entity.
--
-- Privacy notes:
--   * telephony_calls stores a REFERENCE to a recording, never audio data.
--   * delivery_shipments stores only the address/contact fields needed to fulfil the order.
-- =====================================================================================

-- ── telephony: numbers provisioned by a provider ───────────────────────────────────
CREATE TABLE telephony_numbers (
  id            TEXT PRIMARY KEY,
  tenant_id     TEXT NOT NULL REFERENCES tenants(id) ON DELETE CASCADE,
  plugin_id     TEXT NOT NULL DEFAULT 'nexio.telephony',
  connection_id TEXT REFERENCES plugin_connections(id) ON DELETE CASCADE,
  phone_number  TEXT NOT NULL,
  provider      TEXT NOT NULL,
  status        TEXT NOT NULL DEFAULT 'active',      -- active | released | pending
  capabilities  TEXT NOT NULL DEFAULT '{}',          -- {inbound,inbound:outbound,recordings}
  created_by    TEXT REFERENCES users(id),
  created_at    TEXT NOT NULL,
  updated_at    TEXT NOT NULL
);
CREATE UNIQUE INDEX idx_telephony_numbers_unique ON telephony_numbers(tenant_id, connection_id, phone_number);
CREATE INDEX idx_telephony_numbers_tenant ON telephony_numbers(tenant_id, status);

-- ── telephony: call log ────────────────────────────────────────────────────────────
CREATE TABLE telephony_calls (
  id               TEXT PRIMARY KEY,
  tenant_id        TEXT NOT NULL REFERENCES tenants(id) ON DELETE CASCADE,
  plugin_id        TEXT NOT NULL DEFAULT 'nexio.telephony',
  connection_id    TEXT REFERENCES plugin_connections(id) ON DELETE SET NULL,
  direction        TEXT NOT NULL,                    -- inbound | outbound
  from_number      TEXT,
  to_number        TEXT,
  contact_id       TEXT REFERENCES contacts(id) ON DELETE SET NULL,
  order_id         TEXT REFERENCES orders(id) ON DELETE SET NULL,
  agent_id         TEXT REFERENCES users(id) ON DELETE SET NULL,
  provider         TEXT NOT NULL,
  provider_call_id TEXT,
  -- 'simulated' means: no provider was configured, so no call reached the PSTN.
  status           TEXT NOT NULL DEFAULT 'queued',
                   -- queued | ringing | in_progress | completed | failed | cancelled | simulated
  provider_configured INTEGER NOT NULL DEFAULT 0,
  started_at       TEXT,
  answered_at      TEXT,
  ended_at         TEXT,
  duration_seconds INTEGER NOT NULL DEFAULT 0,
  recording_ref    TEXT,                             -- reference only, never audio bytes
  transcript_ref   TEXT,                             -- reserved for the future AI Gateway
  hangup_cause     TEXT,
  metadata         TEXT NOT NULL DEFAULT '{}',
  created_by       TEXT REFERENCES users(id),
  created_at       TEXT NOT NULL,
  updated_at       TEXT NOT NULL
);
CREATE INDEX idx_telephony_calls_tenant ON telephony_calls(tenant_id, created_at);
CREATE INDEX idx_telephony_calls_contact ON telephony_calls(tenant_id, contact_id);
CREATE INDEX idx_telephony_calls_order ON telephony_calls(tenant_id, order_id);
CREATE INDEX idx_telephony_calls_agent ON telephony_calls(tenant_id, agent_id);

-- ── delivery: provider status → universal status mapping ───────────────────────────
CREATE TABLE delivery_status_map (
  id              TEXT PRIMARY KEY,
  provider        TEXT NOT NULL,                     -- sandbox | uzum | yandex
  provider_status TEXT NOT NULL,
  universal_status TEXT NOT NULL,
  created_at      TEXT NOT NULL
);
CREATE UNIQUE INDEX idx_delivery_status_map_unique ON delivery_status_map(provider, provider_status);

-- ── delivery: shipments ───────────────────────────────────────────────────────────
CREATE TABLE delivery_shipments (
  id                  TEXT PRIMARY KEY,
  tenant_id           TEXT NOT NULL REFERENCES tenants(id) ON DELETE CASCADE,
  plugin_id           TEXT NOT NULL DEFAULT 'nexio.delivery',
  connection_id       TEXT REFERENCES plugin_connections(id) ON DELETE SET NULL,
  order_id            TEXT REFERENCES orders(id) ON DELETE SET NULL,
  provider            TEXT NOT NULL,
  provider_shipment_id TEXT,
  -- Universal Nexio status; provider-specific values live in provider_status.
  status              TEXT NOT NULL DEFAULT 'pending',
                      -- pending | accepted | courier_assigned | picked_up | in_transit
                      -- | delivered | cancelled | failed
  provider_status     TEXT,
  address_from        TEXT,
  address_to          TEXT,
  recipient_name      TEXT,
  recipient_phone     TEXT,
  courier_name        TEXT,
  courier_phone       TEXT,
  cost                REAL NOT NULL DEFAULT 0,
  currency            TEXT NOT NULL DEFAULT 'UZS',
  tracking_url        TEXT,
  provider_configured INTEGER NOT NULL DEFAULT 0,
  history             TEXT NOT NULL DEFAULT '[]',
  last_error          TEXT,
  created_by          TEXT REFERENCES users(id),
  created_at          TEXT NOT NULL,
  updated_at          TEXT NOT NULL
);
CREATE INDEX idx_delivery_shipments_tenant ON delivery_shipments(tenant_id, created_at);
CREATE INDEX idx_delivery_shipments_order ON delivery_shipments(tenant_id, order_id);
CREATE INDEX idx_delivery_shipments_status ON delivery_shipments(tenant_id, status);

-- Universal statuses seeded so normalisation never depends on application constants alone.
INSERT INTO delivery_status_map (id, provider, provider_status, universal_status, created_at) VALUES
  ('dsm_sandbox_created',   'sandbox', 'created',    'pending',          '2026-01-01 00:00:00'),
  ('dsm_sandbox_accepted',  'sandbox', 'accepted',   'accepted',         '2026-01-01 00:00:00'),
  ('dsm_sandbox_courier',   'sandbox', 'courier',    'courier_assigned', '2026-01-01 00:00:00'),
  ('dsm_sandbox_pickup',    'sandbox', 'picked_up',  'picked_up',        '2026-01-01 00:00:00'),
  ('dsm_sandbox_transit',   'sandbox', 'in_transit', 'in_transit',       '2026-01-01 00:00:00'),
  ('dsm_sandbox_delivered', 'sandbox', 'delivered',  'delivered',        '2026-01-01 00:00:00'),
  ('dsm_sandbox_cancelled', 'sandbox', 'cancelled',  'cancelled',        '2026-01-01 00:00:00'),
  ('dsm_sandbox_failed',    'sandbox', 'failed',     'failed',           '2026-01-01 00:00:00');
