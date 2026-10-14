PRAGMA foreign_keys = ON;

CREATE TABLE platform_provider_configs (
  provider         TEXT PRIMARY KEY,
  encrypted_secret TEXT,
  secret_hint      TEXT,
  model            TEXT,
  enabled          INTEGER NOT NULL DEFAULT 0,
  updated_by       TEXT REFERENCES users(id),
  created_at       TEXT NOT NULL,
  updated_at       TEXT NOT NULL
);
