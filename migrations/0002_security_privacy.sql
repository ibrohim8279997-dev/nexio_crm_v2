CREATE TABLE policy_acceptances (
  id TEXT PRIMARY KEY,
  user_id TEXT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
  policy_version TEXT NOT NULL,
  terms_accepted INTEGER NOT NULL DEFAULT 0,
  privacy_accepted INTEGER NOT NULL DEFAULT 0,
  ip TEXT,
  user_agent TEXT,
  accepted_at TEXT NOT NULL
);
CREATE INDEX idx_policy_acceptances_user ON policy_acceptances(user_id, accepted_at);

CREATE TABLE privacy_requests (
  id TEXT PRIMARY KEY,
  user_id TEXT REFERENCES users(id) ON DELETE SET NULL,
  tenant_id TEXT REFERENCES tenants(id) ON DELETE CASCADE,
  kind TEXT NOT NULL,
  status TEXT NOT NULL DEFAULT 'pending',
  requested_at TEXT NOT NULL,
  completed_at TEXT,
  notes TEXT
);
CREATE INDEX idx_privacy_requests_status ON privacy_requests(status, requested_at);

ALTER TABLE consents ADD COLUMN policy_version TEXT;
ALTER TABLE consents ADD COLUMN source TEXT;
ALTER TABLE consents ADD COLUMN actor_id TEXT;
ALTER TABLE consents ADD COLUMN ip TEXT;
ALTER TABLE consents ADD COLUMN expires_at TEXT;
ALTER TABLE consents ADD COLUMN withdrawn_at TEXT;

CREATE TABLE payments_new (
  id TEXT PRIMARY KEY,
  tenant_id TEXT NOT NULL REFERENCES tenants(id) ON DELETE CASCADE,
  invoice_id TEXT REFERENCES invoices(id) ON DELETE SET NULL,
  provider TEXT NOT NULL DEFAULT 'sandbox',
  provider_ref TEXT,
  amount REAL NOT NULL DEFAULT 0,
  currency TEXT NOT NULL DEFAULT 'USD',
  status TEXT NOT NULL DEFAULT 'pending',
  idempotency_key TEXT,
  created_at TEXT NOT NULL
);
INSERT INTO payments_new SELECT id,tenant_id,invoice_id,provider,provider_ref,amount,currency,status,idempotency_key,created_at FROM payments;
DROP TABLE payments;
ALTER TABLE payments_new RENAME TO payments;
CREATE INDEX idx_payments_tenant ON payments(tenant_id);
CREATE UNIQUE INDEX idx_payments_tenant_key ON payments(tenant_id, provider, idempotency_key) WHERE idempotency_key IS NOT NULL;
