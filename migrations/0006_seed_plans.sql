-- Production defaults required by tenant provisioning and public pricing.
INSERT OR IGNORE INTO plans
  (id, code, name, tagline, price_monthly, price_yearly, currency, seats, api_requests, ai_minutes, storage_gb, modules, is_public, position)
VALUES
  ('plan_starter', 'starter', 'Starter', 'Для небольших команд, которые только начинают', 49, 490, 'USD', 5, 10000, 0, 5, '["crm","projects"]', 1, 1),
  ('plan_business', 'business', 'Business', 'Полный набор для растущей компании', 129, 1290, 'USD', 25, 100000, 300, 50, '["crm","projects","finance","helpdesk","analytics","automations","api","portal","documents"]', 1, 2),
  ('plan_enterprise', 'enterprise', 'Enterprise', 'Для крупных организаций и AI-продаж', 249, 2490, 'USD', 150, 2000000, 3000, 500, '["crm","projects","finance","helpdesk","analytics","automations","api","ai_agents","voice","portal","documents"]', 1, 3);

INSERT OR IGNORE INTO plan_features (id, plan_id, label, included, position) VALUES
  ('pf_0001', 'plan_starter', 'До 5 пользователей', 1, 0),
  ('pf_0002', 'plan_starter', 'Контакты, лиды, сделки', 1, 1),
  ('pf_0003', 'plan_starter', 'Канбан и таблицы', 1, 2),
  ('pf_0004', 'plan_starter', 'Задачи и проекты', 1, 3),
  ('pf_0005', 'plan_starter', 'AI-агенты и звонки', 0, 4),
  ('pf_0006', 'plan_starter', 'Клиентский портал', 0, 5),
  ('pf_0007', 'plan_starter', 'Email поддержка', 1, 6),
  ('pf_0008', 'plan_business', 'До 25 пользователей', 1, 0),
  ('pf_0009', 'plan_business', 'Полная воронка продаж', 1, 1),
  ('pf_000a', 'plan_business', 'Финансы и счета', 1, 2),
  ('pf_000b', 'plan_business', 'Helpdesk и SLA', 1, 3),
  ('pf_000c', 'plan_business', 'Расширенная аналитика', 1, 4),
  ('pf_000d', 'plan_business', 'Автоматизации', 1, 5),
  ('pf_000e', 'plan_business', 'API и вебхуки', 1, 6),
  ('pf_000f', 'plan_business', 'Клиентский портал', 1, 7),
  ('pf_000g', 'plan_business', 'White label / свой домен', 0, 8),
  ('pf_000h', 'plan_enterprise', 'До 150 пользователей', 1, 0),
  ('pf_000i', 'plan_enterprise', 'Мультивалютность и прайсы', 1, 1),
  ('pf_000j', 'plan_enterprise', 'AI-агенты продаж', 1, 2),
  ('pf_000k', 'plan_enterprise', 'Голосовые AI-звонки', 1, 3),
  ('pf_000l', 'plan_enterprise', 'Кастомные домены', 1, 4),
  ('pf_000m', 'plan_enterprise', 'Enterprise SSO', 1, 5),
  ('pf_000n', 'plan_enterprise', 'Аудит и журналы', 1, 6),
  ('pf_000o', 'plan_enterprise', 'SLA 99.9%', 1, 7),
  ('pf_000p', 'plan_enterprise', 'Выделенный менеджер', 1, 8);
