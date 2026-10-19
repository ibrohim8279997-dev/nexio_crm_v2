CREATE TABLE IF NOT EXISTS notifications (
  id TEXT PRIMARY KEY, tenant_id TEXT, user_id TEXT NOT NULL, type TEXT NOT NULL DEFAULT 'info',
  title TEXT NOT NULL, body TEXT, action_url TEXT, read_at TEXT, created_at TEXT NOT NULL
);
CREATE INDEX IF NOT EXISTS idx_notifications_user ON notifications(user_id, created_at);
CREATE TABLE IF NOT EXISTS user_telegram_links (
  id TEXT PRIMARY KEY, user_id TEXT NOT NULL UNIQUE, chat_id TEXT UNIQUE, username TEXT,
  link_token TEXT NOT NULL UNIQUE, linked_at TEXT, created_at TEXT NOT NULL
);
ALTER TABLE tenants ADD COLUMN logo_url TEXT;
