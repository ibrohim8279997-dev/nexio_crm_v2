-- Nexio CRM · migration 0001 — platform, CRM, billing, integrations, AI.
-- Every tenant-owned row carries tenant_id; tenant context is always derived
-- server-side from the verified session, never from client input.

PRAGMA foreign_keys = ON;

-- ─── Platform ────────────────────────────────────────────────────────────────
CREATE TABLE users (
  id            TEXT PRIMARY KEY,
  email         TEXT NOT NULL UNIQUE COLLATE NOCASE,
  password_hash TEXT NOT NULL,
  name          TEXT NOT NULL,
  phone         TEXT,
  avatar_color  TEXT NOT NULL DEFAULT '#2563EB',
  is_platform_admin INTEGER NOT NULL DEFAULT 0,
  platform_role TEXT,                       -- platform_superadmin | platform_support | platform_finance
  mfa_enabled   INTEGER NOT NULL DEFAULT 0,
  last_login_at TEXT,
  created_at    TEXT NOT NULL,
  updated_at    TEXT NOT NULL
);

CREATE TABLE tenants (
  id            TEXT PRIMARY KEY,
  name          TEXT NOT NULL,
  slug          TEXT NOT NULL UNIQUE COLLATE NOCASE,
  legal_name    TEXT,
  logo_text     TEXT,
  brand_color   TEXT NOT NULL DEFAULT '#2563EB',
  locale        TEXT NOT NULL DEFAULT 'ru',
  timezone      TEXT NOT NULL DEFAULT 'Asia/Tashkent',
  currency      TEXT NOT NULL DEFAULT 'UZS',
  country       TEXT NOT NULL DEFAULT 'UZ',
  industry      TEXT,
  status        TEXT NOT NULL DEFAULT 'trial',  -- trial | active | past_due | suspended | cancelled
  plan_id       TEXT REFERENCES plans(id),
  trial_ends_at TEXT,
  created_at    TEXT NOT NULL,
  updated_at    TEXT NOT NULL
);

CREATE TABLE tenant_memberships (
  id          TEXT PRIMARY KEY,
  tenant_id   TEXT NOT NULL REFERENCES tenants(id) ON DELETE CASCADE,
  user_id     TEXT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
  role        TEXT NOT NULL,                -- tenant_owner | tenant_admin | sales_manager | sales_agent |
                                            -- team_lead | project_manager | developer | support_agent |
                                            -- finance_manager | analyst | employee | read_only | client_portal_user
  department  TEXT,
  title       TEXT,
  status      TEXT NOT NULL DEFAULT 'active',  -- active | invited | suspended
  org_id      TEXT,                          -- customer organization for client_portal_user (validated at write time)
  invited_by  TEXT REFERENCES users(id),
  created_at  TEXT NOT NULL,
  UNIQUE (tenant_id, user_id)
);
CREATE INDEX idx_memberships_user ON tenant_memberships(user_id);
CREATE INDEX idx_memberships_tenant ON tenant_memberships(tenant_id);

CREATE TABLE sessions (
  id         TEXT PRIMARY KEY,
  user_id    TEXT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
  tenant_id  TEXT REFERENCES tenants(id) ON DELETE CASCADE,
  ip         TEXT,
  user_agent TEXT,
  expires_at TEXT NOT NULL,
  created_at TEXT NOT NULL
);
CREATE INDEX idx_sessions_user ON sessions(user_id);

CREATE TABLE invitations (
  id         TEXT PRIMARY KEY,
  tenant_id  TEXT REFERENCES tenants(id) ON DELETE CASCADE,  -- nullable: password-reset tokens are tenant-less
  email      TEXT NOT NULL,
  role       TEXT NOT NULL,
  org_id     TEXT,                          -- customer organization for client_portal_user
  token      TEXT NOT NULL UNIQUE,
  invited_by TEXT REFERENCES users(id),
  status     TEXT NOT NULL DEFAULT 'pending',  -- pending | accepted | revoked | expired
  expires_at TEXT NOT NULL,
  created_at TEXT NOT NULL
);

CREATE TABLE audit_logs (
  id         TEXT PRIMARY KEY,
  tenant_id  TEXT REFERENCES tenants(id) ON DELETE CASCADE,
  actor_id   TEXT REFERENCES users(id),
  actor_name TEXT,
  action     TEXT NOT NULL,
  target     TEXT,
  target_id  TEXT,
  meta       TEXT,                          -- JSON, secrets are never written here
  ip         TEXT,
  created_at TEXT NOT NULL
);
CREATE INDEX idx_audit_tenant ON audit_logs(tenant_id, created_at);

-- ─── CRM: accounts, people, leads, pipelines, deals ──────────────────────────
CREATE TABLE organizations (
  id          TEXT PRIMARY KEY,
  tenant_id   TEXT NOT NULL REFERENCES tenants(id) ON DELETE CASCADE,
  name        TEXT NOT NULL,
  industry    TEXT,
  website     TEXT,
  email       TEXT,
  phone       TEXT,
  country     TEXT,
  city        TEXT,
  address     TEXT,
  size        TEXT,
  status      TEXT NOT NULL DEFAULT 'active',  -- active | prospect | churned
  tier        TEXT NOT NULL DEFAULT 'standard',-- vip | standard | basic
  owner_id    TEXT REFERENCES users(id),
  tags        TEXT NOT NULL DEFAULT '[]',
  notes       TEXT,
  created_at  TEXT NOT NULL,
  updated_at  TEXT NOT NULL
);
CREATE INDEX idx_orgs_tenant ON organizations(tenant_id, created_at);

CREATE TABLE contacts (
  id          TEXT PRIMARY KEY,
  tenant_id   TEXT NOT NULL REFERENCES tenants(id) ON DELETE CASCADE,
  org_id      TEXT REFERENCES organizations(id) ON DELETE SET NULL,
  first_name  TEXT NOT NULL,
  last_name   TEXT,
  position    TEXT,
  email       TEXT,
  phone       TEXT,
  telegram    TEXT,
  source      TEXT,
  status      TEXT NOT NULL DEFAULT 'active',  -- active | lead | inactive | do_not_contact
  is_primary  INTEGER NOT NULL DEFAULT 0,
  consent_marketing INTEGER NOT NULL DEFAULT 0,
  consent_calls     INTEGER NOT NULL DEFAULT 0,
  owner_id    TEXT REFERENCES users(id),
  tags        TEXT NOT NULL DEFAULT '[]',
  created_at  TEXT NOT NULL,
  updated_at  TEXT NOT NULL
);
CREATE INDEX idx_contacts_tenant ON contacts(tenant_id, created_at);
CREATE INDEX idx_contacts_org ON contacts(org_id);

CREATE TABLE pipelines (
  id         TEXT PRIMARY KEY,
  tenant_id  TEXT NOT NULL REFERENCES tenants(id) ON DELETE CASCADE,
  name       TEXT NOT NULL,
  is_default INTEGER NOT NULL DEFAULT 0,
  created_at TEXT NOT NULL
);

CREATE TABLE pipeline_stages (
  id          TEXT PRIMARY KEY,
  tenant_id   TEXT NOT NULL REFERENCES tenants(id) ON DELETE CASCADE,
  pipeline_id TEXT NOT NULL REFERENCES pipelines(id) ON DELETE CASCADE,
  key         TEXT NOT NULL,
  name        TEXT NOT NULL,
  position    INTEGER NOT NULL,
  probability INTEGER NOT NULL DEFAULT 0,
  kind        TEXT NOT NULL DEFAULT 'open',    -- open | won | lost
  color       TEXT NOT NULL DEFAULT '#2563EB'
);
CREATE INDEX idx_stages_pipeline ON pipeline_stages(pipeline_id, position);

CREATE TABLE leads (
  id          TEXT PRIMARY KEY,
  tenant_id   TEXT NOT NULL REFERENCES tenants(id) ON DELETE CASCADE,
  title       TEXT NOT NULL,
  contact_name TEXT,
  org_id      TEXT REFERENCES organizations(id) ON DELETE SET NULL,
  email       TEXT,
  phone       TEXT,
  source      TEXT,                            -- website | telegram | referral | cold_call | api | campaign
  status      TEXT NOT NULL DEFAULT 'new',     -- new | contacted | qualified | converted | disqualified
  score       INTEGER NOT NULL DEFAULT 0,
  value       REAL NOT NULL DEFAULT 0,
  currency    TEXT NOT NULL DEFAULT 'UZS',
  owner_id    TEXT REFERENCES users(id),
  notes       TEXT,
  converted_deal_id TEXT,
  created_at  TEXT NOT NULL,
  updated_at  TEXT NOT NULL
);
CREATE INDEX idx_leads_tenant ON leads(tenant_id, created_at);
CREATE INDEX idx_leads_status ON leads(tenant_id, status);

CREATE TABLE deals (
  id            TEXT PRIMARY KEY,
  tenant_id     TEXT NOT NULL REFERENCES tenants(id) ON DELETE CASCADE,
  title         TEXT NOT NULL,
  org_id        TEXT REFERENCES organizations(id) ON DELETE SET NULL,
  contact_id    TEXT REFERENCES contacts(id) ON DELETE SET NULL,
  pipeline_id   TEXT REFERENCES pipelines(id),
  stage_id      TEXT REFERENCES pipeline_stages(id),
  amount        REAL NOT NULL DEFAULT 0,
  currency      TEXT NOT NULL DEFAULT 'UZS',
  probability   INTEGER NOT NULL DEFAULT 10,
  status        TEXT NOT NULL DEFAULT 'open',   -- open | won | lost
  source        TEXT,
  owner_id      TEXT REFERENCES users(id),
  expected_close_at TEXT,
  won_at        TEXT,
  lost_at       TEXT,
  lost_reason   TEXT,
  is_ai_sourced INTEGER NOT NULL DEFAULT 0,
  created_at    TEXT NOT NULL,
  updated_at    TEXT NOT NULL
);
CREATE INDEX idx_deals_tenant ON deals(tenant_id, created_at);
CREATE INDEX idx_deals_stage ON deals(tenant_id, stage_id);
CREATE INDEX idx_deals_owner ON deals(tenant_id, owner_id);

CREATE TABLE activities (
  id         TEXT PRIMARY KEY,
  tenant_id  TEXT NOT NULL REFERENCES tenants(id) ON DELETE CASCADE,
  kind       TEXT NOT NULL,        -- call | email | note | stage_change | task | meeting | system | ai_call
  subject    TEXT NOT NULL,
  body       TEXT,
  entity_type TEXT,                -- deal | lead | organization | contact | order | project
  entity_id  TEXT,
  actor_id   TEXT REFERENCES users(id),
  actor_name TEXT,
  meta       TEXT,
  created_at TEXT NOT NULL
);
CREATE INDEX idx_activities_entity ON activities(tenant_id, entity_type, entity_id);
CREATE INDEX idx_activities_tenant ON activities(tenant_id, created_at);

-- ─── Catalog, orders, projects, tasks ───────────────────────────────────────
CREATE TABLE products (
  id          TEXT PRIMARY KEY,
  tenant_id   TEXT NOT NULL REFERENCES tenants(id) ON DELETE CASCADE,
  name        TEXT NOT NULL,
  sku         TEXT,
  category    TEXT,
  description TEXT,
  price       REAL NOT NULL DEFAULT 0,
  cost        REAL NOT NULL DEFAULT 0,
  currency    TEXT NOT NULL DEFAULT 'UZS',
  billing_type TEXT NOT NULL DEFAULT 'one_time',  -- one_time | monthly | hourly
  is_active   INTEGER NOT NULL DEFAULT 1,
  created_at  TEXT NOT NULL
);
CREATE INDEX idx_products_tenant ON products(tenant_id);

CREATE TABLE orders (
  id           TEXT PRIMARY KEY,
  tenant_id    TEXT NOT NULL REFERENCES tenants(id) ON DELETE CASCADE,
  number       TEXT NOT NULL,
  org_id       TEXT REFERENCES organizations(id) ON DELETE SET NULL,
  contact_id   TEXT REFERENCES contacts(id) ON DELETE SET NULL,
  deal_id      TEXT REFERENCES deals(id) ON DELETE SET NULL,
  project_id   TEXT,
  status       TEXT NOT NULL DEFAULT 'draft', -- draft | confirmed | in_progress | fulfilled | cancelled | refunded
  amount       REAL NOT NULL DEFAULT 0,
  currency     TEXT NOT NULL DEFAULT 'UZS',
  source       TEXT NOT NULL DEFAULT 'manual',-- manual | integration | portal | ai_agent
  payment_status TEXT NOT NULL DEFAULT 'unpaid', -- unpaid | partial | paid | overdue
  confirmed_at TEXT,
  snapshot     TEXT,                            -- frozen terms/price at confirmation
  notes        TEXT,
  created_by   TEXT REFERENCES users(id),
  created_at   TEXT NOT NULL,
  updated_at   TEXT NOT NULL
);
CREATE INDEX idx_orders_tenant ON orders(tenant_id, created_at);

CREATE TABLE order_items (
  id         TEXT PRIMARY KEY,
  tenant_id  TEXT NOT NULL REFERENCES tenants(id) ON DELETE CASCADE,
  order_id   TEXT NOT NULL REFERENCES orders(id) ON DELETE CASCADE,
  product_id TEXT REFERENCES products(id),
  name       TEXT NOT NULL,
  qty        REAL NOT NULL DEFAULT 1,
  price      REAL NOT NULL DEFAULT 0,
  total      REAL NOT NULL DEFAULT 0
);
CREATE INDEX idx_order_items_order ON order_items(order_id);

CREATE TABLE projects (
  id          TEXT PRIMARY KEY,
  tenant_id   TEXT NOT NULL REFERENCES tenants(id) ON DELETE CASCADE,
  name        TEXT NOT NULL,
  org_id      TEXT REFERENCES organizations(id) ON DELETE SET NULL,
  order_id    TEXT REFERENCES orders(id) ON DELETE SET NULL,
  status      TEXT NOT NULL DEFAULT 'planning', -- planning | active | on_hold | review | done | cancelled
  priority    TEXT NOT NULL DEFAULT 'medium',   -- low | medium | high | critical
  progress    INTEGER NOT NULL DEFAULT 0,
  budget      REAL NOT NULL DEFAULT 0,
  currency    TEXT NOT NULL DEFAULT 'UZS',
  manager_id  TEXT REFERENCES users(id),
  start_date  TEXT,
  due_date    TEXT,
  description TEXT,
  created_at  TEXT NOT NULL,
  updated_at  TEXT NOT NULL
);
CREATE INDEX idx_projects_tenant ON projects(tenant_id, created_at);

CREATE TABLE project_milestones (
  id         TEXT PRIMARY KEY,
  tenant_id  TEXT NOT NULL REFERENCES tenants(id) ON DELETE CASCADE,
  project_id TEXT NOT NULL REFERENCES projects(id) ON DELETE CASCADE,
  name       TEXT NOT NULL,
  status     TEXT NOT NULL DEFAULT 'pending',  -- pending | in_progress | done
  due_date   TEXT,
  position   INTEGER NOT NULL DEFAULT 0
);

CREATE TABLE tasks (
  id          TEXT PRIMARY KEY,
  tenant_id   TEXT NOT NULL REFERENCES tenants(id) ON DELETE CASCADE,
  title       TEXT NOT NULL,
  description TEXT,
  project_id  TEXT REFERENCES projects(id) ON DELETE CASCADE,
  entity_type TEXT,
  entity_id   TEXT,
  assignee_id TEXT REFERENCES users(id),
  status      TEXT NOT NULL DEFAULT 'todo',   -- todo | in_progress | review | done
  priority    TEXT NOT NULL DEFAULT 'medium', -- low | medium | high | critical
  due_date    TEXT,
  completed_at TEXT,
  created_by  TEXT REFERENCES users(id),
  created_at  TEXT NOT NULL,
  updated_at  TEXT NOT NULL
);
CREATE INDEX idx_tasks_tenant ON tasks(tenant_id, status);
CREATE INDEX idx_tasks_assignee ON tasks(tenant_id, assignee_id);

CREATE TABLE tickets (
  id          TEXT PRIMARY KEY,
  tenant_id   TEXT NOT NULL REFERENCES tenants(id) ON DELETE CASCADE,
  number      TEXT NOT NULL,
  subject     TEXT NOT NULL,
  org_id      TEXT REFERENCES organizations(id) ON DELETE SET NULL,
  requester   TEXT,
  channel     TEXT NOT NULL DEFAULT 'email',  -- email | telegram | portal | phone
  priority    TEXT NOT NULL DEFAULT 'normal', -- low | normal | high | urgent
  status      TEXT NOT NULL DEFAULT 'open',   -- open | pending | resolved | closed
  sla_due_at  TEXT,
  assignee_id TEXT REFERENCES users(id),
  body        TEXT,
  created_at  TEXT NOT NULL,
  updated_at  TEXT NOT NULL
);
CREATE INDEX idx_tickets_tenant ON tickets(tenant_id, status);

CREATE TABLE communications (
  id         TEXT PRIMARY KEY,
  tenant_id  TEXT NOT NULL REFERENCES tenants(id) ON DELETE CASCADE,
  channel    TEXT NOT NULL,     -- email | telegram | whatsapp | phone | portal
  direction  TEXT NOT NULL,     -- inbound | outbound
  subject    TEXT,
  body       TEXT,
  org_id     TEXT REFERENCES organizations(id) ON DELETE SET NULL,
  contact_id TEXT REFERENCES contacts(id) ON DELETE SET NULL,
  user_id    TEXT REFERENCES users(id),
  status     TEXT NOT NULL DEFAULT 'sent',
  created_at TEXT NOT NULL
);
CREATE INDEX idx_comms_tenant ON communications(tenant_id, created_at);

CREATE TABLE documents (
  id         TEXT PRIMARY KEY,
  tenant_id  TEXT NOT NULL REFERENCES tenants(id) ON DELETE CASCADE,
  name       TEXT NOT NULL,
  kind       TEXT NOT NULL DEFAULT 'other',  -- proposal | contract | invoice | act | other
  org_id     TEXT REFERENCES organizations(id) ON DELETE SET NULL,
  order_id   TEXT REFERENCES orders(id) ON DELETE SET NULL,
  version    INTEGER NOT NULL DEFAULT 1,
  size_kb    INTEGER NOT NULL DEFAULT 0,
  status     TEXT NOT NULL DEFAULT 'draft',  -- draft | sent | signed | archived
  created_by TEXT REFERENCES users(id),
  created_at TEXT NOT NULL
);
CREATE INDEX idx_documents_tenant ON documents(tenant_id);

CREATE TABLE automations (
  id          TEXT PRIMARY KEY,
  tenant_id   TEXT NOT NULL REFERENCES tenants(id) ON DELETE CASCADE,
  name        TEXT NOT NULL,
  trigger_key TEXT NOT NULL,           -- lead.created | deal.won | order.paid | task.overdue | no_reply.2d
  conditions  TEXT NOT NULL DEFAULT '[]',
  actions     TEXT NOT NULL DEFAULT '[]',
  status      TEXT NOT NULL DEFAULT 'active', -- active | paused
  runs_count  INTEGER NOT NULL DEFAULT 0,
  last_run_at TEXT,
  created_at  TEXT NOT NULL
);
CREATE INDEX idx_automations_tenant ON automations(tenant_id);

CREATE TABLE integrations (
  id           TEXT PRIMARY KEY,
  tenant_id    TEXT NOT NULL REFERENCES tenants(id) ON DELETE CASCADE,
  provider     TEXT NOT NULL,                 -- telegram | email_smtp | whatsapp | telephony | web_form | payments | webhook | analytics
  name         TEXT NOT NULL,
  status       TEXT NOT NULL DEFAULT 'available', -- available | connected | sandbox | error
  mode         TEXT NOT NULL DEFAULT 'sandbox',   -- sandbox | live
  config       TEXT NOT NULL DEFAULT '{}',        -- non-secret config only
  last_sync_at TEXT,
  last_error   TEXT,
  connected_at TEXT,
  created_at   TEXT NOT NULL
);
CREATE INDEX idx_integrations_tenant ON integrations(tenant_id);

CREATE TABLE webhook_events (
  id         TEXT PRIMARY KEY,
  tenant_id  TEXT REFERENCES tenants(id) ON DELETE CASCADE,
  event      TEXT NOT NULL,
  payload    TEXT,
  status     TEXT NOT NULL DEFAULT 'delivered', -- delivered | failed | retrying
  attempts   INTEGER NOT NULL DEFAULT 1,
  created_at TEXT NOT NULL
);

CREATE TABLE nexio_service_requests (
  id          TEXT PRIMARY KEY,
  tenant_id   TEXT NOT NULL REFERENCES tenants(id) ON DELETE CASCADE,
  service     TEXT NOT NULL,   -- website | crm | android | windows | api | ai_agent | support
  title       TEXT NOT NULL,
  description TEXT,
  budget      REAL,
  currency    TEXT NOT NULL DEFAULT 'UZS',
  status      TEXT NOT NULL DEFAULT 'submitted', -- submitted | in_review | accepted | in_progress | delivered | declined
  priority    TEXT NOT NULL DEFAULT 'normal',
  assignee_id TEXT REFERENCES users(id),
  created_by  TEXT REFERENCES users(id),
  created_at  TEXT NOT NULL,
  updated_at  TEXT NOT NULL
);
CREATE INDEX idx_nexio_req ON nexio_service_requests(tenant_id, created_at);

-- ─── Billing (Nexio Billing) ────────────────────────────────────────────────
CREATE TABLE plans (
  id            TEXT PRIMARY KEY,
  code          TEXT NOT NULL UNIQUE,
  name          TEXT NOT NULL,
  tagline       TEXT,
  price_monthly REAL NOT NULL DEFAULT 0,
  price_yearly  REAL NOT NULL DEFAULT 0,
  currency      TEXT NOT NULL DEFAULT 'USD',
  seats         INTEGER NOT NULL DEFAULT 3,
  api_requests  INTEGER NOT NULL DEFAULT 10000,
  ai_minutes    INTEGER NOT NULL DEFAULT 0,
  storage_gb    INTEGER NOT NULL DEFAULT 5,
  modules       TEXT NOT NULL DEFAULT '[]',
  is_public     INTEGER NOT NULL DEFAULT 1,
  position      INTEGER NOT NULL DEFAULT 0
);

CREATE TABLE plan_features (
  id       TEXT PRIMARY KEY,
  plan_id  TEXT NOT NULL REFERENCES plans(id) ON DELETE CASCADE,
  label    TEXT NOT NULL,
  included INTEGER NOT NULL DEFAULT 1,
  position INTEGER NOT NULL DEFAULT 0
);

CREATE TABLE subscriptions (
  id           TEXT PRIMARY KEY,
  tenant_id    TEXT NOT NULL REFERENCES tenants(id) ON DELETE CASCADE,
  plan_id      TEXT NOT NULL REFERENCES plans(id),
  status       TEXT NOT NULL DEFAULT 'trialing', -- trialing | active | past_due | grace | cancelled
  seats        INTEGER NOT NULL DEFAULT 3,
  amount       REAL NOT NULL DEFAULT 0,
  currency     TEXT NOT NULL DEFAULT 'USD',
  interval     TEXT NOT NULL DEFAULT 'month',
  period_start TEXT NOT NULL,
  period_end   TEXT NOT NULL,
  cancel_at_period_end INTEGER NOT NULL DEFAULT 0,
  cancelled_at TEXT,
  created_at   TEXT NOT NULL
);
CREATE INDEX idx_subs_tenant ON subscriptions(tenant_id, status);

CREATE TABLE invoices (
  id          TEXT PRIMARY KEY,
  tenant_id   TEXT NOT NULL REFERENCES tenants(id) ON DELETE CASCADE,
  number      TEXT NOT NULL,
  subscription_id TEXT REFERENCES subscriptions(id) ON DELETE SET NULL,
  amount      REAL NOT NULL DEFAULT 0,
  tax         REAL NOT NULL DEFAULT 0,
  total       REAL NOT NULL DEFAULT 0,
  currency    TEXT NOT NULL DEFAULT 'USD',
  status      TEXT NOT NULL DEFAULT 'open',  -- open | paid | overdue | void
  line_items  TEXT NOT NULL DEFAULT '[]',
  issued_at   TEXT NOT NULL,
  due_at      TEXT,
  paid_at     TEXT,
  created_at  TEXT NOT NULL
);
CREATE INDEX idx_invoices_tenant ON invoices(tenant_id, issued_at);

CREATE TABLE payments (
  id         TEXT PRIMARY KEY,
  tenant_id  TEXT NOT NULL REFERENCES tenants(id) ON DELETE CASCADE,
  invoice_id TEXT REFERENCES invoices(id) ON DELETE SET NULL,
  provider   TEXT NOT NULL DEFAULT 'sandbox',
  provider_ref TEXT,
  amount     REAL NOT NULL DEFAULT 0,
  currency   TEXT NOT NULL DEFAULT 'USD',
  status     TEXT NOT NULL DEFAULT 'succeeded', -- pending | succeeded | failed | refunded
  idempotency_key TEXT UNIQUE,
  created_at TEXT NOT NULL
);
CREATE INDEX idx_payments_tenant ON payments(tenant_id);

CREATE TABLE usage_records (
  id         TEXT PRIMARY KEY,
  tenant_id  TEXT NOT NULL REFERENCES tenants(id) ON DELETE CASCADE,
  metric     TEXT NOT NULL,   -- api_requests | ai_minutes | storage_gb | seats | voice_calls | ai_tokens
  quantity   REAL NOT NULL DEFAULT 0,
  limit_value REAL NOT NULL DEFAULT 0,
  period     TEXT NOT NULL,
  recorded_at TEXT NOT NULL
);
CREATE INDEX idx_usage_tenant ON usage_records(tenant_id, period, metric);

CREATE TABLE billing_events (
  id         TEXT PRIMARY KEY,
  tenant_id  TEXT REFERENCES tenants(id) ON DELETE CASCADE,
  kind       TEXT NOT NULL,
  detail     TEXT,
  created_at TEXT NOT NULL
);

-- ─── Nexio AI Agents ───────────────────────────────────────────────────────
CREATE TABLE ai_agents (
  id             TEXT PRIMARY KEY,
  tenant_id      TEXT NOT NULL REFERENCES tenants(id) ON DELETE CASCADE,
  name           TEXT NOT NULL,
  role           TEXT NOT NULL DEFAULT 'sales',  -- sales | analytics | support | automation
  description    TEXT,
  provider       TEXT NOT NULL DEFAULT 'sandbox',
  model          TEXT NOT NULL DEFAULT 'nexio-voice-1',
  voice          TEXT NOT NULL DEFAULT 'ru-female-1',
  language       TEXT NOT NULL DEFAULT 'ru',
  persona        TEXT,
  script         TEXT,
  status         TEXT NOT NULL DEFAULT 'draft',  -- draft | active | paused | archived
  autonomy       TEXT NOT NULL DEFAULT 'supervised', -- supervised | assisted | autonomous
  policy         TEXT NOT NULL DEFAULT '{}',
  knowledge      TEXT NOT NULL DEFAULT '[]',
  tools          TEXT NOT NULL DEFAULT '[]',
  budget_limit   REAL NOT NULL DEFAULT 1000,
  budget_used    REAL NOT NULL DEFAULT 0,
  cost_per_minute REAL NOT NULL DEFAULT 0.09,
  created_by     TEXT REFERENCES users(id),
  created_at     TEXT NOT NULL,
  updated_at     TEXT NOT NULL
);
CREATE INDEX idx_agents_tenant ON ai_agents(tenant_id, status);

CREATE TABLE ai_campaigns (
  id          TEXT PRIMARY KEY,
  tenant_id   TEXT NOT NULL REFERENCES tenants(id) ON DELETE CASCADE,
  agent_id    TEXT NOT NULL REFERENCES ai_agents(id) ON DELETE CASCADE,
  name        TEXT NOT NULL,
  goal        TEXT,
  status      TEXT NOT NULL DEFAULT 'draft',  -- draft | scheduled | running | paused | completed | stopped
  total_contacts INTEGER NOT NULL DEFAULT 0,
  completed   INTEGER NOT NULL DEFAULT 0,
  connected   INTEGER NOT NULL DEFAULT 0,
  interested  INTEGER NOT NULL DEFAULT 0,
  orders_created INTEGER NOT NULL DEFAULT 0,
  schedule    TEXT,
  started_at  TEXT,
  created_at  TEXT NOT NULL
);
CREATE INDEX idx_campaigns_tenant ON ai_campaigns(tenant_id, status);

CREATE TABLE ai_calls (
  id            TEXT PRIMARY KEY,
  tenant_id     TEXT NOT NULL REFERENCES tenants(id) ON DELETE CASCADE,
  agent_id      TEXT REFERENCES ai_agents(id) ON DELETE SET NULL,
  campaign_id   TEXT REFERENCES ai_campaigns(id) ON DELETE SET NULL,
  contact_id    TEXT REFERENCES contacts(id) ON DELETE SET NULL,
  org_id        TEXT REFERENCES organizations(id) ON DELETE SET NULL,
  deal_id       TEXT REFERENCES deals(id) ON DELETE SET NULL,
  order_id      TEXT REFERENCES orders(id) ON DELETE SET NULL,
  direction     TEXT NOT NULL DEFAULT 'outbound',
  phone         TEXT,
  status        TEXT NOT NULL DEFAULT 'queued',
  outcome       TEXT,             -- interested | objection | qualified | not_interested | no_answer | voicemail | opted_out | human_handoff | failed
  duration_sec  INTEGER NOT NULL DEFAULT 0,
  cost          REAL NOT NULL DEFAULT 0,
  consent_basis TEXT,
  ai_disclosed  INTEGER NOT NULL DEFAULT 0,
  recorded      INTEGER NOT NULL DEFAULT 0,     -- recording actually stored?
  recording_allowed INTEGER NOT NULL DEFAULT 0, -- policy allowed it?
  confidence    REAL NOT NULL DEFAULT 0,
  summary       TEXT,
  transcript    TEXT,
  sandbox       INTEGER NOT NULL DEFAULT 1,
  started_at    TEXT,
  ended_at      TEXT,
  created_at    TEXT NOT NULL
);
CREATE INDEX idx_ai_calls_tenant ON ai_calls(tenant_id, created_at);
CREATE INDEX idx_ai_calls_status ON ai_calls(tenant_id, status);

CREATE TABLE ai_tool_calls (
  id         TEXT PRIMARY KEY,
  tenant_id  TEXT NOT NULL REFERENCES tenants(id) ON DELETE CASCADE,
  call_id    TEXT REFERENCES ai_calls(id) ON DELETE CASCADE,
  tool       TEXT NOT NULL,
  args       TEXT,
  result     TEXT,
  approved   INTEGER NOT NULL DEFAULT 0,
  created_at TEXT NOT NULL
);

CREATE TABLE ai_approvals (
  id         TEXT PRIMARY KEY,
  tenant_id  TEXT NOT NULL REFERENCES tenants(id) ON DELETE CASCADE,
  call_id    TEXT REFERENCES ai_calls(id) ON DELETE CASCADE,
  agent_id   TEXT,
  kind       TEXT NOT NULL,     -- discount | large_order | custom_terms | refund
  detail     TEXT,
  status     TEXT NOT NULL DEFAULT 'pending', -- pending | approved | rejected
  decided_by TEXT REFERENCES users(id),
  created_at TEXT NOT NULL
);

CREATE TABLE consents (
  id         TEXT PRIMARY KEY,
  tenant_id  TEXT NOT NULL REFERENCES tenants(id) ON DELETE CASCADE,
  contact_id TEXT NOT NULL REFERENCES contacts(id) ON DELETE CASCADE,
  channel    TEXT NOT NULL,
  granted    INTEGER NOT NULL DEFAULT 0,
  basis      TEXT,
  recorded_at TEXT NOT NULL
);

CREATE TABLE suppression_list (
  id         TEXT PRIMARY KEY,
  tenant_id  TEXT NOT NULL REFERENCES tenants(id) ON DELETE CASCADE,
  phone      TEXT,
  email      TEXT,
  reason     TEXT NOT NULL DEFAULT 'do_not_call',
  created_at TEXT NOT NULL
);
CREATE INDEX idx_suppression_tenant ON suppression_list(tenant_id);

CREATE TABLE ai_evaluations (
  id         TEXT PRIMARY KEY,
  tenant_id  TEXT NOT NULL REFERENCES tenants(id) ON DELETE CASCADE,
  agent_id   TEXT REFERENCES ai_agents(id) ON DELETE CASCADE,
  metric     TEXT NOT NULL,
  score      REAL NOT NULL DEFAULT 0,
  sample_size INTEGER NOT NULL DEFAULT 0,
  period     TEXT,
  created_at TEXT NOT NULL
);

-- ─── Feature flags & settings ──────────────────────────────────────────────
CREATE TABLE feature_flags (
  id         TEXT PRIMARY KEY,
  tenant_id  TEXT REFERENCES tenants(id) ON DELETE CASCADE,
  key        TEXT NOT NULL,
  enabled    INTEGER NOT NULL DEFAULT 0,
  created_at TEXT NOT NULL
);

CREATE TABLE tenant_settings (
  tenant_id  TEXT NOT NULL PRIMARY KEY REFERENCES tenants(id) ON DELETE CASCADE,
  settings   TEXT NOT NULL DEFAULT '{}',
  updated_at TEXT NOT NULL
);
