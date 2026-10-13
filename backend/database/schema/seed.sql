-- Nexio CRM · generated demo seed. Regenerate: node seed/generate.mjs
-- Demo password for every seeded account: nexio123
PRAGMA foreign_keys = OFF;

-- plans: 3 rows
INSERT INTO plans VALUES
  ('plan_starter','starter','Starter','Для небольших команд, которые только начинают',29,290,'USD',5,10000,0,5,'["crm","projects"]',1,1),
  ('plan_business','business','Business','Полный набор для растущей компании',99,990,'USD',25,100000,300,50,'["crm","projects","finance","helpdesk","analytics","automations","api","portal","documents"]',1,2),
  ('plan_enterprise','enterprise','Enterprise','Для крупных организаций и AI-продаж',299,2990,'USD',150,2000000,3000,500,'["crm","projects","finance","helpdesk","analytics","automations","api","ai_agents","voice","portal","documents"]',1,3);

-- plan_features: 25 rows
INSERT INTO plan_features VALUES
  ('pf_0001','plan_starter','До 5 пользователей',1,0),
  ('pf_0002','plan_starter','Контакты, лиды, сделки',1,1),
  ('pf_0003','plan_starter','Канбан и таблицы',1,2),
  ('pf_0004','plan_starter','Задачи и проекты',1,3),
  ('pf_0005','plan_starter','AI-агенты и звонки',0,4),
  ('pf_0006','plan_starter','Клиентский портал',0,5),
  ('pf_0007','plan_starter','Email поддержка',1,6),
  ('pf_0008','plan_business','До 25 пользователей',1,0),
  ('pf_0009','plan_business','Полная воронка продаж',1,1),
  ('pf_000a','plan_business','Финансы и счета',1,2),
  ('pf_000b','plan_business','Helpdesk и SLA',1,3),
  ('pf_000c','plan_business','Расширенная аналитика',1,4),
  ('pf_000d','plan_business','Автоматизации',1,5),
  ('pf_000e','plan_business','API и вебхуки',1,6),
  ('pf_000f','plan_business','Клиентский портал',1,7),
  ('pf_000g','plan_business','White label / свой домен',0,8),
  ('pf_000h','plan_enterprise','До 150 пользователей',1,0),
  ('pf_000i','plan_enterprise','Мультивалютность и прайсы',1,1),
  ('pf_000j','plan_enterprise','AI-агенты продаж',1,2),
  ('pf_000k','plan_enterprise','Голосовые AI-звонки',1,3),
  ('pf_000l','plan_enterprise','Кастомные домены',1,4),
  ('pf_000m','plan_enterprise','Enterprise SSO',1,5),
  ('pf_000n','plan_enterprise','Аудит и журналы',1,6),
  ('pf_000o','plan_enterprise','SLA 99.9%',1,7),
  ('pf_000p','plan_enterprise','Выделенный менеджер',1,8);

-- users: 16 rows
INSERT INTO users VALUES
  ('usr_000q','owner@nexio.uz','pbkdf2$120000$97f24da8035eb9146fca2580db3691ec$Z/FOtLDawnntDiRkBgqg2kX28z2nlVghv/Kw4CczqS8=','Arslon Karov',NULL,'#2563EB',1,'platform_superadmin',1,'2026-10-11 09:00:00','2026-02-14 09:00:00','2026-10-12 09:00:00'),
  ('usr_000r','admin@acmecorp.uz','pbkdf2$120000$bc1772cd2883de3994ef4aa5005bb611$xuEV2TO6j+oY3wKfT/M7hDYBNlxWOslrQulJNpdiSB8=','Jasher Deen',NULL,'#10B981',0,NULL,1,'2026-10-12 09:00:00','2026-02-14 09:00:00','2026-10-12 09:00:00'),
  ('usr_000s','lead@acmecorp.uz','pbkdf2$120000$e13c97f24da8035eb9146fca2580db36$t1jzbvdEs3XLYpTYHrLUnaSy+JXo5pPU6IS5mrmVioQ=','Dilnoza Yusupova',NULL,'#F59E0B',0,NULL,1,'2026-10-11 09:00:00','2026-02-14 09:00:00','2026-10-12 09:00:00'),
  ('usr_000t','agent@acmecorp.uz','pbkdf2$120000$0661bc1772cd2883de3994ef4aa5005b$cRMAwTs4qfcLsUNLKpwy+IFkNtVWWjsX2pRnCs6eOtU=','Rustam Nazarov',NULL,'#8B5CF6',0,NULL,1,'2026-10-12 09:00:00','2026-02-14 09:00:00','2026-10-12 09:00:00'),
  ('usr_000u','pm@acmecorp.uz','pbkdf2$120000$2b86e13c97f24da8035eb9146fca2580$+71mPFSBe/Yr/IB+XDmK685Lo1Amf3SoxT3Sp81JAJM=','Kamila Ergasheva',NULL,'#0EA5E9',0,NULL,1,'2026-10-10 09:00:00','2026-02-14 09:00:00','2026-10-12 09:00:00'),
  ('usr_000v','dev@acmecorp.uz','pbkdf2$120000$50ab0661bc1772cd2883de3994ef4aa5$Q/zgUgwGosoAVm0v1ctZm87nvgIaNmeKucYp1fHWU1A=','Sardor Toshmatov',NULL,'#EF4444',0,NULL,1,'2026-10-10 09:00:00','2026-02-14 09:00:00','2026-10-12 09:00:00'),
  ('usr_000w','support@acmecorp.uz','pbkdf2$120000$75d02b86e13c97f24da8035eb9146fca$kjdFO288/YYALm5fBJrUWzRM3wptNdXkMT+bnywqn2I=','Nilufar Ahmedova',NULL,'#14B8A6',0,NULL,1,'2026-10-09 09:00:00','2026-02-14 09:00:00','2026-10-12 09:00:00'),
  ('usr_000x','finance@acmecorp.uz','pbkdf2$120000$9af550ab0661bc1772cd2883de3994ef$gebltxamNY0DufCsi64WwUnm8fSb9QgUdWp9kSwQ000=','Bekzod Rahimov',NULL,'#F97316',0,NULL,1,'2026-10-09 09:00:00','2026-02-14 09:00:00','2026-10-12 09:00:00'),
  ('usr_000y','analyst@acmecorp.uz','pbkdf2$120000$bf1a75d02b86e13c97f24da8035eb914$Hx9iwqrpXnUGY7fNisB9mBSAarE5xHvQW13kX0pozgo=','Zilola Karimova',NULL,'#6366F1',0,NULL,1,'2026-10-11 09:00:00','2026-02-14 09:00:00','2026-10-12 09:00:00'),
  ('usr_000z','owner@globex.uz','pbkdf2$120000$e43f9af550ab0661bc1772cd2883de39$ihgep8TUTy6mEnDThi7X7oooAOdXhaGS4rZAOEMHfZ0=','Timur Alimov',NULL,'#8B5CF6',0,NULL,1,'2026-10-11 09:00:00','2026-02-14 09:00:00','2026-10-12 09:00:00'),
  ('usr_0010','owner@orbit.uz','pbkdf2$120000$0964bf1a75d02b86e13c97f24da8035e$B0KBlrLcoJxHV5BNLIqTNbl1wgZFuT8V8AkKXDt5nAI=','Lola Saidova',NULL,'#EC4899',0,NULL,1,'2026-10-09 09:00:00','2026-02-14 09:00:00','2026-10-12 09:00:00'),
  ('usr_0011','owner@technova.uz','pbkdf2$120000$2e89e43f9af550ab0661bc1772cd2883$YcrEK+DU0v3PDbzduRAHff6tYjbLxWEg/Hzm+U8q2pk=','Aziz Bekmurodov',NULL,'#22C55E',0,NULL,1,'2026-10-11 09:00:00','2026-02-14 09:00:00','2026-10-12 09:00:00'),
  ('usr_0012','support@nexio.uz','pbkdf2$120000$53ae0964bf1a75d02b86e13c97f24da8$ms1TzTrc2OnpgsmW1SSwCDquLqIJJsWi7oJ4RGpkEPU=','Gulnora Isaeva',NULL,'#64748B',1,'platform_support',1,'2026-10-12 09:00:00','2026-02-14 09:00:00','2026-10-12 09:00:00'),
  ('usr_0013','demo@nexio.uz','pbkdf2$120000$78d32e89e43f9af550ab0661bc1772cd$QAHXQqR9zSBOwDVha/VHS7SLG/Zgyg88VSsbc5nrjG4=','Demo User',NULL,'#2563EB',0,NULL,1,'2026-10-09 09:00:00','2026-02-14 09:00:00','2026-10-12 09:00:00'),
  ('usr_0014','client@acmecorp.uz','pbkdf2$120000$9df853ae0964bf1a75d02b86e13c97f2$+Q5jsSOVXAzsboWLTs88U9k/s0GTHSyJODu2YPswQfU=','Aziza Nabiyeva',NULL,'#0EA5E9',0,NULL,1,'2026-10-12 09:00:00','2026-02-14 09:00:00','2026-10-12 09:00:00'),
  ('usr_0015','buyer@starkindustries.uz','pbkdf2$120000$c21d78d32e89e43f9af550ab0661bc17$PbC7Vx/Q8/JXh41sjDBl5vV5PZRabslZdsfDQlZmGpk=','Farhod Yusupov',NULL,'#14B8A6',0,NULL,1,'2026-10-11 09:00:00','2026-02-14 09:00:00','2026-10-12 09:00:00');

-- tenants: 12 rows
INSERT INTO tenants VALUES
  ('tnt_0016','Acme Corp','acme-corp','Acme Corp MChJ','ACME','#2563EB','ru','Asia/Tashkent','UZS','UZ','Software','active','plan_business',NULL,'2026-03-16 09:00:00','2026-10-11 09:00:00'),
  ('tnt_0017','Nexio Lab','nexio-lab','Nexio Lab MChJ','NEXIO','#2563EB','ru','Asia/Tashkent','UZS','UZ','IT Services','active','plan_enterprise',NULL,'2025-09-07 09:00:00','2026-10-11 09:00:00'),
  ('tnt_0018','Globex Industries','globex-industries','Globex Industries MChJ','GLOBEX','#0EA5E9','ru','Asia/Tashkent','UZS','UZ','Manufacturing','active','plan_enterprise',NULL,'2026-04-15 09:00:00','2026-10-11 09:00:00'),
  ('tnt_0019','Orbit Systems','orbit-systems','Orbit Systems MChJ','ORBIT','#0EA5E9','ru','Asia/Tashkent','UZS','UZ','Logistics','active','plan_business',NULL,'2026-06-14 09:00:00','2026-10-11 09:00:00'),
  ('tnt_001a','TechNova','technova','TechNova MChJ','TECHNOVA','#8B5CF6','ru','Asia/Tashkent','UZS','UZ','SaaS','trial','plan_starter','2026-10-17 09:00:00','2026-10-03 09:00:00','2026-10-11 09:00:00'),
  ('tnt_001b','Initech LLC','initech-llc','Initech LLC MChJ','INITECH','#10B981','ru','Asia/Tashkent','UZS','KZ','Consulting','past_due','plan_starter',NULL,'2026-07-09 09:00:00','2026-10-11 09:00:00'),
  ('tnt_001c','BrightWave Digital','brightwave','BrightWave Digital MChJ','BRIGHTWAVE','#10B981','ru','Asia/Tashkent','UZS','UZ','Marketing','active','plan_business',NULL,'2026-05-15 09:00:00','2026-10-11 09:00:00'),
  ('tnt_001d','Silk Road Logistics','silkroad-logistics','Silk Road Logistics MChJ','SILK','#2563EB','ru','Asia/Tashkent','UZS','UZ','Logistics','active','plan_enterprise',NULL,'2025-12-16 09:00:00','2026-10-11 09:00:00'),
  ('tnt_001e','Zenith Soft','zenith-soft','Zenith Soft MChJ','ZENITH','#0EA5E9','ru','Asia/Tashkent','UZS','UZ','Software','suspended','plan_business',NULL,'2026-01-25 09:00:00','2026-10-11 09:00:00'),
  ('tnt_001f','PrimeCargo','primecargo','PrimeCargo MChJ','PRIMECARGO','#2563EB','ru','Asia/Tashkent','UZS','UZ','Freight','cancelled','plan_starter',NULL,'2025-11-06 09:00:00','2026-10-11 09:00:00'),
  ('tnt_001g','Vertex Digital','vertex-digital','Vertex Digital MChJ','VERTEX','#2563EB','ru','Asia/Tashkent','UZS','UZ','Design','active','plan_business',NULL,'2026-07-16 09:00:00','2026-10-11 09:00:00'),
  ('tnt_001h','Umbrella Co','umbrella-co','Umbrella Co MChJ','UMBRELLA','#2563EB','ru','Asia/Tashkent','UZS','UZ','Retail','active','plan_enterprise',NULL,'2026-03-06 09:00:00','2026-10-11 09:00:00');

-- tenant_settings: 12 rows
INSERT INTO tenant_settings VALUES
  ('tnt_0016','{"work_hours":"09:00–18:00","week_start":"mon","default_pipeline":"Sales","tax_rate":12,"date_format":"DD.MM.YYYY"}','2026-10-11 09:00:00'),
  ('tnt_0017','{"work_hours":"09:00–18:00","week_start":"mon","default_pipeline":"Sales","tax_rate":12,"date_format":"DD.MM.YYYY"}','2026-10-11 09:00:00'),
  ('tnt_0018','{"work_hours":"09:00–18:00","week_start":"mon","default_pipeline":"Sales","tax_rate":12,"date_format":"DD.MM.YYYY"}','2026-10-11 09:00:00'),
  ('tnt_0019','{"work_hours":"09:00–18:00","week_start":"mon","default_pipeline":"Sales","tax_rate":12,"date_format":"DD.MM.YYYY"}','2026-10-11 09:00:00'),
  ('tnt_001a','{"work_hours":"09:00–18:00","week_start":"mon","default_pipeline":"Sales","tax_rate":12,"date_format":"DD.MM.YYYY"}','2026-10-11 09:00:00'),
  ('tnt_001b','{"work_hours":"09:00–18:00","week_start":"mon","default_pipeline":"Sales","tax_rate":12,"date_format":"DD.MM.YYYY"}','2026-10-11 09:00:00'),
  ('tnt_001c','{"work_hours":"09:00–18:00","week_start":"mon","default_pipeline":"Sales","tax_rate":12,"date_format":"DD.MM.YYYY"}','2026-10-11 09:00:00'),
  ('tnt_001d','{"work_hours":"09:00–18:00","week_start":"mon","default_pipeline":"Sales","tax_rate":12,"date_format":"DD.MM.YYYY"}','2026-10-11 09:00:00'),
  ('tnt_001e','{"work_hours":"09:00–18:00","week_start":"mon","default_pipeline":"Sales","tax_rate":12,"date_format":"DD.MM.YYYY"}','2026-10-11 09:00:00'),
  ('tnt_001f','{"work_hours":"09:00–18:00","week_start":"mon","default_pipeline":"Sales","tax_rate":12,"date_format":"DD.MM.YYYY"}','2026-10-11 09:00:00'),
  ('tnt_001g','{"work_hours":"09:00–18:00","week_start":"mon","default_pipeline":"Sales","tax_rate":12,"date_format":"DD.MM.YYYY"}','2026-10-11 09:00:00'),
  ('tnt_001h','{"work_hours":"09:00–18:00","week_start":"mon","default_pipeline":"Sales","tax_rate":12,"date_format":"DD.MM.YYYY"}','2026-10-11 09:00:00');

-- tenant_memberships: 15 rows
INSERT INTO tenant_memberships VALUES
  ('mem_001i','tnt_0016','usr_000r','tenant_owner','Delivery','Руководитель отдела','active',NULL,'usr_000q','2026-03-26 09:00:00'),
  ('mem_001j','tnt_0016','usr_000s','sales_manager','Engineering','Руководитель отдела','active',NULL,'usr_000q','2026-04-05 09:00:00'),
  ('mem_001k','tnt_0016','usr_000t','sales_agent','Engineering','Менеджер','active',NULL,'usr_000q','2026-04-15 09:00:00'),
  ('mem_001l','tnt_0016','usr_000u','project_manager','Delivery','Специалист','active',NULL,'usr_000q','2026-04-25 09:00:00'),
  ('mem_001m','tnt_0016','usr_000v','developer','Support','Руководитель отдела','active',NULL,'usr_000q','2026-05-05 09:00:00'),
  ('mem_001n','tnt_0016','usr_000w','support_agent','Sales','Менеджер','active',NULL,'usr_000q','2026-05-15 09:00:00'),
  ('mem_001o','tnt_0016','usr_000x','finance_manager','Sales','Руководитель отдела','active',NULL,'usr_000q','2026-05-25 09:00:00'),
  ('mem_001p','tnt_0016','usr_000y','analyst','Finance','Ведущий специалист','active',NULL,'usr_000q','2026-06-04 09:00:00'),
  ('mem_001q','tnt_0016','usr_0013','tenant_admin','Finance','Ведущий специалист','active',NULL,'usr_000q','2026-06-14 09:00:00'),
  ('mem_001r','tnt_0017','usr_000q','tenant_owner','Management','CEO','active',NULL,NULL,'2025-09-07 09:00:00'),
  ('mem_001s','tnt_0018','usr_000z','tenant_owner','Management','Владелец','active',NULL,'usr_000q','2026-04-15 09:00:00'),
  ('mem_001t','tnt_0019','usr_0010','tenant_owner','Management','Владелец','active',NULL,'usr_000q','2026-06-14 09:00:00'),
  ('mem_001u','tnt_001a','usr_0011','tenant_owner','Management','Владелец','active',NULL,'usr_000q','2026-10-03 09:00:00'),
  ('mem_00fh','tnt_0016','usr_0014','client_portal_user','Finance','Финансовый директор','active','org_00ce','usr_000r','2026-06-14 09:00:00'),
  ('mem_00fi','tnt_0016','usr_0015','client_portal_user','Procurement','Руководитель закупок','active','org_00be','usr_000r','2026-06-14 09:00:00');

-- invitations: 1 rows
INSERT INTO invitations VALUES
  ('inv_001v','tnt_0016','new.hire@acmecorp.uz','sales_agent','inv_demo_7f3c91','usr_000r','pending','2026-10-18 09:00:00','2026-10-11 09:00:00');

-- subscriptions: 12 rows
INSERT INTO subscriptions VALUES
  ('sub_001w','tnt_0016','plan_business','active',25,99,'USD','month','2026-10-03 09:00:00','2026-11-11 09:00:00',0,NULL,'2026-03-16 09:00:00'),
  ('sub_002n','tnt_0017','plan_enterprise','active',150,299,'USD','month','2026-10-04 09:00:00','2026-11-11 09:00:00',0,NULL,'2025-09-07 09:00:00'),
  ('sub_003t','tnt_0018','plan_enterprise','active',150,299,'USD','month','2026-09-30 09:00:00','2026-11-11 09:00:00',0,NULL,'2026-04-15 09:00:00'),
  ('sub_004h','tnt_0019','plan_business','active',25,99,'USD','month','2026-09-24 09:00:00','2026-11-11 09:00:00',0,NULL,'2026-06-14 09:00:00'),
  ('sub_004z','tnt_001a','plan_starter','trialing',5,29,'USD','month','2026-09-21 09:00:00','2026-11-11 09:00:00',0,NULL,'2026-10-03 09:00:00'),
  ('sub_0058','tnt_001b','plan_starter','past_due',5,29,'USD','month','2026-09-26 09:00:00','2026-11-11 09:00:00',0,NULL,'2026-07-09 09:00:00'),
  ('sub_005l','tnt_001c','plan_business','active',25,99,'USD','month','2026-09-27 09:00:00','2026-11-11 09:00:00',0,NULL,'2026-05-15 09:00:00'),
  ('sub_0066','tnt_001d','plan_enterprise','active',150,299,'USD','month','2026-09-15 09:00:00','2026-11-11 09:00:00',0,NULL,'2025-12-16 09:00:00'),
  ('sub_0076','tnt_001e','plan_business','grace',25,99,'USD','month','2026-09-29 09:00:00','2026-11-11 09:00:00',0,NULL,'2026-01-25 09:00:00'),
  ('sub_0083','tnt_001f','plan_starter','cancelled',5,29,'USD','month','2026-09-28 09:00:00','2026-11-11 09:00:00',1,'2026-09-22 09:00:00','2025-11-06 09:00:00'),
  ('sub_0096','tnt_001g','plan_business','active',25,99,'USD','month','2026-09-19 09:00:00','2026-11-11 09:00:00',0,NULL,'2026-07-16 09:00:00'),
  ('sub_009l','tnt_001h','plan_enterprise','active',150,299,'USD','month','2026-09-16 09:00:00','2026-11-11 09:00:00',0,NULL,'2026-03-06 09:00:00');

-- invoices: 78 rows
INSERT INTO invoices VALUES
  ('inv_001x','tnt_0016','INV-2026-1069','sub_001w',99,11.88,110.88,'USD','paid','[{"label":"Business plan · monthly","qty":1,"amount":99},{"label":"Tax 12%","qty":1,"amount":11.88}]','2026-03-16 09:00:00','2026-03-23 09:00:00','2026-03-18 09:00:00','2026-03-16 09:00:00'),
  ('inv_0020','tnt_0016','INV-2026-1072','sub_001w',99,11.88,110.88,'USD','paid','[{"label":"Business plan · monthly","qty":1,"amount":99},{"label":"Tax 12%","qty":1,"amount":11.88}]','2026-04-15 09:00:00','2026-04-22 09:00:00','2026-04-17 09:00:00','2026-04-15 09:00:00'),
  ('inv_0023','tnt_0016','INV-2026-1075','sub_001w',99,11.88,110.88,'USD','paid','[{"label":"Business plan · monthly","qty":1,"amount":99},{"label":"Tax 12%","qty":1,"amount":11.88}]','2026-05-15 09:00:00','2026-05-22 09:00:00','2026-05-17 09:00:00','2026-05-15 09:00:00'),
  ('inv_0026','tnt_0016','INV-2026-1078','sub_001w',99,11.88,110.88,'USD','paid','[{"label":"Business plan · monthly","qty":1,"amount":99},{"label":"Tax 12%","qty":1,"amount":11.88}]','2026-06-14 09:00:00','2026-06-21 09:00:00','2026-06-16 09:00:00','2026-06-14 09:00:00'),
  ('inv_0029','tnt_0016','INV-2026-1081','sub_001w',99,11.88,110.88,'USD','paid','[{"label":"Business plan · monthly","qty":1,"amount":99},{"label":"Tax 12%","qty":1,"amount":11.88}]','2026-07-14 09:00:00','2026-07-21 09:00:00','2026-07-16 09:00:00','2026-07-14 09:00:00'),
  ('inv_002c','tnt_0016','INV-2026-1084','sub_001w',99,11.88,110.88,'USD','paid','[{"label":"Business plan · monthly","qty":1,"amount":99},{"label":"Tax 12%","qty":1,"amount":11.88}]','2026-08-13 09:00:00','2026-08-20 09:00:00','2026-08-15 09:00:00','2026-08-13 09:00:00'),
  ('inv_002f','tnt_0016','INV-2026-1087','sub_001w',99,11.88,110.88,'USD','paid','[{"label":"Business plan · monthly","qty":1,"amount":99},{"label":"Tax 12%","qty":1,"amount":11.88}]','2026-09-12 09:00:00','2026-09-19 09:00:00','2026-09-14 09:00:00','2026-09-12 09:00:00'),
  ('inv_002o','tnt_0017','INV-2025-1096','sub_002n',299,35.88,334.88,'USD','paid','[{"label":"Enterprise plan · monthly","qty":1,"amount":299},{"label":"Tax 12%","qty":1,"amount":35.88}]','2025-10-17 09:00:00','2025-10-24 09:00:00','2025-10-19 09:00:00','2025-10-17 09:00:00'),
  ('inv_002r','tnt_0017','INV-2025-1099','sub_002n',299,35.88,334.88,'USD','paid','[{"label":"Enterprise plan · monthly","qty":1,"amount":299},{"label":"Tax 12%","qty":1,"amount":35.88}]','2025-11-16 09:00:00','2025-11-23 09:00:00','2025-11-18 09:00:00','2025-11-16 09:00:00'),
  ('inv_002u','tnt_0017','INV-2025-1102','sub_002n',299,35.88,334.88,'USD','paid','[{"label":"Enterprise plan · monthly","qty":1,"amount":299},{"label":"Tax 12%","qty":1,"amount":35.88}]','2025-12-16 09:00:00','2025-12-23 09:00:00','2025-12-18 09:00:00','2025-12-16 09:00:00'),
  ('inv_002x','tnt_0017','INV-2026-1105','sub_002n',299,35.88,334.88,'USD','paid','[{"label":"Enterprise plan · monthly","qty":1,"amount":299},{"label":"Tax 12%","qty":1,"amount":35.88}]','2026-01-15 09:00:00','2026-01-22 09:00:00','2026-01-17 09:00:00','2026-01-15 09:00:00'),
  ('inv_0030','tnt_0017','INV-2026-1108','sub_002n',299,35.88,334.88,'USD','paid','[{"label":"Enterprise plan · monthly","qty":1,"amount":299},{"label":"Tax 12%","qty":1,"amount":35.88}]','2026-02-14 09:00:00','2026-02-21 09:00:00','2026-02-16 09:00:00','2026-02-14 09:00:00'),
  ('inv_0033','tnt_0017','INV-2026-1111','sub_002n',299,35.88,334.88,'USD','paid','[{"label":"Enterprise plan · monthly","qty":1,"amount":299},{"label":"Tax 12%","qty":1,"amount":35.88}]','2026-03-16 09:00:00','2026-03-23 09:00:00','2026-03-18 09:00:00','2026-03-16 09:00:00'),
  ('inv_0036','tnt_0017','INV-2026-1114','sub_002n',299,35.88,334.88,'USD','paid','[{"label":"Enterprise plan · monthly","qty":1,"amount":299},{"label":"Tax 12%","qty":1,"amount":35.88}]','2026-04-15 09:00:00','2026-04-22 09:00:00','2026-04-17 09:00:00','2026-04-15 09:00:00'),
  ('inv_0039','tnt_0017','INV-2026-1117','sub_002n',299,35.88,334.88,'USD','paid','[{"label":"Enterprise plan · monthly","qty":1,"amount":299},{"label":"Tax 12%","qty":1,"amount":35.88}]','2026-05-15 09:00:00','2026-05-22 09:00:00','2026-05-17 09:00:00','2026-05-15 09:00:00'),
  ('inv_003c','tnt_0017','INV-2026-1120','sub_002n',299,35.88,334.88,'USD','paid','[{"label":"Enterprise plan · monthly","qty":1,"amount":299},{"label":"Tax 12%","qty":1,"amount":35.88}]','2026-06-14 09:00:00','2026-06-21 09:00:00','2026-06-16 09:00:00','2026-06-14 09:00:00'),
  ('inv_003f','tnt_0017','INV-2026-1123','sub_002n',299,35.88,334.88,'USD','paid','[{"label":"Enterprise plan · monthly","qty":1,"amount":299},{"label":"Tax 12%","qty":1,"amount":35.88}]','2026-07-14 09:00:00','2026-07-21 09:00:00','2026-07-16 09:00:00','2026-07-14 09:00:00'),
  ('inv_003i','tnt_0017','INV-2026-1126','sub_002n',299,35.88,334.88,'USD','paid','[{"label":"Enterprise plan · monthly","qty":1,"amount":299},{"label":"Tax 12%","qty":1,"amount":35.88}]','2026-08-13 09:00:00','2026-08-20 09:00:00','2026-08-15 09:00:00','2026-08-13 09:00:00'),
  ('inv_003l','tnt_0017','INV-2026-1129','sub_002n',299,35.88,334.88,'USD','paid','[{"label":"Enterprise plan · monthly","qty":1,"amount":299},{"label":"Tax 12%","qty":1,"amount":35.88}]','2026-09-12 09:00:00','2026-09-19 09:00:00','2026-09-14 09:00:00','2026-09-12 09:00:00'),
  ('inv_003u','tnt_0018','INV-2026-1138','sub_003t',299,35.88,334.88,'USD','paid','[{"label":"Enterprise plan · monthly","qty":1,"amount":299},{"label":"Tax 12%","qty":1,"amount":35.88}]','2026-04-15 09:00:00','2026-04-22 09:00:00','2026-04-17 09:00:00','2026-04-15 09:00:00'),
  ('inv_003x','tnt_0018','INV-2026-1141','sub_003t',299,35.88,334.88,'USD','paid','[{"label":"Enterprise plan · monthly","qty":1,"amount":299},{"label":"Tax 12%","qty":1,"amount":35.88}]','2026-05-15 09:00:00','2026-05-22 09:00:00','2026-05-17 09:00:00','2026-05-15 09:00:00'),
  ('inv_0040','tnt_0018','INV-2026-1144','sub_003t',299,35.88,334.88,'USD','paid','[{"label":"Enterprise plan · monthly","qty":1,"amount":299},{"label":"Tax 12%","qty":1,"amount":35.88}]','2026-06-14 09:00:00','2026-06-21 09:00:00','2026-06-16 09:00:00','2026-06-14 09:00:00'),
  ('inv_0043','tnt_0018','INV-2026-1147','sub_003t',299,35.88,334.88,'USD','paid','[{"label":"Enterprise plan · monthly","qty":1,"amount":299},{"label":"Tax 12%","qty":1,"amount":35.88}]','2026-07-14 09:00:00','2026-07-21 09:00:00','2026-07-16 09:00:00','2026-07-14 09:00:00'),
  ('inv_0046','tnt_0018','INV-2026-1150','sub_003t',299,35.88,334.88,'USD','paid','[{"label":"Enterprise plan · monthly","qty":1,"amount":299},{"label":"Tax 12%","qty":1,"amount":35.88}]','2026-08-13 09:00:00','2026-08-20 09:00:00','2026-08-15 09:00:00','2026-08-13 09:00:00'),
  ('inv_0049','tnt_0018','INV-2026-1153','sub_003t',299,35.88,334.88,'USD','paid','[{"label":"Enterprise plan · monthly","qty":1,"amount":299},{"label":"Tax 12%","qty":1,"amount":35.88}]','2026-09-12 09:00:00','2026-09-19 09:00:00','2026-09-14 09:00:00','2026-09-12 09:00:00'),
  ('inv_004i','tnt_0019','INV-2026-1162','sub_004h',99,11.88,110.88,'USD','paid','[{"label":"Business plan · monthly","qty":1,"amount":99},{"label":"Tax 12%","qty":1,"amount":11.88}]','2026-06-14 09:00:00','2026-06-21 09:00:00','2026-06-16 09:00:00','2026-06-14 09:00:00'),
  ('inv_004l','tnt_0019','INV-2026-1165','sub_004h',99,11.88,110.88,'USD','paid','[{"label":"Business plan · monthly","qty":1,"amount":99},{"label":"Tax 12%","qty":1,"amount":11.88}]','2026-07-14 09:00:00','2026-07-21 09:00:00','2026-07-16 09:00:00','2026-07-14 09:00:00'),
  ('inv_004o','tnt_0019','INV-2026-1168','sub_004h',99,11.88,110.88,'USD','paid','[{"label":"Business plan · monthly","qty":1,"amount":99},{"label":"Tax 12%","qty":1,"amount":11.88}]','2026-08-13 09:00:00','2026-08-20 09:00:00','2026-08-15 09:00:00','2026-08-13 09:00:00'),
  ('inv_004r','tnt_0019','INV-2026-1171','sub_004h',99,11.88,110.88,'USD','paid','[{"label":"Business plan · monthly","qty":1,"amount":99},{"label":"Tax 12%","qty":1,"amount":11.88}]','2026-09-12 09:00:00','2026-09-19 09:00:00','2026-09-14 09:00:00','2026-09-12 09:00:00'),
  ('inv_0050','tnt_001a','INV-2026-1180','sub_004z',29,3.48,32.48,'USD','paid','[{"label":"Starter plan · monthly","qty":1,"amount":29},{"label":"Tax 12%","qty":1,"amount":3.48}]','2026-09-12 09:00:00','2026-09-19 09:00:00','2026-09-14 09:00:00','2026-09-12 09:00:00'),
  ('inv_0059','tnt_001b','INV-2026-1189','sub_0058',29,3.48,32.48,'USD','paid','[{"label":"Starter plan · monthly","qty":1,"amount":29},{"label":"Tax 12%","qty":1,"amount":3.48}]','2026-07-14 09:00:00','2026-07-21 09:00:00','2026-07-16 09:00:00','2026-07-14 09:00:00'),
  ('inv_005c','tnt_001b','INV-2026-1192','sub_0058',29,3.48,32.48,'USD','paid','[{"label":"Starter plan · monthly","qty":1,"amount":29},{"label":"Tax 12%","qty":1,"amount":3.48}]','2026-08-13 09:00:00','2026-08-20 09:00:00','2026-08-15 09:00:00','2026-08-13 09:00:00'),
  ('inv_005f','tnt_001b','INV-2026-1195','sub_0058',29,3.48,32.48,'USD','overdue','[{"label":"Starter plan · monthly","qty":1,"amount":29},{"label":"Tax 12%","qty":1,"amount":3.48}]','2026-09-12 09:00:00','2026-09-19 09:00:00',NULL,'2026-09-12 09:00:00'),
  ('inv_005m','tnt_001c','INV-2026-1202','sub_005l',99,11.88,110.88,'USD','paid','[{"label":"Business plan · monthly","qty":1,"amount":99},{"label":"Tax 12%","qty":1,"amount":11.88}]','2026-05-15 09:00:00','2026-05-22 09:00:00','2026-05-17 09:00:00','2026-05-15 09:00:00'),
  ('inv_005p','tnt_001c','INV-2026-1205','sub_005l',99,11.88,110.88,'USD','paid','[{"label":"Business plan · monthly","qty":1,"amount":99},{"label":"Tax 12%","qty":1,"amount":11.88}]','2026-06-14 09:00:00','2026-06-21 09:00:00','2026-06-16 09:00:00','2026-06-14 09:00:00'),
  ('inv_005s','tnt_001c','INV-2026-1208','sub_005l',99,11.88,110.88,'USD','paid','[{"label":"Business plan · monthly","qty":1,"amount":99},{"label":"Tax 12%","qty":1,"amount":11.88}]','2026-07-14 09:00:00','2026-07-21 09:00:00','2026-07-16 09:00:00','2026-07-14 09:00:00'),
  ('inv_005v','tnt_001c','INV-2026-1211','sub_005l',99,11.88,110.88,'USD','paid','[{"label":"Business plan · monthly","qty":1,"amount":99},{"label":"Tax 12%","qty":1,"amount":11.88}]','2026-08-13 09:00:00','2026-08-20 09:00:00','2026-08-15 09:00:00','2026-08-13 09:00:00'),
  ('inv_005y','tnt_001c','INV-2026-1214','sub_005l',99,11.88,110.88,'USD','paid','[{"label":"Business plan · monthly","qty":1,"amount":99},{"label":"Tax 12%","qty":1,"amount":11.88}]','2026-09-12 09:00:00','2026-09-19 09:00:00','2026-09-14 09:00:00','2026-09-12 09:00:00'),
  ('inv_0067','tnt_001d','INV-2025-1223','sub_0066',299,35.88,334.88,'USD','paid','[{"label":"Enterprise plan · monthly","qty":1,"amount":299},{"label":"Tax 12%","qty":1,"amount":35.88}]','2025-12-16 09:00:00','2025-12-23 09:00:00','2025-12-18 09:00:00','2025-12-16 09:00:00'),
  ('inv_006a','tnt_001d','INV-2026-1226','sub_0066',299,35.88,334.88,'USD','paid','[{"label":"Enterprise plan · monthly","qty":1,"amount":299},{"label":"Tax 12%","qty":1,"amount":35.88}]','2026-01-15 09:00:00','2026-01-22 09:00:00','2026-01-17 09:00:00','2026-01-15 09:00:00'),
  ('inv_006d','tnt_001d','INV-2026-1229','sub_0066',299,35.88,334.88,'USD','paid','[{"label":"Enterprise plan · monthly","qty":1,"amount":299},{"label":"Tax 12%","qty":1,"amount":35.88}]','2026-02-14 09:00:00','2026-02-21 09:00:00','2026-02-16 09:00:00','2026-02-14 09:00:00'),
  ('inv_006g','tnt_001d','INV-2026-1232','sub_0066',299,35.88,334.88,'USD','paid','[{"label":"Enterprise plan · monthly","qty":1,"amount":299},{"label":"Tax 12%","qty":1,"amount":35.88}]','2026-03-16 09:00:00','2026-03-23 09:00:00','2026-03-18 09:00:00','2026-03-16 09:00:00'),
  ('inv_006j','tnt_001d','INV-2026-1235','sub_0066',299,35.88,334.88,'USD','paid','[{"label":"Enterprise plan · monthly","qty":1,"amount":299},{"label":"Tax 12%","qty":1,"amount":35.88}]','2026-04-15 09:00:00','2026-04-22 09:00:00','2026-04-17 09:00:00','2026-04-15 09:00:00'),
  ('inv_006m','tnt_001d','INV-2026-1238','sub_0066',299,35.88,334.88,'USD','paid','[{"label":"Enterprise plan · monthly","qty":1,"amount":299},{"label":"Tax 12%","qty":1,"amount":35.88}]','2026-05-15 09:00:00','2026-05-22 09:00:00','2026-05-17 09:00:00','2026-05-15 09:00:00'),
  ('inv_006p','tnt_001d','INV-2026-1241','sub_0066',299,35.88,334.88,'USD','paid','[{"label":"Enterprise plan · monthly","qty":1,"amount":299},{"label":"Tax 12%","qty":1,"amount":35.88}]','2026-06-14 09:00:00','2026-06-21 09:00:00','2026-06-16 09:00:00','2026-06-14 09:00:00'),
  ('inv_006s','tnt_001d','INV-2026-1244','sub_0066',299,35.88,334.88,'USD','paid','[{"label":"Enterprise plan · monthly","qty":1,"amount":299},{"label":"Tax 12%","qty":1,"amount":35.88}]','2026-07-14 09:00:00','2026-07-21 09:00:00','2026-07-16 09:00:00','2026-07-14 09:00:00'),
  ('inv_006v','tnt_001d','INV-2026-1247','sub_0066',299,35.88,334.88,'USD','paid','[{"label":"Enterprise plan · monthly","qty":1,"amount":299},{"label":"Tax 12%","qty":1,"amount":35.88}]','2026-08-13 09:00:00','2026-08-20 09:00:00','2026-08-15 09:00:00','2026-08-13 09:00:00'),
  ('inv_006y','tnt_001d','INV-2026-1250','sub_0066',299,35.88,334.88,'USD','paid','[{"label":"Enterprise plan · monthly","qty":1,"amount":299},{"label":"Tax 12%","qty":1,"amount":35.88}]','2026-09-12 09:00:00','2026-09-19 09:00:00','2026-09-14 09:00:00','2026-09-12 09:00:00'),
  ('inv_0077','tnt_001e','INV-2026-1259','sub_0076',99,11.88,110.88,'USD','paid','[{"label":"Business plan · monthly","qty":1,"amount":99},{"label":"Tax 12%","qty":1,"amount":11.88}]','2026-01-15 09:00:00','2026-01-22 09:00:00','2026-01-17 09:00:00','2026-01-15 09:00:00'),
  ('inv_007a','tnt_001e','INV-2026-1262','sub_0076',99,11.88,110.88,'USD','paid','[{"label":"Business plan · monthly","qty":1,"amount":99},{"label":"Tax 12%","qty":1,"amount":11.88}]','2026-02-14 09:00:00','2026-02-21 09:00:00','2026-02-16 09:00:00','2026-02-14 09:00:00');
INSERT INTO invoices VALUES
  ('inv_007d','tnt_001e','INV-2026-1265','sub_0076',99,11.88,110.88,'USD','paid','[{"label":"Business plan · monthly","qty":1,"amount":99},{"label":"Tax 12%","qty":1,"amount":11.88}]','2026-03-16 09:00:00','2026-03-23 09:00:00','2026-03-18 09:00:00','2026-03-16 09:00:00'),
  ('inv_007g','tnt_001e','INV-2026-1268','sub_0076',99,11.88,110.88,'USD','paid','[{"label":"Business plan · monthly","qty":1,"amount":99},{"label":"Tax 12%","qty":1,"amount":11.88}]','2026-04-15 09:00:00','2026-04-22 09:00:00','2026-04-17 09:00:00','2026-04-15 09:00:00'),
  ('inv_007j','tnt_001e','INV-2026-1271','sub_0076',99,11.88,110.88,'USD','paid','[{"label":"Business plan · monthly","qty":1,"amount":99},{"label":"Tax 12%","qty":1,"amount":11.88}]','2026-05-15 09:00:00','2026-05-22 09:00:00','2026-05-17 09:00:00','2026-05-15 09:00:00'),
  ('inv_007m','tnt_001e','INV-2026-1274','sub_0076',99,11.88,110.88,'USD','paid','[{"label":"Business plan · monthly","qty":1,"amount":99},{"label":"Tax 12%","qty":1,"amount":11.88}]','2026-06-14 09:00:00','2026-06-21 09:00:00','2026-06-16 09:00:00','2026-06-14 09:00:00'),
  ('inv_007p','tnt_001e','INV-2026-1277','sub_0076',99,11.88,110.88,'USD','paid','[{"label":"Business plan · monthly","qty":1,"amount":99},{"label":"Tax 12%","qty":1,"amount":11.88}]','2026-07-14 09:00:00','2026-07-21 09:00:00','2026-07-16 09:00:00','2026-07-14 09:00:00'),
  ('inv_007s','tnt_001e','INV-2026-1280','sub_0076',99,11.88,110.88,'USD','paid','[{"label":"Business plan · monthly","qty":1,"amount":99},{"label":"Tax 12%","qty":1,"amount":11.88}]','2026-08-13 09:00:00','2026-08-20 09:00:00','2026-08-15 09:00:00','2026-08-13 09:00:00'),
  ('inv_007v','tnt_001e','INV-2026-1283','sub_0076',99,11.88,110.88,'USD','paid','[{"label":"Business plan · monthly","qty":1,"amount":99},{"label":"Tax 12%","qty":1,"amount":11.88}]','2026-09-12 09:00:00','2026-09-19 09:00:00','2026-09-14 09:00:00','2026-09-12 09:00:00'),
  ('inv_0084','tnt_001f','INV-2025-1292','sub_0083',29,3.48,32.48,'USD','paid','[{"label":"Starter plan · monthly","qty":1,"amount":29},{"label":"Tax 12%","qty":1,"amount":3.48}]','2025-11-16 09:00:00','2025-11-23 09:00:00','2025-11-18 09:00:00','2025-11-16 09:00:00'),
  ('inv_0087','tnt_001f','INV-2025-1295','sub_0083',29,3.48,32.48,'USD','paid','[{"label":"Starter plan · monthly","qty":1,"amount":29},{"label":"Tax 12%","qty":1,"amount":3.48}]','2025-12-16 09:00:00','2025-12-23 09:00:00','2025-12-18 09:00:00','2025-12-16 09:00:00'),
  ('inv_008a','tnt_001f','INV-2026-1298','sub_0083',29,3.48,32.48,'USD','paid','[{"label":"Starter plan · monthly","qty":1,"amount":29},{"label":"Tax 12%","qty":1,"amount":3.48}]','2026-01-15 09:00:00','2026-01-22 09:00:00','2026-01-17 09:00:00','2026-01-15 09:00:00'),
  ('inv_008d','tnt_001f','INV-2026-1301','sub_0083',29,3.48,32.48,'USD','paid','[{"label":"Starter plan · monthly","qty":1,"amount":29},{"label":"Tax 12%","qty":1,"amount":3.48}]','2026-02-14 09:00:00','2026-02-21 09:00:00','2026-02-16 09:00:00','2026-02-14 09:00:00'),
  ('inv_008g','tnt_001f','INV-2026-1304','sub_0083',29,3.48,32.48,'USD','paid','[{"label":"Starter plan · monthly","qty":1,"amount":29},{"label":"Tax 12%","qty":1,"amount":3.48}]','2026-03-16 09:00:00','2026-03-23 09:00:00','2026-03-18 09:00:00','2026-03-16 09:00:00'),
  ('inv_008j','tnt_001f','INV-2026-1307','sub_0083',29,3.48,32.48,'USD','paid','[{"label":"Starter plan · monthly","qty":1,"amount":29},{"label":"Tax 12%","qty":1,"amount":3.48}]','2026-04-15 09:00:00','2026-04-22 09:00:00','2026-04-17 09:00:00','2026-04-15 09:00:00'),
  ('inv_008m','tnt_001f','INV-2026-1310','sub_0083',29,3.48,32.48,'USD','paid','[{"label":"Starter plan · monthly","qty":1,"amount":29},{"label":"Tax 12%","qty":1,"amount":3.48}]','2026-05-15 09:00:00','2026-05-22 09:00:00','2026-05-17 09:00:00','2026-05-15 09:00:00'),
  ('inv_008p','tnt_001f','INV-2026-1313','sub_0083',29,3.48,32.48,'USD','paid','[{"label":"Starter plan · monthly","qty":1,"amount":29},{"label":"Tax 12%","qty":1,"amount":3.48}]','2026-06-14 09:00:00','2026-06-21 09:00:00','2026-06-16 09:00:00','2026-06-14 09:00:00'),
  ('inv_008s','tnt_001f','INV-2026-1316','sub_0083',29,3.48,32.48,'USD','paid','[{"label":"Starter plan · monthly","qty":1,"amount":29},{"label":"Tax 12%","qty":1,"amount":3.48}]','2026-07-14 09:00:00','2026-07-21 09:00:00','2026-07-16 09:00:00','2026-07-14 09:00:00'),
  ('inv_008v','tnt_001f','INV-2026-1319','sub_0083',29,3.48,32.48,'USD','paid','[{"label":"Starter plan · monthly","qty":1,"amount":29},{"label":"Tax 12%","qty":1,"amount":3.48}]','2026-08-13 09:00:00','2026-08-20 09:00:00','2026-08-15 09:00:00','2026-08-13 09:00:00'),
  ('inv_008y','tnt_001f','INV-2026-1322','sub_0083',29,3.48,32.48,'USD','paid','[{"label":"Starter plan · monthly","qty":1,"amount":29},{"label":"Tax 12%","qty":1,"amount":3.48}]','2026-09-12 09:00:00','2026-09-19 09:00:00','2026-09-14 09:00:00','2026-09-12 09:00:00'),
  ('inv_0097','tnt_001g','INV-2026-1331','sub_0096',99,11.88,110.88,'USD','paid','[{"label":"Business plan · monthly","qty":1,"amount":99},{"label":"Tax 12%","qty":1,"amount":11.88}]','2026-07-14 09:00:00','2026-07-21 09:00:00','2026-07-16 09:00:00','2026-07-14 09:00:00'),
  ('inv_009a','tnt_001g','INV-2026-1334','sub_0096',99,11.88,110.88,'USD','paid','[{"label":"Business plan · monthly","qty":1,"amount":99},{"label":"Tax 12%","qty":1,"amount":11.88}]','2026-08-13 09:00:00','2026-08-20 09:00:00','2026-08-15 09:00:00','2026-08-13 09:00:00'),
  ('inv_009d','tnt_001g','INV-2026-1337','sub_0096',99,11.88,110.88,'USD','paid','[{"label":"Business plan · monthly","qty":1,"amount":99},{"label":"Tax 12%","qty":1,"amount":11.88}]','2026-09-12 09:00:00','2026-09-19 09:00:00','2026-09-14 09:00:00','2026-09-12 09:00:00'),
  ('inv_009m','tnt_001h','INV-2026-1346','sub_009l',299,35.88,334.88,'USD','paid','[{"label":"Enterprise plan · monthly","qty":1,"amount":299},{"label":"Tax 12%","qty":1,"amount":35.88}]','2026-03-16 09:00:00','2026-03-23 09:00:00','2026-03-18 09:00:00','2026-03-16 09:00:00'),
  ('inv_009p','tnt_001h','INV-2026-1349','sub_009l',299,35.88,334.88,'USD','paid','[{"label":"Enterprise plan · monthly","qty":1,"amount":299},{"label":"Tax 12%","qty":1,"amount":35.88}]','2026-04-15 09:00:00','2026-04-22 09:00:00','2026-04-17 09:00:00','2026-04-15 09:00:00'),
  ('inv_009s','tnt_001h','INV-2026-1352','sub_009l',299,35.88,334.88,'USD','paid','[{"label":"Enterprise plan · monthly","qty":1,"amount":299},{"label":"Tax 12%","qty":1,"amount":35.88}]','2026-05-15 09:00:00','2026-05-22 09:00:00','2026-05-17 09:00:00','2026-05-15 09:00:00'),
  ('inv_009v','tnt_001h','INV-2026-1355','sub_009l',299,35.88,334.88,'USD','paid','[{"label":"Enterprise plan · monthly","qty":1,"amount":299},{"label":"Tax 12%","qty":1,"amount":35.88}]','2026-06-14 09:00:00','2026-06-21 09:00:00','2026-06-16 09:00:00','2026-06-14 09:00:00'),
  ('inv_009y','tnt_001h','INV-2026-1358','sub_009l',299,35.88,334.88,'USD','paid','[{"label":"Enterprise plan · monthly","qty":1,"amount":299},{"label":"Tax 12%","qty":1,"amount":35.88}]','2026-07-14 09:00:00','2026-07-21 09:00:00','2026-07-16 09:00:00','2026-07-14 09:00:00'),
  ('inv_00a1','tnt_001h','INV-2026-1361','sub_009l',299,35.88,334.88,'USD','paid','[{"label":"Enterprise plan · monthly","qty":1,"amount":299},{"label":"Tax 12%","qty":1,"amount":35.88}]','2026-08-13 09:00:00','2026-08-20 09:00:00','2026-08-15 09:00:00','2026-08-13 09:00:00'),
  ('inv_00a4','tnt_001h','INV-2026-1364','sub_009l',299,35.88,334.88,'USD','paid','[{"label":"Enterprise plan · monthly","qty":1,"amount":299},{"label":"Tax 12%","qty":1,"amount":35.88}]','2026-09-12 09:00:00','2026-09-19 09:00:00','2026-09-14 09:00:00','2026-09-12 09:00:00');

-- payments: 77 rows
INSERT INTO payments VALUES
  ('pay_001y','tnt_0016','inv_001x','sandbox','sandbox_ref_001z',110.88,'USD','succeeded',NULL,'2026-03-18 09:00:00'),
  ('pay_0021','tnt_0016','inv_0020','sandbox','sandbox_ref_0022',110.88,'USD','succeeded',NULL,'2026-04-17 09:00:00'),
  ('pay_0024','tnt_0016','inv_0023','sandbox','sandbox_ref_0025',110.88,'USD','succeeded',NULL,'2026-05-17 09:00:00'),
  ('pay_0027','tnt_0016','inv_0026','sandbox','sandbox_ref_0028',110.88,'USD','succeeded',NULL,'2026-06-16 09:00:00'),
  ('pay_002a','tnt_0016','inv_0029','sandbox','sandbox_ref_002b',110.88,'USD','succeeded',NULL,'2026-07-16 09:00:00'),
  ('pay_002d','tnt_0016','inv_002c','sandbox','sandbox_ref_002e',110.88,'USD','succeeded',NULL,'2026-08-15 09:00:00'),
  ('pay_002g','tnt_0016','inv_002f','sandbox','sandbox_ref_002h',110.88,'USD','succeeded',NULL,'2026-09-14 09:00:00'),
  ('pay_002p','tnt_0017','inv_002o','sandbox','sandbox_ref_002q',334.88,'USD','succeeded',NULL,'2025-10-19 09:00:00'),
  ('pay_002s','tnt_0017','inv_002r','sandbox','sandbox_ref_002t',334.88,'USD','succeeded',NULL,'2025-11-18 09:00:00'),
  ('pay_002v','tnt_0017','inv_002u','sandbox','sandbox_ref_002w',334.88,'USD','succeeded',NULL,'2025-12-18 09:00:00'),
  ('pay_002y','tnt_0017','inv_002x','sandbox','sandbox_ref_002z',334.88,'USD','succeeded',NULL,'2026-01-17 09:00:00'),
  ('pay_0031','tnt_0017','inv_0030','sandbox','sandbox_ref_0032',334.88,'USD','succeeded',NULL,'2026-02-16 09:00:00'),
  ('pay_0034','tnt_0017','inv_0033','sandbox','sandbox_ref_0035',334.88,'USD','succeeded',NULL,'2026-03-18 09:00:00'),
  ('pay_0037','tnt_0017','inv_0036','sandbox','sandbox_ref_0038',334.88,'USD','succeeded',NULL,'2026-04-17 09:00:00'),
  ('pay_003a','tnt_0017','inv_0039','sandbox','sandbox_ref_003b',334.88,'USD','succeeded',NULL,'2026-05-17 09:00:00'),
  ('pay_003d','tnt_0017','inv_003c','sandbox','sandbox_ref_003e',334.88,'USD','succeeded',NULL,'2026-06-16 09:00:00'),
  ('pay_003g','tnt_0017','inv_003f','sandbox','sandbox_ref_003h',334.88,'USD','succeeded',NULL,'2026-07-16 09:00:00'),
  ('pay_003j','tnt_0017','inv_003i','sandbox','sandbox_ref_003k',334.88,'USD','succeeded',NULL,'2026-08-15 09:00:00'),
  ('pay_003m','tnt_0017','inv_003l','sandbox','sandbox_ref_003n',334.88,'USD','succeeded',NULL,'2026-09-14 09:00:00'),
  ('pay_003v','tnt_0018','inv_003u','sandbox','sandbox_ref_003w',334.88,'USD','succeeded',NULL,'2026-04-17 09:00:00'),
  ('pay_003y','tnt_0018','inv_003x','sandbox','sandbox_ref_003z',334.88,'USD','succeeded',NULL,'2026-05-17 09:00:00'),
  ('pay_0041','tnt_0018','inv_0040','sandbox','sandbox_ref_0042',334.88,'USD','succeeded',NULL,'2026-06-16 09:00:00'),
  ('pay_0044','tnt_0018','inv_0043','sandbox','sandbox_ref_0045',334.88,'USD','succeeded',NULL,'2026-07-16 09:00:00'),
  ('pay_0047','tnt_0018','inv_0046','sandbox','sandbox_ref_0048',334.88,'USD','succeeded',NULL,'2026-08-15 09:00:00'),
  ('pay_004a','tnt_0018','inv_0049','sandbox','sandbox_ref_004b',334.88,'USD','succeeded',NULL,'2026-09-14 09:00:00'),
  ('pay_004j','tnt_0019','inv_004i','sandbox','sandbox_ref_004k',110.88,'USD','succeeded',NULL,'2026-06-16 09:00:00'),
  ('pay_004m','tnt_0019','inv_004l','sandbox','sandbox_ref_004n',110.88,'USD','succeeded',NULL,'2026-07-16 09:00:00'),
  ('pay_004p','tnt_0019','inv_004o','sandbox','sandbox_ref_004q',110.88,'USD','succeeded',NULL,'2026-08-15 09:00:00'),
  ('pay_004s','tnt_0019','inv_004r','sandbox','sandbox_ref_004t',110.88,'USD','succeeded',NULL,'2026-09-14 09:00:00'),
  ('pay_0051','tnt_001a','inv_0050','sandbox','sandbox_ref_0052',32.48,'USD','succeeded',NULL,'2026-09-14 09:00:00'),
  ('pay_005a','tnt_001b','inv_0059','sandbox','sandbox_ref_005b',32.48,'USD','succeeded',NULL,'2026-07-16 09:00:00'),
  ('pay_005d','tnt_001b','inv_005c','sandbox','sandbox_ref_005e',32.48,'USD','succeeded',NULL,'2026-08-15 09:00:00'),
  ('pay_005n','tnt_001c','inv_005m','sandbox','sandbox_ref_005o',110.88,'USD','succeeded',NULL,'2026-05-17 09:00:00'),
  ('pay_005q','tnt_001c','inv_005p','sandbox','sandbox_ref_005r',110.88,'USD','succeeded',NULL,'2026-06-16 09:00:00'),
  ('pay_005t','tnt_001c','inv_005s','sandbox','sandbox_ref_005u',110.88,'USD','succeeded',NULL,'2026-07-16 09:00:00'),
  ('pay_005w','tnt_001c','inv_005v','sandbox','sandbox_ref_005x',110.88,'USD','succeeded',NULL,'2026-08-15 09:00:00'),
  ('pay_005z','tnt_001c','inv_005y','sandbox','sandbox_ref_0060',110.88,'USD','succeeded',NULL,'2026-09-14 09:00:00'),
  ('pay_0068','tnt_001d','inv_0067','sandbox','sandbox_ref_0069',334.88,'USD','succeeded',NULL,'2025-12-18 09:00:00'),
  ('pay_006b','tnt_001d','inv_006a','sandbox','sandbox_ref_006c',334.88,'USD','succeeded',NULL,'2026-01-17 09:00:00'),
  ('pay_006e','tnt_001d','inv_006d','sandbox','sandbox_ref_006f',334.88,'USD','succeeded',NULL,'2026-02-16 09:00:00'),
  ('pay_006h','tnt_001d','inv_006g','sandbox','sandbox_ref_006i',334.88,'USD','succeeded',NULL,'2026-03-18 09:00:00'),
  ('pay_006k','tnt_001d','inv_006j','sandbox','sandbox_ref_006l',334.88,'USD','succeeded',NULL,'2026-04-17 09:00:00'),
  ('pay_006n','tnt_001d','inv_006m','sandbox','sandbox_ref_006o',334.88,'USD','succeeded',NULL,'2026-05-17 09:00:00'),
  ('pay_006q','tnt_001d','inv_006p','sandbox','sandbox_ref_006r',334.88,'USD','succeeded',NULL,'2026-06-16 09:00:00'),
  ('pay_006t','tnt_001d','inv_006s','sandbox','sandbox_ref_006u',334.88,'USD','succeeded',NULL,'2026-07-16 09:00:00'),
  ('pay_006w','tnt_001d','inv_006v','sandbox','sandbox_ref_006x',334.88,'USD','succeeded',NULL,'2026-08-15 09:00:00'),
  ('pay_006z','tnt_001d','inv_006y','sandbox','sandbox_ref_0070',334.88,'USD','succeeded',NULL,'2026-09-14 09:00:00'),
  ('pay_0078','tnt_001e','inv_0077','sandbox','sandbox_ref_0079',110.88,'USD','succeeded',NULL,'2026-01-17 09:00:00'),
  ('pay_007b','tnt_001e','inv_007a','sandbox','sandbox_ref_007c',110.88,'USD','succeeded',NULL,'2026-02-16 09:00:00'),
  ('pay_007e','tnt_001e','inv_007d','sandbox','sandbox_ref_007f',110.88,'USD','succeeded',NULL,'2026-03-18 09:00:00');
INSERT INTO payments VALUES
  ('pay_007h','tnt_001e','inv_007g','sandbox','sandbox_ref_007i',110.88,'USD','succeeded',NULL,'2026-04-17 09:00:00'),
  ('pay_007k','tnt_001e','inv_007j','sandbox','sandbox_ref_007l',110.88,'USD','succeeded',NULL,'2026-05-17 09:00:00'),
  ('pay_007n','tnt_001e','inv_007m','sandbox','sandbox_ref_007o',110.88,'USD','succeeded',NULL,'2026-06-16 09:00:00'),
  ('pay_007q','tnt_001e','inv_007p','sandbox','sandbox_ref_007r',110.88,'USD','succeeded',NULL,'2026-07-16 09:00:00'),
  ('pay_007t','tnt_001e','inv_007s','sandbox','sandbox_ref_007u',110.88,'USD','succeeded',NULL,'2026-08-15 09:00:00'),
  ('pay_007w','tnt_001e','inv_007v','sandbox','sandbox_ref_007x',110.88,'USD','succeeded',NULL,'2026-09-14 09:00:00'),
  ('pay_0085','tnt_001f','inv_0084','sandbox','sandbox_ref_0086',32.48,'USD','succeeded',NULL,'2025-11-18 09:00:00'),
  ('pay_0088','tnt_001f','inv_0087','sandbox','sandbox_ref_0089',32.48,'USD','succeeded',NULL,'2025-12-18 09:00:00'),
  ('pay_008b','tnt_001f','inv_008a','sandbox','sandbox_ref_008c',32.48,'USD','succeeded',NULL,'2026-01-17 09:00:00'),
  ('pay_008e','tnt_001f','inv_008d','sandbox','sandbox_ref_008f',32.48,'USD','succeeded',NULL,'2026-02-16 09:00:00'),
  ('pay_008h','tnt_001f','inv_008g','sandbox','sandbox_ref_008i',32.48,'USD','succeeded',NULL,'2026-03-18 09:00:00'),
  ('pay_008k','tnt_001f','inv_008j','sandbox','sandbox_ref_008l',32.48,'USD','succeeded',NULL,'2026-04-17 09:00:00'),
  ('pay_008n','tnt_001f','inv_008m','sandbox','sandbox_ref_008o',32.48,'USD','succeeded',NULL,'2026-05-17 09:00:00'),
  ('pay_008q','tnt_001f','inv_008p','sandbox','sandbox_ref_008r',32.48,'USD','succeeded',NULL,'2026-06-16 09:00:00'),
  ('pay_008t','tnt_001f','inv_008s','sandbox','sandbox_ref_008u',32.48,'USD','succeeded',NULL,'2026-07-16 09:00:00'),
  ('pay_008w','tnt_001f','inv_008v','sandbox','sandbox_ref_008x',32.48,'USD','succeeded',NULL,'2026-08-15 09:00:00'),
  ('pay_008z','tnt_001f','inv_008y','sandbox','sandbox_ref_0090',32.48,'USD','succeeded',NULL,'2026-09-14 09:00:00'),
  ('pay_0098','tnt_001g','inv_0097','sandbox','sandbox_ref_0099',110.88,'USD','succeeded',NULL,'2026-07-16 09:00:00'),
  ('pay_009b','tnt_001g','inv_009a','sandbox','sandbox_ref_009c',110.88,'USD','succeeded',NULL,'2026-08-15 09:00:00'),
  ('pay_009e','tnt_001g','inv_009d','sandbox','sandbox_ref_009f',110.88,'USD','succeeded',NULL,'2026-09-14 09:00:00'),
  ('pay_009n','tnt_001h','inv_009m','sandbox','sandbox_ref_009o',334.88,'USD','succeeded',NULL,'2026-03-18 09:00:00'),
  ('pay_009q','tnt_001h','inv_009p','sandbox','sandbox_ref_009r',334.88,'USD','succeeded',NULL,'2026-04-17 09:00:00'),
  ('pay_009t','tnt_001h','inv_009s','sandbox','sandbox_ref_009u',334.88,'USD','succeeded',NULL,'2026-05-17 09:00:00'),
  ('pay_009w','tnt_001h','inv_009v','sandbox','sandbox_ref_009x',334.88,'USD','succeeded',NULL,'2026-06-16 09:00:00'),
  ('pay_009z','tnt_001h','inv_009y','sandbox','sandbox_ref_00a0',334.88,'USD','succeeded',NULL,'2026-07-16 09:00:00'),
  ('pay_00a2','tnt_001h','inv_00a1','sandbox','sandbox_ref_00a3',334.88,'USD','succeeded',NULL,'2026-08-15 09:00:00'),
  ('pay_00a5','tnt_001h','inv_00a4','sandbox','sandbox_ref_00a6',334.88,'USD','succeeded',NULL,'2026-09-14 09:00:00');

-- usage_records: 48 rows
INSERT INTO usage_records VALUES
  ('usg_002i','tnt_0016','seats',6,25,'2026-09','2026-10-11 09:00:00'),
  ('usg_002j','tnt_0016','api_requests',10671,100000,'2026-09','2026-10-11 09:00:00'),
  ('usg_002k','tnt_0016','ai_minutes',0,300,'2026-09','2026-10-11 09:00:00'),
  ('usg_002l','tnt_0016','storage_gb',47,50,'2026-09','2026-10-11 09:00:00'),
  ('usg_003o','tnt_0017','seats',41,150,'2026-09','2026-10-11 09:00:00'),
  ('usg_003p','tnt_0017','api_requests',43248,2000000,'2026-09','2026-10-11 09:00:00'),
  ('usg_003q','tnt_0017','ai_minutes',1464,3000,'2026-09','2026-10-11 09:00:00'),
  ('usg_003r','tnt_0017','storage_gb',57,500,'2026-09','2026-10-11 09:00:00'),
  ('usg_004c','tnt_0018','seats',112,150,'2026-09','2026-10-11 09:00:00'),
  ('usg_004d','tnt_0018','api_requests',49841,2000000,'2026-09','2026-10-11 09:00:00'),
  ('usg_004e','tnt_0018','ai_minutes',481,3000,'2026-09','2026-10-11 09:00:00'),
  ('usg_004f','tnt_0018','storage_gb',6,500,'2026-09','2026-10-11 09:00:00'),
  ('usg_004u','tnt_0019','seats',20,25,'2026-09','2026-10-11 09:00:00'),
  ('usg_004v','tnt_0019','api_requests',28231,100000,'2026-09','2026-10-11 09:00:00'),
  ('usg_004w','tnt_0019','ai_minutes',0,300,'2026-09','2026-10-11 09:00:00'),
  ('usg_004x','tnt_0019','storage_gb',20,50,'2026-09','2026-10-11 09:00:00'),
  ('usg_0053','tnt_001a','seats',2,5,'2026-09','2026-10-11 09:00:00'),
  ('usg_0054','tnt_001a','api_requests',3070,10000,'2026-09','2026-10-11 09:00:00'),
  ('usg_0055','tnt_001a','ai_minutes',0,0,'2026-09','2026-10-11 09:00:00'),
  ('usg_0056','tnt_001a','storage_gb',1,5,'2026-09','2026-10-11 09:00:00'),
  ('usg_005g','tnt_001b','seats',4,5,'2026-09','2026-10-11 09:00:00'),
  ('usg_005h','tnt_001b','api_requests',3047,10000,'2026-09','2026-10-11 09:00:00'),
  ('usg_005i','tnt_001b','ai_minutes',0,0,'2026-09','2026-10-11 09:00:00'),
  ('usg_005j','tnt_001b','storage_gb',2,5,'2026-09','2026-10-11 09:00:00'),
  ('usg_0061','tnt_001c','seats',17,25,'2026-09','2026-10-11 09:00:00'),
  ('usg_0062','tnt_001c','api_requests',23317,100000,'2026-09','2026-10-11 09:00:00'),
  ('usg_0063','tnt_001c','ai_minutes',0,300,'2026-09','2026-10-11 09:00:00'),
  ('usg_0064','tnt_001c','storage_gb',5,50,'2026-09','2026-10-11 09:00:00'),
  ('usg_0071','tnt_001d','seats',71,150,'2026-09','2026-10-11 09:00:00'),
  ('usg_0072','tnt_001d','api_requests',393811,2000000,'2026-09','2026-10-11 09:00:00'),
  ('usg_0073','tnt_001d','ai_minutes',2831,3000,'2026-09','2026-10-11 09:00:00'),
  ('usg_0074','tnt_001d','storage_gb',26,500,'2026-09','2026-10-11 09:00:00'),
  ('usg_007y','tnt_001e','seats',14,25,'2026-09','2026-10-11 09:00:00'),
  ('usg_007z','tnt_001e','api_requests',26002,100000,'2026-09','2026-10-11 09:00:00'),
  ('usg_0080','tnt_001e','ai_minutes',0,300,'2026-09','2026-10-11 09:00:00'),
  ('usg_0081','tnt_001e','storage_gb',21,50,'2026-09','2026-10-11 09:00:00'),
  ('usg_0091','tnt_001f','seats',5,5,'2026-09','2026-10-11 09:00:00'),
  ('usg_0092','tnt_001f','api_requests',2299,10000,'2026-09','2026-10-11 09:00:00'),
  ('usg_0093','tnt_001f','ai_minutes',0,0,'2026-09','2026-10-11 09:00:00'),
  ('usg_0094','tnt_001f','storage_gb',5,5,'2026-09','2026-10-11 09:00:00'),
  ('usg_009g','tnt_001g','seats',16,25,'2026-09','2026-10-11 09:00:00'),
  ('usg_009h','tnt_001g','api_requests',28982,100000,'2026-09','2026-10-11 09:00:00'),
  ('usg_009i','tnt_001g','ai_minutes',0,300,'2026-09','2026-10-11 09:00:00'),
  ('usg_009j','tnt_001g','storage_gb',11,50,'2026-09','2026-10-11 09:00:00'),
  ('usg_00a7','tnt_001h','seats',14,150,'2026-09','2026-10-11 09:00:00'),
  ('usg_00a8','tnt_001h','api_requests',113931,2000000,'2026-09','2026-10-11 09:00:00'),
  ('usg_00a9','tnt_001h','ai_minutes',2452,3000,'2026-09','2026-10-11 09:00:00'),
  ('usg_00aa','tnt_001h','storage_gb',431,500,'2026-09','2026-10-11 09:00:00');

-- billing_events: 12 rows
INSERT INTO billing_events VALUES
  ('bev_002m','tnt_0016','subscription.created','Business · sandbox','2026-03-16 09:00:00'),
  ('bev_003s','tnt_0017','subscription.created','Enterprise · sandbox','2025-09-07 09:00:00'),
  ('bev_004g','tnt_0018','subscription.created','Enterprise · sandbox','2026-04-15 09:00:00'),
  ('bev_004y','tnt_0019','subscription.created','Business · sandbox','2026-06-14 09:00:00'),
  ('bev_0057','tnt_001a','subscription.created','Starter · sandbox','2026-10-03 09:00:00'),
  ('bev_005k','tnt_001b','subscription.created','Starter · sandbox','2026-07-09 09:00:00'),
  ('bev_0065','tnt_001c','subscription.created','Business · sandbox','2026-05-15 09:00:00'),
  ('bev_0075','tnt_001d','subscription.created','Enterprise · sandbox','2025-12-16 09:00:00'),
  ('bev_0082','tnt_001e','subscription.created','Business · sandbox','2026-01-25 09:00:00'),
  ('bev_0095','tnt_001f','subscription.created','Starter · sandbox','2025-11-06 09:00:00'),
  ('bev_009k','tnt_001g','subscription.created','Business · sandbox','2026-07-16 09:00:00'),
  ('bev_00ab','tnt_001h','subscription.created','Enterprise · sandbox','2026-03-06 09:00:00');

-- pipelines: 2 rows
INSERT INTO pipelines VALUES
  ('pip_00ac','tnt_0016','Sales Pipeline',1,'2026-03-16 09:00:00'),
  ('pip_00al','tnt_0017','Sales Pipeline',1,'2025-09-07 09:00:00');

-- pipeline_stages: 16 rows
INSERT INTO pipeline_stages VALUES
  ('stg_00ad','tnt_0016','pip_00ac','new','Новый лид',0,10,'open','#94A3B8'),
  ('stg_00ae','tnt_0016','pip_00ac','qualified','Квалификация',1,25,'open','#60A5FA'),
  ('stg_00af','tnt_0016','pip_00ac','contact','Контакт',2,40,'open','#3B82F6'),
  ('stg_00ag','tnt_0016','pip_00ac','negotiation','Переговоры',3,60,'open','#2563EB'),
  ('stg_00ah','tnt_0016','pip_00ac','proposal','Предложение',4,75,'open','#1D4ED8'),
  ('stg_00ai','tnt_0016','pip_00ac','agreed','Согласовано',5,90,'open','#0EA5E9'),
  ('stg_00aj','tnt_0016','pip_00ac','order','Заказ',6,95,'won','#10B981'),
  ('stg_00ak','tnt_0016','pip_00ac','done','Выполнено',7,100,'won','#059669'),
  ('stg_00am','tnt_0017','pip_00al','new','Новый лид',0,10,'open','#94A3B8'),
  ('stg_00an','tnt_0017','pip_00al','qualified','Квалификация',1,25,'open','#60A5FA'),
  ('stg_00ao','tnt_0017','pip_00al','contact','Контакт',2,40,'open','#3B82F6'),
  ('stg_00ap','tnt_0017','pip_00al','negotiation','Переговоры',3,60,'open','#2563EB'),
  ('stg_00aq','tnt_0017','pip_00al','proposal','Предложение',4,75,'open','#1D4ED8'),
  ('stg_00ar','tnt_0017','pip_00al','agreed','Согласовано',5,90,'open','#0EA5E9'),
  ('stg_00as','tnt_0017','pip_00al','order','Заказ',6,95,'won','#10B981'),
  ('stg_00at','tnt_0017','pip_00al','done','Выполнено',7,100,'won','#059669');

-- organizations: 46 rows
INSERT INTO organizations VALUES
  ('org_00au','tnt_0016','Acme Corp','Healthcare','https://acmecorp.uz','info@acmecorp.uz','+998 75 245 72 58','UZ','Navoiy','Chilonzor 69','200+','active','vip','usr_000s','["new"]',NULL,'2026-08-22 09:00:00','2026-10-01 09:00:00'),
  ('org_00ax','tnt_0016','Globex Industries','Marketing','https://globexindustries.uz','info@globexindustries.uz','+998 89 497 39 84','UZ','Bukhara','Navoi 162','200+','active','vip','usr_000r','["new"]',NULL,'2026-07-06 09:00:00','2026-09-30 09:00:00'),
  ('org_00b1','tnt_0016','Orbit Systems','Construction','https://orbitsystems.uz','info@orbitsystems.uz','+998 90 183 37 33','UZ','Navoiy','Chilonzor 120','1–10','active','vip','usr_000s','["new"]',NULL,'2026-06-13 09:00:00','2026-09-30 09:00:00'),
  ('org_00b6','tnt_0016','TechNova','Logistics','https://technova.uz','info@technova.uz','+998 97 192 24 58','UZ','Samarkand','Chilonzor 165','51–200','prospect','standard','usr_000t','["sme"]',NULL,'2026-05-09 09:00:00','2026-10-07 09:00:00'),
  ('org_00b9','tnt_0016','Initech LLC','Construction','https://initechllc.uz','info@initechllc.uz','+998 87 533 77 37','UZ','Navoiy','Amir Temur 37','11–50','active','standard','usr_000y','["enterprise"]',NULL,'2026-01-18 09:00:00','2026-10-10 09:00:00'),
  ('org_00be','tnt_0016','Stark Industries','Marketing','https://starkindustries.uz','info@starkindustries.uz','+998 73 324 30 57','UZ','Fergana','Bobur 7','200+','prospect','standard','usr_000w','["partner"]',NULL,'2026-02-18 09:00:00','2026-10-02 09:00:00'),
  ('org_00bh','tnt_0016','Umbrella Co','Manufacturing','https://umbrellaco.uz','info@umbrellaco.uz','+998 70 556 78 58','UZ','Samarkand','Amir Temur 107','1–10','active','standard','usr_000x','["partner"]',NULL,'2026-04-20 09:00:00','2026-10-12 09:00:00'),
  ('org_00bm','tnt_0016','BrightWave Digital','Telecom','https://brightwavedigital.uz','info@brightwavedigital.uz','+998 88 970 13 63','UZ','Tashkent','Mustaqillik 65','1–10','prospect','standard','usr_000v','["enterprise"]',NULL,'2026-06-23 09:00:00','2026-10-04 09:00:00'),
  ('org_00bp','tnt_0016','Zenith Soft','Manufacturing','https://zenithsoft.uz','info@zenithsoft.uz','+998 78 413 61 83','UZ','Bukhara','Mustaqillik 83','51–200','active','standard','usr_000t','["partner"]',NULL,'2026-09-11 09:00:00','2026-10-09 09:00:00'),
  ('org_00bu','tnt_0016','PrimeCargo','Retail','https://primecargo.uz','info@primecargo.uz','+998 78 457 85 51','UZ','Navoiy','Mustaqillik 7','51–200','active','standard','usr_000v','["sme"]',NULL,'2026-02-26 09:00:00','2026-10-03 09:00:00'),
  ('org_00bx','tnt_0016','Silk Road Logistics','Software','https://silkroadlogistics.uz','info@silkroadlogistics.uz','+998 89 709 71 74','UZ','Bukhara','Mustaqillik 5','11–50','prospect','standard','usr_000w','["partner"]',NULL,'2026-04-25 09:00:00','2026-10-10 09:00:00'),
  ('org_00c0','tnt_0016','Vertex Digital','Telecom','https://vertexdigital.uz','info@vertexdigital.uz','+998 83 379 64 87','UZ','Bukhara','Mustaqillik 29','1–10','prospect','standard','usr_000v','["sme"]',NULL,'2026-06-26 09:00:00','2026-10-10 09:00:00'),
  ('org_00c3','tnt_0016','Nova Retail Group','Finance','https://novaretailgroup.uz','info@novaretailgroup.uz','+998 88 759 21 96','UZ','Samarkand','Navoi 45','200+','active','standard','usr_000r','["sme"]',NULL,'2026-05-18 09:00:00','2026-10-04 09:00:00'),
  ('org_00c6','tnt_0016','BlueOrbit Media','Retail','https://blueorbitmedia.uz','info@blueorbitmedia.uz','+998 88 186 12 74','UZ','Tashkent','Chilonzor 147','51–200','active','standard','usr_000t','["partner"]',NULL,'2026-03-06 09:00:00','2026-10-07 09:00:00'),
  ('org_00cb','tnt_0016','Uzbek Telecom','Manufacturing','https://uzbektelecom.uz','info@uzbektelecom.uz','+998 99 787 99 81','UZ','Bukhara','Mustaqillik 130','51–200','active','standard','usr_000s','["sme"]',NULL,'2026-01-01 09:00:00','2026-10-06 09:00:00'),
  ('org_00ce','tnt_0016','Fergana Textile','Retail','https://ferganatextile.uz','info@ferganatextile.uz','+998 77 185 50 34','UZ','Fergana','Chilonzor 126','1–10','prospect','standard','usr_000r','["enterprise"]',NULL,'2025-12-31 09:00:00','2026-10-11 09:00:00'),
  ('org_00ci','tnt_0016','Samarkand Foods','Software','https://samarkandfoods.uz','info@samarkandfoods.uz','+998 98 949 12 42','UZ','Samarkand','Amir Temur 88','200+','active','standard','usr_000s','["new"]',NULL,'2026-05-09 09:00:00','2026-10-05 09:00:00'),
  ('org_00cn','tnt_0016','Tashkent Motors','Logistics','https://tashkentmotors.uz','info@tashkentmotors.uz','+998 76 835 21 76','UZ','Tashkent','Navoi 180','51–200','active','standard','usr_000y','["new"]',NULL,'2026-02-15 09:00:00','2026-10-02 09:00:00'),
  ('org_00cq','tnt_0016','Aral Shipping','Finance','https://aralshipping.uz','info@aralshipping.uz','+998 96 879 49 62','UZ','Bukhara','Amir Temur 55','11–50','prospect','standard','usr_000s','["enterprise"]',NULL,'2026-09-19 09:00:00','2026-10-12 09:00:00'),
  ('org_00cu','tnt_0016','Registan Bank','Manufacturing','https://registanbank.uz','info@registanbank.uz','+998 77 168 44 38','UZ','Samarkand','Chilonzor 66','11–50','active','standard','usr_000v','["partner"]',NULL,'2026-07-18 09:00:00','2026-10-09 09:00:00'),
  ('org_00cy','tnt_0016','Chorsu Trade','Construction','https://chorsutrade.uz','info@chorsutrade.uz','+998 84 601 34 38','UZ','Navoiy','Navoi 66','51–200','active','standard','usr_000y','["sme"]',NULL,'2026-01-21 09:00:00','2026-09-30 09:00:00'),
  ('org_00d3','tnt_0016','Navoi Mining Tech','Construction','https://navoiminingtech.uz','info@navoiminingtech.uz','+998 87 593 43 53','UZ','Fergana','Bobur 141','200+','prospect','standard','usr_000x','["new"]',NULL,'2026-06-17 09:00:00','2026-10-07 09:00:00'),
  ('org_00d7','tnt_0016','Bukhara Ceramics','Retail','https://bukharaceramics.uz','info@bukharaceramics.uz','+998 79 251 49 85','UZ','Samarkand','Chilonzor 82','11–50','prospect','standard','usr_000x','["partner"]',NULL,'2026-04-04 09:00:00','2026-09-30 09:00:00'),
  ('org_00dc','tnt_0016','Khiva Travel','Software','https://khivatravel.uz','info@khivatravel.uz','+998 81 186 13 89','UZ','Navoiy','Mustaqillik 108','11–50','active','standard','usr_000u','["new"]',NULL,'2026-08-05 09:00:00','2026-10-07 09:00:00'),
  ('org_00dh','tnt_0016','Astra Industries','Retail','https://astraindustries.uz','info@astraindustries.uz','+998 83 819 13 84','UZ','Samarkand','Amir Temur 29','1–10','active','standard','usr_000r','["new"]',NULL,'2026-01-24 09:00:00','2026-10-10 09:00:00'),
  ('org_00dk','tnt_0016','Vega LLC','Retail','https://vegallc.uz','info@vegallc.uz','+998 85 251 29 28','UZ','Tashkent','Navoi 155','1–10','prospect','standard','usr_000y','["enterprise"]',NULL,'2026-06-27 09:00:00','2026-10-11 09:00:00'),
  ('org_00dp','tnt_0016','Orion LLC','Software','https://orionllc.uz','info@orionllc.uz','+998 88 704 58 39','UZ','Tashkent','Mustaqillik 98','51–200','active','standard','usr_000r','["new"]',NULL,'2026-03-29 09:00:00','2026-10-04 09:00:00'),
  ('org_00du','tnt_0016','Sirius LLC','Construction','https://siriusllc.uz','info@siriusllc.uz','+998 75 245 14 52','UZ','Fergana','Mustaqillik 78','1–10','prospect','basic','usr_000u','["enterprise"]',NULL,'2026-09-11 09:00:00','2026-10-08 09:00:00'),
  ('org_00dx','tnt_0016','Delta Trade','Healthcare','https://deltatrade.uz','info@deltatrade.uz','+998 85 525 79 80','UZ','Navoiy','Amir Temur 99','11–50','prospect','standard','usr_000y','["enterprise"]',NULL,'2026-02-08 09:00:00','2026-10-09 09:00:00'),
  ('org_00e2','tnt_0016','Sirius Systems','Education','https://siriussystems.uz','info@siriussystems.uz','+998 81 957 54 22','UZ','Navoiy','Amir Temur 142','51–200','prospect','basic','usr_000y','["partner"]',NULL,'2026-08-13 09:00:00','2026-10-05 09:00:00'),
  ('org_00e6','tnt_0016','Orion Industries','Education','https://orionindustries.uz','info@orionindustries.uz','+998 93 133 88 27','UZ','Samarkand','Navoi 58','200+','active','basic','usr_000s','["enterprise"]',NULL,'2026-07-01 09:00:00','2026-10-05 09:00:00'),
  ('org_00e9','tnt_0016','Nova LLC','Software','https://novallc.uz','info@novallc.uz','+998 99 103 87 87','UZ','Samarkand','Mustaqillik 122','1–10','active','basic','usr_000u','["sme"]',NULL,'2026-08-11 09:00:00','2026-10-12 09:00:00'),
  ('org_00ed','tnt_0016','Orion Trade','Logistics','https://oriontrade.uz','info@oriontrade.uz','+998 82 808 18 63','UZ','Fergana','Chilonzor 128','200+','active','basic','usr_000x','["partner"]',NULL,'2026-09-13 09:00:00','2026-10-01 09:00:00'),
  ('org_00ei','tnt_0016','Orion Trade','Marketing','https://oriontrade.uz','info@oriontrade.uz','+998 70 198 26 66','UZ','Samarkand','Bobur 144','1–10','prospect','standard','usr_000x','["sme"]',NULL,'2026-08-12 09:00:00','2026-10-07 09:00:00'),
  ('org_00en','tnt_0017','Acme Corp','Software','https://acmecorp.uz','info@acmecorp.uz','+998 84 141 45 92','UZ','Tashkent','Mustaqillik 66','200+','active','vip',NULL,'["enterprise"]',NULL,'2026-03-14 09:00:00','2026-10-07 09:00:00'),
  ('org_00eq','tnt_0017','Globex Industries','Healthcare','https://globexindustries.uz','info@globexindustries.uz','+998 78 669 46 58','UZ','Bukhara','Amir Temur 161','1–10','active','vip',NULL,'["enterprise"]',NULL,'2026-10-01 09:00:00','2026-10-10 09:00:00'),
  ('org_00et','tnt_0017','Orbit Systems','Education','https://orbitsystems.uz','info@orbitsystems.uz','+998 88 630 73 52','UZ','Samarkand','Mustaqillik 70','11–50','prospect','vip',NULL,'["partner"]',NULL,'2026-05-14 09:00:00','2026-10-05 09:00:00'),
  ('org_00ev','tnt_0017','TechNova','Software','https://technova.uz','info@technova.uz','+998 86 417 47 68','UZ','Navoiy','Bobur 115','200+','active','basic',NULL,'["partner"]',NULL,'2026-07-31 09:00:00','2026-10-12 09:00:00'),
  ('org_00ey','tnt_0017','Initech LLC','Education','https://initechllc.uz','info@initechllc.uz','+998 93 403 60 68','UZ','Navoiy','Bobur 77','200+','active','standard',NULL,'["partner"]',NULL,'2026-07-21 09:00:00','2026-10-10 09:00:00'),
  ('org_00f0','tnt_0017','Stark Industries','Telecom','https://starkindustries.uz','info@starkindustries.uz','+998 82 481 56 68','UZ','Bukhara','Chilonzor 118','51–200','active','standard',NULL,'["enterprise"]',NULL,'2026-04-14 09:00:00','2026-10-04 09:00:00'),
  ('org_00f2','tnt_0017','Umbrella Co','Software','https://umbrellaco.uz','info@umbrellaco.uz','+998 74 902 95 43','UZ','Navoiy','Chilonzor 90','11–50','active','basic',NULL,'["sme"]',NULL,'2026-05-05 09:00:00','2026-10-03 09:00:00'),
  ('org_00f5','tnt_0017','BrightWave Digital','Manufacturing','https://brightwavedigital.uz','info@brightwavedigital.uz','+998 90 152 13 41','UZ','Tashkent','Navoi 19','51–200','active','standard',NULL,'["new"]',NULL,'2026-05-12 09:00:00','2026-10-12 09:00:00'),
  ('org_00f7','tnt_0017','Zenith Soft','Finance','https://zenithsoft.uz','info@zenithsoft.uz','+998 95 813 11 36','UZ','Fergana','Navoi 34','51–200','active','standard',NULL,'["enterprise"]',NULL,'2026-08-06 09:00:00','2026-10-01 09:00:00'),
  ('org_00f9','tnt_0017','PrimeCargo','Marketing','https://primecargo.uz','info@primecargo.uz','+998 88 829 41 49','UZ','Tashkent','Amir Temur 55','200+','active','basic',NULL,'["partner"]',NULL,'2026-01-01 09:00:00','2026-10-12 09:00:00'),
  ('org_00fc','tnt_0017','Silk Road Logistics','Software','https://silkroadlogistics.uz','info@silkroadlogistics.uz','+998 71 205 51 73','UZ','Bukhara','Mustaqillik 107','200+','active','basic',NULL,'["partner"]',NULL,'2026-04-10 09:00:00','2026-10-09 09:00:00'),
  ('org_00ff','tnt_0017','Vertex Digital','Marketing','https://vertexdigital.uz','info@vertexdigital.uz','+998 79 661 67 47','UZ','Samarkand','Chilonzor 30','11–50','active','basic',NULL,'["enterprise"]',NULL,'2026-03-07 09:00:00','2026-10-06 09:00:00');

-- contacts: 121 rows
INSERT INTO contacts VALUES
  ('con_00av','tnt_0016','org_00au','Nargiza','Nazarov','Финансовый директор','nargiza.nazarov@acmecorp.uz','+998 79 586 53 19',NULL,'api','active',1,1,1,'usr_000s','["influencer"]','2026-03-04 09:00:00','2026-10-09 09:00:00'),
  ('con_00aw','tnt_0016','org_00au','Nargiza','Saidova','Закупщик','nargiza.saidova@acmecorp.uz','+998 80 183 37 76','@nargiza7','cold_call','active',0,1,0,'usr_000s','["champion"]','2026-01-24 09:00:00','2026-10-03 09:00:00'),
  ('con_00ay','tnt_0016','org_00ax','Nilufar','Abdullaeva','CTO','nilufar.abdullaeva@globexindustries.uz','+998 76 954 43 80','@nilufar7','telegram','active',1,1,1,'usr_000r','["influencer"]','2026-06-12 09:00:00','2026-10-06 09:00:00'),
  ('con_00az','tnt_0016','org_00ax','Shohruh','Pulatov','Директор по продажам','shohruh.pulatov@globexindustries.uz','+998 95 864 19 60',NULL,'instagram','active',0,0,0,'usr_000r','["champion"]','2026-08-06 09:00:00','2026-10-02 09:00:00'),
  ('con_00b0','tnt_0016','org_00ax','Iskander','Karimov','Операционный директор','iskander.karimov@globexindustries.uz','+998 83 172 35 49',NULL,'instagram','active',0,0,0,'usr_000r','["champion"]','2026-09-17 09:00:00','2026-10-05 09:00:00'),
  ('con_00b2','tnt_0016','org_00b1','Madina','Isaeva','Директор по продажам','madina.isaeva@orbitsystems.uz','+998 93 707 17 40','@madina51','website','active',1,1,1,'usr_000s','["champion"]','2026-09-16 09:00:00','2026-10-12 09:00:00'),
  ('con_00b3','tnt_0016','org_00b1','Shohruh','Alimov','Финансовый директор','shohruh.alimov@orbitsystems.uz','+998 75 767 63 20','@shohruh93','exhibition','active',0,1,1,'usr_000s','["decision_maker"]','2026-05-28 09:00:00','2026-10-12 09:00:00'),
  ('con_00b4','tnt_0016','org_00b1','Otabek','Ahmedova','Финансовый директор','otabek.ahmedova@orbitsystems.uz','+998 87 275 38 62',NULL,'instagram','active',0,1,1,'usr_000s','["champion"]','2026-07-23 09:00:00','2026-10-07 09:00:00'),
  ('con_00b5','tnt_0016','org_00b1','Nargiza','Nazarov','Операционный директор','nargiza.nazarov@orbitsystems.uz','+998 86 267 75 70','@nargiza15','telegram','active',0,0,0,'usr_000s','["influencer"]','2026-04-10 09:00:00','2026-10-07 09:00:00'),
  ('con_00b7','tnt_0016','org_00b6','Dilnoza','Alimov','CTO','dilnoza.alimov@technova.uz','+998 85 294 47 97','@dilnoza83','website','active',1,1,1,'usr_000t','["influencer"]','2026-04-14 09:00:00','2026-10-04 09:00:00'),
  ('con_00b8','tnt_0016','org_00b6','Zilola','Yusupova','Закупщик','zilola.yusupova@technova.uz','+998 87 140 40 69',NULL,'cold_call','active',0,1,1,'usr_000t','["champion"]','2026-08-14 09:00:00','2026-10-11 09:00:00'),
  ('con_00ba','tnt_0016','org_00b9','Lola','Isaeva','Руководитель проектов','lola.isaeva@initechllc.uz','+998 90 907 27 64',NULL,'campaign','active',1,1,1,'usr_000y','["decision_maker"]','2026-05-04 09:00:00','2026-10-09 09:00:00'),
  ('con_00bb','tnt_0016','org_00b9','Alisher','Saidova','Маркетолог','alisher.saidova@initechllc.uz','+998 97 673 88 50',NULL,'api','active',0,1,1,'usr_000y','["decision_maker"]','2026-09-13 09:00:00','2026-10-11 09:00:00'),
  ('con_00bc','tnt_0016','org_00b9','Malika','Rahimov','Закупщик','malika.rahimov@initechllc.uz','+998 89 948 86 14','@malika71','campaign','active',0,1,1,'usr_000y','["influencer"]','2026-06-16 09:00:00','2026-10-03 09:00:00'),
  ('con_00bd','tnt_0016','org_00b9','Bekzod','Toshmatov','IT-директор','bekzod.toshmatov@initechllc.uz','+998 85 641 98 48','@bekzod1','referral','active',0,0,0,'usr_000y','["influencer"]','2026-04-02 09:00:00','2026-10-12 09:00:00'),
  ('con_00bf','tnt_0016','org_00be','Zarina','Ergasheva','CTO','zarina.ergasheva@starkindustries.uz','+998 90 785 52 24','@zarina32','api','active',1,1,0,'usr_000w','["champion"]','2026-03-24 09:00:00','2026-10-04 09:00:00'),
  ('con_00bg','tnt_0016','org_00be','Alisher','Umarov','Операционный директор','alisher.umarov@starkindustries.uz','+998 70 805 71 32',NULL,'cold_call','active',0,1,1,'usr_000w','["decision_maker"]','2026-06-07 09:00:00','2026-10-05 09:00:00'),
  ('con_00bi','tnt_0016','org_00bh','Malika','Ergasheva','Руководитель проектов','malika.ergasheva@umbrellaco.uz','+998 74 513 66 18',NULL,'campaign','active',1,1,1,'usr_000x','["influencer"]','2026-03-14 09:00:00','2026-10-07 09:00:00'),
  ('con_00bj','tnt_0016','org_00bh','Lola','Bekmurodov','Финансовый директор','lola.bekmurodov@umbrellaco.uz','+998 73 735 19 92','@lola92','exhibition','active',0,1,1,'usr_000x','["champion"]','2026-07-14 09:00:00','2026-10-12 09:00:00'),
  ('con_00bk','tnt_0016','org_00bh','Zilola','Ergasheva','IT-директор','zilola.ergasheva@umbrellaco.uz','+998 82 921 25 86','@zilola10','instagram','active',0,1,1,'usr_000x','["decision_maker"]','2026-09-04 09:00:00','2026-10-10 09:00:00'),
  ('con_00bl','tnt_0016','org_00bh','Nargiza','Mirzaeva','CTO','nargiza.mirzaeva@umbrellaco.uz','+998 74 699 25 78',NULL,'api','active',0,1,1,'usr_000x','["champion"]','2026-01-19 09:00:00','2026-10-03 09:00:00'),
  ('con_00bn','tnt_0016','org_00bm','Lola','Isaeva','IT-директор','lola.isaeva@brightwavedigital.uz','+998 91 927 20 29','@lola7','referral','active',1,1,1,'usr_000v','["champion"]','2026-01-08 09:00:00','2026-10-08 09:00:00'),
  ('con_00bo','tnt_0016','org_00bm','Zarina','Mirzaeva','Финансовый директор','zarina.mirzaeva@brightwavedigital.uz','+998 78 490 61 33',NULL,'referral','active',0,1,1,'usr_000v','["champion"]','2026-01-07 09:00:00','2026-10-04 09:00:00'),
  ('con_00bq','tnt_0016','org_00bp','Rustam','Mirzaeva','CEO','rustam.mirzaeva@zenithsoft.uz','+998 78 577 69 27',NULL,'instagram','active',1,1,1,'usr_000t','["decision_maker"]','2026-03-02 09:00:00','2026-10-07 09:00:00'),
  ('con_00br','tnt_0016','org_00bp','Alisher','Ergasheva','CTO','alisher.ergasheva@zenithsoft.uz','+998 86 438 68 79',NULL,'campaign','active',0,0,0,'usr_000t','["decision_maker"]','2026-02-12 09:00:00','2026-10-11 09:00:00'),
  ('con_00bs','tnt_0016','org_00bp','Farrux','Toshmatov','Директор по продажам','farrux.toshmatov@zenithsoft.uz','+998 75 637 34 90','@farrux9','api','active',0,1,0,'usr_000t','["decision_maker"]','2026-07-22 09:00:00','2026-10-05 09:00:00'),
  ('con_00bt','tnt_0016','org_00bp','Sardor','Alimov','Финансовый директор','sardor.alimov@zenithsoft.uz','+998 97 388 84 70',NULL,'instagram','active',0,1,0,'usr_000t','["influencer"]','2026-06-13 09:00:00','2026-10-04 09:00:00'),
  ('con_00bv','tnt_0016','org_00bu','Farrux','Karimova','IT-директор','farrux.karimova@primecargo.uz','+998 92 866 93 71',NULL,'instagram','active',1,1,1,'usr_000v','["influencer"]','2026-01-21 09:00:00','2026-10-09 09:00:00'),
  ('con_00bw','tnt_0016','org_00bu','Nilufar','Alimov','IT-директор','nilufar.alimov@primecargo.uz','+998 95 662 48 99','@nilufar10','website','active',0,1,1,'usr_000v','["decision_maker"]','2026-10-04 09:00:00','2026-10-02 09:00:00'),
  ('con_00by','tnt_0016','org_00bx','Gulnora','Saidova','CEO','gulnora.saidova@silkroadlogistics.uz','+998 91 685 68 82',NULL,'instagram','active',1,1,1,'usr_000w','["influencer"]','2026-05-11 09:00:00','2026-10-09 09:00:00'),
  ('con_00bz','tnt_0016','org_00bx','Nargiza','Rahimov','Маркетолог','nargiza.rahimov@silkroadlogistics.uz','+998 98 602 10 73',NULL,'referral','active',0,1,1,'usr_000w','["influencer"]','2026-07-18 09:00:00','2026-10-07 09:00:00'),
  ('con_00c1','tnt_0016','org_00c0','Zilola','Karimov','CTO','zilola.karimov@vertexdigital.uz','+998 90 661 81 24',NULL,'campaign','active',1,1,1,'usr_000v','["decision_maker"]','2026-08-11 09:00:00','2026-10-11 09:00:00'),
  ('con_00c2','tnt_0016','org_00c0','Nilufar','Mirzaeva','Закупщик','nilufar.mirzaeva@vertexdigital.uz','+998 98 426 34 28',NULL,'instagram','active',0,0,0,'usr_000v','["decision_maker"]','2026-07-21 09:00:00','2026-10-10 09:00:00'),
  ('con_00c4','tnt_0016','org_00c3','Madina','Mirzaeva','IT-директор','madina.mirzaeva@novaretailgroup.uz','+998 99 878 35 61',NULL,'exhibition','active',1,1,0,'usr_000r','["decision_maker"]','2026-03-21 09:00:00','2026-10-11 09:00:00'),
  ('con_00c5','tnt_0016','org_00c3','Otabek','Pulatov','CTO','otabek.pulatov@novaretailgroup.uz','+998 78 966 41 55',NULL,'campaign','active',0,1,1,'usr_000r','["influencer"]','2026-03-02 09:00:00','2026-10-05 09:00:00'),
  ('con_00c7','tnt_0016','org_00c6','Nilufar','Umarov','IT-директор','nilufar.umarov@blueorbitmedia.uz','+998 99 598 61 19',NULL,'referral','active',1,1,0,'usr_000t','["champion"]','2026-08-21 09:00:00','2026-10-02 09:00:00'),
  ('con_00c8','tnt_0016','org_00c6','Nargiza','Pulatov','CEO','nargiza.pulatov@blueorbitmedia.uz','+998 92 753 51 82',NULL,'campaign','active',0,1,1,'usr_000t','["champion"]','2026-08-04 09:00:00','2026-10-04 09:00:00'),
  ('con_00c9','tnt_0016','org_00c6','Malika','Alimov','Маркетолог','malika.alimov@blueorbitmedia.uz','+998 98 879 64 57',NULL,'api','active',0,1,0,'usr_000t','["influencer"]','2026-09-23 09:00:00','2026-10-11 09:00:00'),
  ('con_00ca','tnt_0016','org_00c6','Nargiza','Isaeva','Руководитель проектов','nargiza.isaeva@blueorbitmedia.uz','+998 70 106 43 10','@nargiza40','campaign','active',0,0,0,'usr_000t','["decision_maker"]','2026-09-02 09:00:00','2026-10-08 09:00:00'),
  ('con_00cc','tnt_0016','org_00cb','Nilufar','Umarov','IT-директор','nilufar.umarov@uzbektelecom.uz','+998 71 307 64 48','@nilufar30','cold_call','active',1,1,1,'usr_000s','["champion"]','2026-09-12 09:00:00','2026-10-03 09:00:00'),
  ('con_00cd','tnt_0016','org_00cb','Nilufar','Alimov','Финансовый директор','nilufar.alimov@uzbektelecom.uz','+998 98 726 36 20',NULL,'campaign','active',0,1,1,'usr_000s','["decision_maker"]','2026-06-23 09:00:00','2026-10-08 09:00:00'),
  ('con_00cf','tnt_0016','org_00ce','Lola','Nazarov','Закупщик','lola.nazarov@ferganatextile.uz','+998 96 703 38 78',NULL,'campaign','active',1,1,1,'usr_000r','["influencer"]','2026-07-15 09:00:00','2026-10-10 09:00:00'),
  ('con_00cg','tnt_0016','org_00ce','Rustam','Abdullaeva','Финансовый директор','rustam.abdullaeva@ferganatextile.uz','+998 98 956 14 34',NULL,'instagram','active',0,0,0,'usr_000r','["decision_maker"]','2026-07-15 09:00:00','2026-10-06 09:00:00'),
  ('con_00ch','tnt_0016','org_00ce','Shohruh','Karimov','CEO','shohruh.karimov@ferganatextile.uz','+998 83 707 27 43',NULL,'website','active',0,0,0,'usr_000r','["influencer"]','2026-06-12 09:00:00','2026-10-05 09:00:00'),
  ('con_00cj','tnt_0016','org_00ci','Shohruh','Abdullaeva','Маркетолог','shohruh.abdullaeva@samarkandfoods.uz','+998 86 888 49 88',NULL,'telegram','active',1,1,1,'usr_000s','["decision_maker"]','2026-09-03 09:00:00','2026-10-09 09:00:00'),
  ('con_00ck','tnt_0016','org_00ci','Nargiza','Saidova','Директор по продажам','nargiza.saidova@samarkandfoods.uz','+998 88 173 46 91','@nargiza27','campaign','active',0,1,1,'usr_000s','["decision_maker"]','2026-04-25 09:00:00','2026-10-12 09:00:00'),
  ('con_00cl','tnt_0016','org_00ci','Kamila','Abdullaeva','IT-директор','kamila.abdullaeva@samarkandfoods.uz','+998 81 832 10 38','@kamila80','campaign','active',0,1,1,'usr_000s','["decision_maker"]','2026-05-20 09:00:00','2026-10-08 09:00:00'),
  ('con_00cm','tnt_0016','org_00ci','Iskander','Bekmurodov','Закупщик','iskander.bekmurodov@samarkandfoods.uz','+998 92 890 32 43',NULL,'referral','active',0,1,1,'usr_000s','["decision_maker"]','2026-07-16 09:00:00','2026-10-04 09:00:00'),
  ('con_00co','tnt_0016','org_00cn','Zilola','Saidova','Маркетолог','zilola.saidova@tashkentmotors.uz','+998 70 987 47 91','@zilola53','website','active',1,1,1,'usr_000y','["influencer"]','2026-08-06 09:00:00','2026-10-02 09:00:00'),
  ('con_00cp','tnt_0016','org_00cn','Aziz','Nazarov','Директор по продажам','aziz.nazarov@tashkentmotors.uz','+998 73 335 92 56','@aziz53','api','active',0,1,0,'usr_000y','["decision_maker"]','2026-02-03 09:00:00','2026-10-07 09:00:00');
INSERT INTO contacts VALUES
  ('con_00cr','tnt_0016','org_00cq','Lola','Saidova','Операционный директор','lola.saidova@aralshipping.uz','+998 99 959 64 84','@lola18','referral','active',1,0,0,'usr_000s','["influencer"]','2026-07-26 09:00:00','2026-10-07 09:00:00'),
  ('con_00cs','tnt_0016','org_00cq','Nargiza','Abdullaeva','Закупщик','nargiza.abdullaeva@aralshipping.uz','+998 92 708 82 41',NULL,'referral','active',0,0,0,'usr_000s','["decision_maker"]','2026-03-26 09:00:00','2026-10-06 09:00:00'),
  ('con_00ct','tnt_0016','org_00cq','Aziz','Ergasheva','Финансовый директор','aziz.ergasheva@aralshipping.uz','+998 98 826 30 32','@aziz79','exhibition','active',0,1,1,'usr_000s','["decision_maker"]','2026-09-10 09:00:00','2026-10-02 09:00:00'),
  ('con_00cv','tnt_0016','org_00cu','Lola','Bekmurodov','Директор по продажам','lola.bekmurodov@registanbank.uz','+998 83 188 73 67',NULL,'referral','active',1,1,1,'usr_000v','["influencer"]','2026-07-26 09:00:00','2026-10-09 09:00:00'),
  ('con_00cw','tnt_0016','org_00cu','Malika','Toshmatov','Операционный директор','malika.toshmatov@registanbank.uz','+998 82 531 81 77','@malika33','campaign','active',0,1,0,'usr_000v','["influencer"]','2026-02-15 09:00:00','2026-10-03 09:00:00'),
  ('con_00cx','tnt_0016','org_00cu','Farrux','Isaeva','IT-директор','farrux.isaeva@registanbank.uz','+998 87 366 79 97',NULL,'website','active',0,1,1,'usr_000v','["decision_maker"]','2026-05-14 09:00:00','2026-10-07 09:00:00'),
  ('con_00cz','tnt_0016','org_00cy','Dilnoza','Bekmurodov','Руководитель проектов','dilnoza.bekmurodov@chorsutrade.uz','+998 71 414 32 15',NULL,'telegram','active',1,1,1,'usr_000y','["influencer"]','2026-06-06 09:00:00','2026-10-07 09:00:00'),
  ('con_00d0','tnt_0016','org_00cy','Nargiza','Nazarov','Финансовый директор','nargiza.nazarov@chorsutrade.uz','+998 84 383 54 93',NULL,'api','active',0,1,1,'usr_000y','["influencer"]','2026-04-18 09:00:00','2026-10-04 09:00:00'),
  ('con_00d1','tnt_0016','org_00cy','Zarina','Bekmurodov','Директор по продажам','zarina.bekmurodov@chorsutrade.uz','+998 79 233 28 72',NULL,'cold_call','active',0,1,1,'usr_000y','["champion"]','2026-07-09 09:00:00','2026-10-11 09:00:00'),
  ('con_00d2','tnt_0016','org_00cy','Otabek','Mirzaeva','IT-директор','otabek.mirzaeva@chorsutrade.uz','+998 80 117 57 58','@otabek81','telegram','active',0,1,1,'usr_000y','["influencer"]','2026-02-17 09:00:00','2026-10-03 09:00:00'),
  ('con_00d4','tnt_0016','org_00d3','Iskander','Nazarov','Директор по продажам','iskander.nazarov@navoiminingtech.uz','+998 92 182 83 35',NULL,'instagram','active',1,1,1,'usr_000x','["influencer"]','2026-06-08 09:00:00','2026-10-12 09:00:00'),
  ('con_00d5','tnt_0016','org_00d3','Shohruh','Ergasheva','Операционный директор','shohruh.ergasheva@navoiminingtech.uz','+998 90 653 83 96','@shohruh14','cold_call','active',0,1,1,'usr_000x','["influencer"]','2026-06-10 09:00:00','2026-10-02 09:00:00'),
  ('con_00d6','tnt_0016','org_00d3','Kamila','Ahmedova','CTO','kamila.ahmedova@navoiminingtech.uz','+998 77 794 93 12','@kamila98','exhibition','active',0,0,0,'usr_000x','["influencer"]','2026-05-17 09:00:00','2026-10-04 09:00:00'),
  ('con_00d8','tnt_0016','org_00d7','Alisher','Bekmurodov','CEO','alisher.bekmurodov@bukharaceramics.uz','+998 82 671 23 52','@alisher91','exhibition','active',1,1,0,'usr_000x','["influencer"]','2026-06-26 09:00:00','2026-10-08 09:00:00'),
  ('con_00d9','tnt_0016','org_00d7','Sardor','Abdullaeva','Маркетолог','sardor.abdullaeva@bukharaceramics.uz','+998 88 982 31 11',NULL,'cold_call','active',0,1,1,'usr_000x','["champion"]','2026-04-26 09:00:00','2026-10-03 09:00:00'),
  ('con_00da','tnt_0016','org_00d7','Bekzod','Ahmedova','CTO','bekzod.ahmedova@bukharaceramics.uz','+998 94 827 68 99',NULL,'referral','active',0,1,0,'usr_000x','["champion"]','2026-04-26 09:00:00','2026-10-04 09:00:00'),
  ('con_00db','tnt_0016','org_00d7','Aziz','Isaeva','Маркетолог','aziz.isaeva@bukharaceramics.uz','+998 71 380 95 62','@aziz65','referral','active',0,0,0,'usr_000x','["influencer"]','2026-03-31 09:00:00','2026-10-08 09:00:00'),
  ('con_00dd','tnt_0016','org_00dc','Sevara','Alimov','CEO','sevara.alimov@khivatravel.uz','+998 75 543 38 61',NULL,'cold_call','active',1,1,1,'usr_000u','["influencer"]','2026-03-18 09:00:00','2026-10-12 09:00:00'),
  ('con_00de','tnt_0016','org_00dc','Dilnoza','Isaeva','Директор по продажам','dilnoza.isaeva@khivatravel.uz','+998 87 868 92 47','@dilnoza37','cold_call','active',0,1,1,'usr_000u','["champion"]','2026-08-12 09:00:00','2026-10-07 09:00:00'),
  ('con_00df','tnt_0016','org_00dc','Kamila','Isaeva','Руководитель проектов','kamila.isaeva@khivatravel.uz','+998 92 386 74 42',NULL,'telegram','active',0,1,1,'usr_000u','["decision_maker"]','2026-03-09 09:00:00','2026-10-07 09:00:00'),
  ('con_00dg','tnt_0016','org_00dc','Gulnora','Bekmurodov','Финансовый директор','gulnora.bekmurodov@khivatravel.uz','+998 72 246 84 46',NULL,'exhibition','active',0,1,0,'usr_000u','["champion"]','2026-05-24 09:00:00','2026-10-08 09:00:00'),
  ('con_00di','tnt_0016','org_00dh','Gulnora','Ergasheva','IT-директор','gulnora.ergasheva@astraindustries.uz','+998 77 340 84 81','@gulnora85','exhibition','active',1,1,0,'usr_000r','["influencer"]','2026-04-16 09:00:00','2026-10-06 09:00:00'),
  ('con_00dj','tnt_0016','org_00dh','Nilufar','Pulatov','Закупщик','nilufar.pulatov@astraindustries.uz','+998 86 599 96 99',NULL,'campaign','active',0,1,1,'usr_000r','["champion"]','2026-01-25 09:00:00','2026-10-10 09:00:00'),
  ('con_00dl','tnt_0016','org_00dk','Zarina','Nazarov','Маркетолог','zarina.nazarov@vegallc.uz','+998 82 349 98 20',NULL,'exhibition','active',1,0,0,'usr_000y','["decision_maker"]','2026-02-21 09:00:00','2026-10-08 09:00:00'),
  ('con_00dm','tnt_0016','org_00dk','Zilola','Rahimov','Маркетолог','zilola.rahimov@vegallc.uz','+998 90 504 31 55',NULL,'cold_call','active',0,1,1,'usr_000y','["decision_maker"]','2026-03-17 09:00:00','2026-10-09 09:00:00'),
  ('con_00dn','tnt_0016','org_00dk','Aziz','Pulatov','Финансовый директор','aziz.pulatov@vegallc.uz','+998 74 340 99 25',NULL,'instagram','active',0,1,1,'usr_000y','["champion"]','2026-09-29 09:00:00','2026-10-07 09:00:00'),
  ('con_00do','tnt_0016','org_00dk','Shohruh','Bekmurodov','Маркетолог','shohruh.bekmurodov@vegallc.uz','+998 73 170 48 29','@shohruh53','instagram','active',0,1,1,'usr_000y','["champion"]','2026-08-05 09:00:00','2026-10-10 09:00:00'),
  ('con_00dq','tnt_0016','org_00dp','Timur','Toshmatov','Финансовый директор','timur.toshmatov@orionllc.uz','+998 76 151 22 59',NULL,'cold_call','active',1,1,1,'usr_000r','["champion"]','2026-05-29 09:00:00','2026-10-06 09:00:00'),
  ('con_00dr','tnt_0016','org_00dp','Alisher','Bekmurodov','Директор по продажам','alisher.bekmurodov@orionllc.uz','+998 96 484 17 50',NULL,'exhibition','active',0,1,1,'usr_000r','["champion"]','2026-07-04 09:00:00','2026-10-10 09:00:00'),
  ('con_00ds','tnt_0016','org_00dp','Sevara','Alimov','Директор по продажам','sevara.alimov@orionllc.uz','+998 96 424 84 79','@sevara95','campaign','active',0,1,1,'usr_000r','["decision_maker"]','2026-05-29 09:00:00','2026-10-08 09:00:00'),
  ('con_00dt','tnt_0016','org_00dp','Gulnora','Bekmurodov','Маркетолог','gulnora.bekmurodov@orionllc.uz','+998 98 617 25 47',NULL,'cold_call','active',0,1,0,'usr_000r','["champion"]','2026-08-06 09:00:00','2026-10-08 09:00:00'),
  ('con_00dv','tnt_0016','org_00du','Kamila','Toshmatov','Руководитель проектов','kamila.toshmatov@siriusllc.uz','+998 80 648 95 92',NULL,'campaign','active',1,0,0,'usr_000u','["decision_maker"]','2026-05-08 09:00:00','2026-10-05 09:00:00'),
  ('con_00dw','tnt_0016','org_00du','Gulnora','Alimov','Руководитель проектов','gulnora.alimov@siriusllc.uz','+998 87 831 31 41','@gulnora19','api','active',0,1,1,'usr_000u','["decision_maker"]','2026-03-04 09:00:00','2026-10-06 09:00:00'),
  ('con_00dy','tnt_0016','org_00dx','Alisher','Alimov','Руководитель проектов','alisher.alimov@deltatrade.uz','+998 70 618 68 14','@alisher63','campaign','active',1,1,0,'usr_000y','["decision_maker"]','2026-04-15 09:00:00','2026-10-02 09:00:00'),
  ('con_00dz','tnt_0016','org_00dx','Nilufar','Toshmatov','IT-директор','nilufar.toshmatov@deltatrade.uz','+998 85 382 10 79','@nilufar89','cold_call','active',0,1,1,'usr_000y','["decision_maker"]','2026-01-30 09:00:00','2026-10-03 09:00:00'),
  ('con_00e0','tnt_0016','org_00dx','Shohruh','Isaeva','CEO','shohruh.isaeva@deltatrade.uz','+998 97 694 71 69','@shohruh87','instagram','active',0,1,1,'usr_000y','["champion"]','2026-06-19 09:00:00','2026-10-02 09:00:00'),
  ('con_00e1','tnt_0016','org_00dx','Dilnoza','Alimov','Закупщик','dilnoza.alimov@deltatrade.uz','+998 92 749 67 14','@dilnoza34','instagram','active',0,1,1,'usr_000y','["champion"]','2026-02-02 09:00:00','2026-10-08 09:00:00'),
  ('con_00e3','tnt_0016','org_00e2','Nilufar','Umarov','Маркетолог','nilufar.umarov@siriussystems.uz','+998 96 991 65 90',NULL,'referral','active',1,1,1,'usr_000y','["influencer"]','2026-01-12 09:00:00','2026-10-04 09:00:00'),
  ('con_00e4','tnt_0016','org_00e2','Bekzod','Mirzaeva','Директор по продажам','bekzod.mirzaeva@siriussystems.uz','+998 79 830 34 38',NULL,'api','active',0,1,1,'usr_000y','["decision_maker"]','2026-04-22 09:00:00','2026-10-09 09:00:00'),
  ('con_00e5','tnt_0016','org_00e2','Zilola','Toshmatov','CTO','zilola.toshmatov@siriussystems.uz','+998 75 413 36 54','@zilola24','telegram','active',0,1,1,'usr_000y','["champion"]','2026-01-27 09:00:00','2026-10-03 09:00:00'),
  ('con_00e7','tnt_0016','org_00e6','Nargiza','Isaeva','Закупщик','nargiza.isaeva@orionindustries.uz','+998 73 399 99 33',NULL,'website','active',1,0,0,'usr_000s','["champion"]','2026-07-14 09:00:00','2026-10-06 09:00:00'),
  ('con_00e8','tnt_0016','org_00e6','Lola','Saidova','Руководитель проектов','lola.saidova@orionindustries.uz','+998 75 507 25 52',NULL,'campaign','active',0,1,1,'usr_000s','["champion"]','2026-08-11 09:00:00','2026-10-06 09:00:00'),
  ('con_00ea','tnt_0016','org_00e9','Shohruh','Karimov','CEO','shohruh.karimov@novallc.uz','+998 92 564 64 82',NULL,'campaign','active',1,0,0,'usr_000u','["decision_maker"]','2026-09-18 09:00:00','2026-10-11 09:00:00'),
  ('con_00eb','tnt_0016','org_00e9','Sardor','Alimov','Операционный директор','sardor.alimov@novallc.uz','+998 73 239 47 48',NULL,'cold_call','active',0,1,1,'usr_000u','["influencer"]','2026-06-28 09:00:00','2026-10-05 09:00:00'),
  ('con_00ec','tnt_0016','org_00e9','Bekzod','Nazarov','CEO','bekzod.nazarov@novallc.uz','+998 85 830 37 61','@bekzod15','instagram','active',0,1,1,'usr_000u','["influencer"]','2026-07-18 09:00:00','2026-10-10 09:00:00'),
  ('con_00ee','tnt_0016','org_00ed','Rustam','Toshmatov','Руководитель проектов','rustam.toshmatov@oriontrade.uz','+998 79 992 67 87',NULL,'exhibition','active',1,1,0,'usr_000x','["decision_maker"]','2026-03-30 09:00:00','2026-10-04 09:00:00'),
  ('con_00ef','tnt_0016','org_00ed','Kamila','Bekmurodov','Руководитель проектов','kamila.bekmurodov@oriontrade.uz','+998 78 551 12 12',NULL,'api','active',0,1,0,'usr_000x','["influencer"]','2026-05-31 09:00:00','2026-10-12 09:00:00'),
  ('con_00eg','tnt_0016','org_00ed','Nilufar','Isaeva','CTO','nilufar.isaeva@oriontrade.uz','+998 95 103 80 80',NULL,'telegram','active',0,0,0,'usr_000x','["decision_maker"]','2026-01-11 09:00:00','2026-10-12 09:00:00'),
  ('con_00eh','tnt_0016','org_00ed','Rustam','Isaeva','CTO','rustam.isaeva@oriontrade.uz','+998 74 403 96 60',NULL,'instagram','active',0,1,1,'usr_000x','["decision_maker"]','2026-10-07 09:00:00','2026-10-11 09:00:00'),
  ('con_00ej','tnt_0016','org_00ei','Otabek','Yusupova','Операционный директор','otabek.yusupova@oriontrade.uz','+998 73 919 94 86','@otabek94','api','active',1,0,0,'usr_000x','["champion"]','2026-08-27 09:00:00','2026-10-06 09:00:00');
INSERT INTO contacts VALUES
  ('con_00ek','tnt_0016','org_00ei','Farrux','Umarov','CEO','farrux.umarov@oriontrade.uz','+998 92 582 94 36','@farrux45','campaign','active',0,0,0,'usr_000x','["decision_maker"]','2026-05-16 09:00:00','2026-10-12 09:00:00'),
  ('con_00el','tnt_0016','org_00ei','Nilufar','Nazarov','Закупщик','nilufar.nazarov@oriontrade.uz','+998 92 760 55 53',NULL,'exhibition','active',0,1,1,'usr_000x','["champion"]','2026-09-18 09:00:00','2026-10-06 09:00:00'),
  ('con_00em','tnt_0016','org_00ei','Malika','Karimov','Маркетолог','malika.karimov@oriontrade.uz','+998 81 775 18 48','@malika39','campaign','active',0,1,1,'usr_000x','["champion"]','2026-03-03 09:00:00','2026-10-06 09:00:00'),
  ('con_00eo','tnt_0017','org_00en','Shohruh','Bekmurodov','Руководитель проектов','shohruh.bekmurodov@acmecorp.uz','+998 90 671 48 51',NULL,'cold_call','active',1,1,1,NULL,'["champion"]','2026-05-06 09:00:00','2026-10-05 09:00:00'),
  ('con_00ep','tnt_0017','org_00en','Otabek','Toshmatov','Закупщик','otabek.toshmatov@acmecorp.uz','+998 99 867 99 79',NULL,'website','active',0,1,1,NULL,'["decision_maker"]','2026-02-05 09:00:00','2026-10-08 09:00:00'),
  ('con_00er','tnt_0017','org_00eq','Madina','Rahimov','Закупщик','madina.rahimov@globexindustries.uz','+998 79 196 74 65','@madina51','campaign','active',1,1,1,NULL,'["decision_maker"]','2026-01-21 09:00:00','2026-10-04 09:00:00'),
  ('con_00es','tnt_0017','org_00eq','Rustam','Toshmatov','CEO','rustam.toshmatov@globexindustries.uz','+998 82 621 46 21',NULL,'telegram','active',0,0,0,NULL,'["champion"]','2026-09-22 09:00:00','2026-10-09 09:00:00'),
  ('con_00eu','tnt_0017','org_00et','Malika','Karimov','Финансовый директор','malika.karimov@orbitsystems.uz','+998 75 572 78 43','@malika79','referral','active',1,1,1,NULL,'["influencer"]','2026-08-08 09:00:00','2026-10-10 09:00:00'),
  ('con_00ew','tnt_0017','org_00ev','Sevara','Bekmurodov','CEO','sevara.bekmurodov@technova.uz','+998 90 156 52 97',NULL,'telegram','active',1,1,1,NULL,'["champion"]','2026-07-14 09:00:00','2026-10-08 09:00:00'),
  ('con_00ex','tnt_0017','org_00ev','Dilnoza','Karimov','Директор по продажам','dilnoza.karimov@technova.uz','+998 97 592 61 66',NULL,'referral','active',0,0,0,NULL,'["influencer"]','2026-08-29 09:00:00','2026-10-04 09:00:00'),
  ('con_00ez','tnt_0017','org_00ey','Bekzod','Bekmurodov','Руководитель проектов','bekzod.bekmurodov@initechllc.uz','+998 95 930 17 59',NULL,'exhibition','active',1,1,1,NULL,'["decision_maker"]','2026-01-07 09:00:00','2026-10-11 09:00:00'),
  ('con_00f1','tnt_0017','org_00f0','Farrux','Isaeva','Директор по продажам','farrux.isaeva@starkindustries.uz','+998 79 117 76 51',NULL,'website','active',1,1,1,NULL,'["champion"]','2026-01-12 09:00:00','2026-10-11 09:00:00'),
  ('con_00f3','tnt_0017','org_00f2','Lola','Yusupova','Операционный директор','lola.yusupova@umbrellaco.uz','+998 98 195 12 60',NULL,'campaign','active',1,1,1,NULL,'["decision_maker"]','2026-03-11 09:00:00','2026-10-07 09:00:00'),
  ('con_00f4','tnt_0017','org_00f2','Timur','Toshmatov','Маркетолог','timur.toshmatov@umbrellaco.uz','+998 85 375 13 37',NULL,'exhibition','active',0,0,0,NULL,'["champion"]','2026-01-28 09:00:00','2026-10-11 09:00:00'),
  ('con_00f6','tnt_0017','org_00f5','Dilnoza','Pulatov','Операционный директор','dilnoza.pulatov@brightwavedigital.uz','+998 73 325 35 31',NULL,'referral','active',1,1,1,NULL,'["champion"]','2026-05-22 09:00:00','2026-10-05 09:00:00'),
  ('con_00f8','tnt_0017','org_00f7','Shohruh','Isaeva','Руководитель проектов','shohruh.isaeva@zenithsoft.uz','+998 98 297 16 11',NULL,'referral','active',1,0,0,NULL,'["decision_maker"]','2026-10-04 09:00:00','2026-10-10 09:00:00'),
  ('con_00fa','tnt_0017','org_00f9','Zarina','Bekmurodov','Директор по продажам','zarina.bekmurodov@primecargo.uz','+998 87 126 99 91',NULL,'website','active',1,1,1,NULL,'["decision_maker"]','2026-10-06 09:00:00','2026-10-04 09:00:00'),
  ('con_00fb','tnt_0017','org_00f9','Malika','Alimov','Закупщик','malika.alimov@primecargo.uz','+998 85 473 43 28',NULL,'instagram','active',0,1,1,NULL,'["champion"]','2026-03-30 09:00:00','2026-10-10 09:00:00'),
  ('con_00fd','tnt_0017','org_00fc','Iskander','Bekmurodov','Директор по продажам','iskander.bekmurodov@silkroadlogistics.uz','+998 90 809 25 30',NULL,'telegram','active',1,1,1,NULL,'["influencer"]','2026-09-21 09:00:00','2026-10-11 09:00:00'),
  ('con_00fe','tnt_0017','org_00fc','Kamila','Isaeva','IT-директор','kamila.isaeva@silkroadlogistics.uz','+998 87 945 82 72',NULL,'website','active',0,1,1,NULL,'["influencer"]','2026-01-09 09:00:00','2026-10-10 09:00:00'),
  ('con_00fg','tnt_0017','org_00ff','Madina','Mirzaeva','Закупщик','madina.mirzaeva@vertexdigital.uz','+998 76 305 35 84',NULL,'website','active',1,1,1,NULL,'["champion"]','2026-01-27 09:00:00','2026-10-12 09:00:00');

-- deals: 104 rows
INSERT INTO deals VALUES
  ('deal_00fj','tnt_0016','Внедрение CRM — Samarkand Foods','org_00ci','con_00eg','pip_00ac','stg_00ad',187127000,'UZS',10,'open','referral','usr_000y','2026-11-04 09:00:00',NULL,NULL,NULL,0,'2026-08-13 09:00:00','2026-10-06 09:00:00'),
  ('deal_00fm','tnt_0016','Поддержка и развитие — Компания','org_00dx','con_00bk','pip_00ac','stg_00ae',138238000,'UZS',25,'open','referral','usr_000u','2026-09-29 09:00:00',NULL,NULL,NULL,1,'2026-08-20 09:00:00','2026-10-07 09:00:00'),
  ('deal_00fp','tnt_0016','Android приложение — Chorsu Trade','org_00cy','con_00cz','pip_00ac','stg_00ag',193453000,'UZS',60,'open','telegram','usr_000w','2026-10-14 09:00:00',NULL,NULL,NULL,0,'2026-07-18 09:00:00','2026-10-09 09:00:00'),
  ('deal_00fr','tnt_0016','Поддержка и развитие — Chorsu Trade','org_00cy','con_00bv','pip_00ac','stg_00ad',175964000,'UZS',10,'open','cold_call','usr_000w','2026-10-09 09:00:00',NULL,NULL,NULL,0,'2026-10-02 09:00:00','2026-10-10 09:00:00'),
  ('deal_00fu','tnt_0016','Windows приложение — Компания','org_00ei','con_00eh','pip_00ac','stg_00ae',88300000,'UZS',25,'open','api','usr_000s','2026-09-13 09:00:00',NULL,NULL,NULL,0,'2026-07-16 09:00:00','2026-10-06 09:00:00'),
  ('deal_00fz','tnt_0016','Внедрение CRM — Navoi Mining Tech','org_00d3','con_00bb','pip_00ac','stg_00aj',43811000,'UZS',95,'won','campaign','usr_000w','2026-11-01 09:00:00','2026-10-25 09:00:00',NULL,NULL,0,'2026-10-07 09:00:00','2026-10-08 09:00:00'),
  ('deal_00g1','tnt_0016','Внедрение CRM — Chorsu Trade','org_00cy','con_00az','pip_00ac','stg_00ai',185139000,'UZS',90,'open','cold_call','usr_000y','2026-11-02 09:00:00',NULL,NULL,NULL,1,'2026-10-07 09:00:00','2026-10-10 09:00:00'),
  ('deal_00g4','tnt_0016','Поддержка и развитие — TechNova','org_00b6','con_00cr','pip_00ac','stg_00ai',32149000,'UZS',90,'open','cold_call','usr_000w','2026-09-25 09:00:00',NULL,NULL,NULL,1,'2026-05-04 09:00:00','2026-10-10 09:00:00'),
  ('deal_00g6','tnt_0016','Windows приложение — Компания','org_00e2','con_00co','pip_00ac','stg_00af',85692000,'UZS',40,'open','instagram','usr_000x','2026-11-11 09:00:00',NULL,NULL,NULL,0,'2026-05-11 09:00:00','2026-10-06 09:00:00'),
  ('deal_00g9','tnt_0016','Разработка сайта — Компания','org_00ed','con_00de','pip_00ac','stg_00ak',70247000,'UZS',100,'won','campaign','usr_000x','2026-10-31 09:00:00','2026-06-23 09:00:00',NULL,NULL,0,'2026-06-09 09:00:00','2026-10-09 09:00:00'),
  ('deal_00gc','tnt_0016','Windows приложение — Компания','org_00e6','con_00cl','pip_00ac','stg_00ai',95393000,'UZS',90,'open','exhibition','usr_000v','2026-10-20 09:00:00',NULL,NULL,NULL,0,'2026-05-11 09:00:00','2026-10-08 09:00:00'),
  ('deal_00gg','tnt_0016','AI голосовой агент — Компания','org_00e6','con_00db','pip_00ac','stg_00ag',173973000,'UZS',60,'open','instagram','usr_0013','2026-09-26 09:00:00',NULL,NULL,NULL,1,'2026-07-05 09:00:00','2026-10-07 09:00:00'),
  ('deal_00gi','tnt_0016','Windows приложение — Компания','org_00e9','con_00bs','pip_00ac','stg_00ad',124018000,'UZS',10,'open','cold_call','usr_000s','2026-10-31 09:00:00',NULL,NULL,NULL,0,'2026-05-16 09:00:00','2026-10-06 09:00:00'),
  ('deal_00gn','tnt_0016','Интеграция API — Компания','org_00e9',NULL,'pip_00ac','stg_00ag',206452000,'UZS',60,'open','website','usr_000t','2026-10-29 09:00:00',NULL,NULL,NULL,1,'2026-07-04 09:00:00','2026-10-12 09:00:00'),
  ('deal_00gp','tnt_0016','Интеграция API — Компания','org_00e2','con_00by','pip_00ac','stg_00ag',117048000,'UZS',60,'open','api','usr_000v','2026-10-22 09:00:00',NULL,NULL,NULL,0,'2026-06-01 09:00:00','2026-10-12 09:00:00'),
  ('deal_00gt','tnt_0016','Поддержка и развитие — BrightWave Digital','org_00bm','con_00cp','pip_00ac','stg_00aj',32762000,'UZS',95,'won','website','usr_000x','2026-09-08 09:00:00','2026-11-01 09:00:00',NULL,NULL,0,'2026-09-25 09:00:00','2026-10-11 09:00:00'),
  ('deal_00gx','tnt_0016','Аудит дизайн-системы — Компания','org_00dx','con_00c4','pip_00ac','stg_00ad',5581000,'UZS',10,'open','cold_call','usr_000x','2026-11-02 09:00:00',NULL,NULL,NULL,0,'2026-08-21 09:00:00','2026-10-12 09:00:00'),
  ('deal_00h1','tnt_0016','Интеграция API — Компания','org_00e6','con_00d0','pip_00ac','stg_00ak',169271000,'UZS',100,'won','exhibition','usr_000r','2026-10-24 09:00:00','2026-10-15 09:00:00',NULL,NULL,0,'2026-09-09 09:00:00','2026-10-09 09:00:00'),
  ('deal_00h5','tnt_0016','Интеграция API — Компания','org_00dx',NULL,'pip_00ac','stg_00ak',138564000,'UZS',100,'won','exhibition','usr_000v','2026-11-25 09:00:00','2026-08-08 09:00:00',NULL,NULL,0,'2026-07-05 09:00:00','2026-10-11 09:00:00'),
  ('deal_00h7','tnt_0016','Android приложение — BlueOrbit Media','org_00c6','con_00ee','pip_00ac','stg_00ak',142751000,'UZS',100,'won','exhibition','usr_000r','2026-11-12 09:00:00','2026-10-03 09:00:00',NULL,NULL,0,'2026-09-26 09:00:00','2026-10-06 09:00:00'),
  ('deal_00h9','tnt_0016','Аудит дизайн-системы — Компания','org_00e9','con_00ea','pip_00ac','stg_00ah',178477000,'UZS',75,'open','campaign','usr_000s','2026-11-21 09:00:00',NULL,NULL,NULL,0,'2026-05-14 09:00:00','2026-10-09 09:00:00'),
  ('deal_00hc','tnt_0016','AI голосовой агент — Registan Bank','org_00cu','con_00by','pip_00ac','stg_00ah',90300000,'UZS',75,'open','campaign','usr_000x','2026-11-10 09:00:00',NULL,NULL,NULL,0,'2026-06-28 09:00:00','2026-10-11 09:00:00'),
  ('deal_00hf','tnt_0016','Windows приложение — Компания','org_00e6',NULL,'pip_00ac','stg_00ag',137101000,'UZS',60,'open','instagram','usr_000v','2026-10-30 09:00:00',NULL,NULL,NULL,0,'2026-05-14 09:00:00','2026-10-10 09:00:00'),
  ('deal_00hi','tnt_0016','Интеграция API — Globex Industries','org_00ax',NULL,'pip_00ac','stg_00ae',75612000,'UZS',25,'open','website','usr_000t','2026-09-03 09:00:00',NULL,NULL,NULL,1,'2026-07-29 09:00:00','2026-10-12 09:00:00'),
  ('deal_00hm','tnt_0016','AI голосовой агент — BlueOrbit Media','org_00c6','con_00ca','pip_00ac','stg_00af',26115000,'UZS',40,'open','exhibition','usr_000u','2026-11-18 09:00:00',NULL,NULL,NULL,1,'2026-06-02 09:00:00','2026-10-10 09:00:00'),
  ('deal_00hp','tnt_0016','Интеграция API — Acme Corp','org_00au','con_00bk','pip_00ac','stg_00af',28434000,'UZS',40,'open','referral','usr_000s','2026-09-27 09:00:00',NULL,NULL,NULL,0,'2026-10-02 09:00:00','2026-10-06 09:00:00'),
  ('deal_00hs','tnt_0016','Интеграция API — Компания','org_00dx',NULL,'pip_00ac','stg_00ai',12360000,'UZS',90,'open','telegram','usr_000u','2026-10-09 09:00:00',NULL,NULL,NULL,1,'2026-08-16 09:00:00','2026-10-10 09:00:00'),
  ('deal_00hv','tnt_0016','Интеграция API — Zenith Soft','org_00bp','con_00d2','pip_00ac','stg_00ae',83287000,'UZS',25,'open','referral','usr_000t','2026-10-05 09:00:00',NULL,NULL,NULL,1,'2026-06-14 09:00:00','2026-10-08 09:00:00'),
  ('deal_00hy','tnt_0016','Внедрение CRM — Chorsu Trade','org_00cy','con_00c5','pip_00ac','stg_00ai',170051000,'UZS',90,'open','instagram','usr_000r','2026-11-14 09:00:00',NULL,NULL,NULL,0,'2026-07-11 09:00:00','2026-10-11 09:00:00'),
  ('deal_00i1','tnt_0016','Интеграция API — Компания','org_00du',NULL,'pip_00ac','stg_00af',58831000,'UZS',40,'open','referral','usr_000t','2026-10-18 09:00:00',NULL,NULL,NULL,1,'2026-06-23 09:00:00','2026-10-09 09:00:00'),
  ('deal_00i5','tnt_0016','Интеграция API — Компания','org_00e2',NULL,'pip_00ac','stg_00ae',163990000,'UZS',25,'open','api','usr_000y','2026-09-04 09:00:00',NULL,NULL,NULL,0,'2026-07-09 09:00:00','2026-10-10 09:00:00'),
  ('deal_00ia','tnt_0016','Поддержка и развитие — Компания','org_00dh',NULL,'pip_00ac','stg_00ah',111498000,'UZS',75,'open','website','usr_000t','2026-09-15 09:00:00',NULL,NULL,NULL,0,'2026-09-23 09:00:00','2026-10-07 09:00:00'),
  ('deal_00ic','tnt_0016','Поддержка и развитие — Zenith Soft','org_00bp','con_00d5','pip_00ac','stg_00aj',146255000,'UZS',95,'won','referral','usr_000v','2026-11-03 09:00:00','2026-07-02 09:00:00',NULL,NULL,0,'2026-05-24 09:00:00','2026-10-09 09:00:00'),
  ('deal_00ig','tnt_0016','Разработка сайта — Silk Road Logistics','org_00bx','con_00do','pip_00ac','stg_00af',160563000,'UZS',40,'open','campaign','usr_000u','2026-09-12 09:00:00',NULL,NULL,NULL,1,'2026-09-05 09:00:00','2026-10-09 09:00:00'),
  ('deal_00ij','tnt_0016','Интеграция API — Samarkand Foods','org_00ci','con_00cz','pip_00ac','stg_00ad',138712000,'UZS',10,'open','website','usr_000v','2026-09-15 09:00:00',NULL,NULL,NULL,0,'2026-05-29 09:00:00','2026-10-11 09:00:00'),
  ('deal_00im','tnt_0016','Поддержка и развитие — Компания','org_00ei',NULL,'pip_00ac','stg_00ah',42432000,'UZS',75,'open','exhibition','usr_000w','2026-10-29 09:00:00',NULL,NULL,NULL,1,'2026-09-26 09:00:00','2026-10-09 09:00:00'),
  ('deal_00iq','tnt_0016','Аудит дизайн-системы — Компания','org_00dx',NULL,'pip_00ac','stg_00ah',199544000,'UZS',75,'open','referral','usr_000t','2026-09-11 09:00:00',NULL,NULL,NULL,0,'2026-06-29 09:00:00','2026-10-11 09:00:00'),
  ('deal_00iu','tnt_0016','Поддержка и развитие — BrightWave Digital','org_00bm','con_00bs','pip_00ac','stg_00ak',168080000,'UZS',100,'won','telegram','usr_000y','2026-09-15 09:00:00','2026-11-01 09:00:00',NULL,NULL,0,'2026-09-27 09:00:00','2026-10-06 09:00:00'),
  ('deal_00iy','tnt_0016','Внедрение CRM — Компания','org_00du',NULL,'pip_00ac','stg_00ae',118795000,'UZS',25,'open','cold_call','usr_000r','2026-09-11 09:00:00',NULL,NULL,NULL,0,'2026-06-12 09:00:00','2026-10-09 09:00:00'),
  ('deal_00j3','tnt_0016','Внедрение CRM — Registan Bank','org_00cu','con_00av','pip_00ac','stg_00ai',182175000,'UZS',90,'open','api','usr_000x','2026-11-11 09:00:00',NULL,NULL,NULL,0,'2026-07-20 09:00:00','2026-10-10 09:00:00'),
  ('deal_00j6','tnt_0016','Поддержка и развитие — PrimeCargo','org_00bu','con_00c5','pip_00ac','stg_00aj',158649000,'UZS',95,'won','campaign','usr_000t','2026-09-09 09:00:00','2026-06-15 09:00:00',NULL,NULL,1,'2026-06-06 09:00:00','2026-10-12 09:00:00'),
  ('deal_00ja','tnt_0016','Android приложение — Компания','org_00e6','con_00bn','pip_00ac','stg_00ad',115610000,'UZS',10,'open','campaign','usr_0013','2026-10-10 09:00:00',NULL,NULL,NULL,0,'2026-05-09 09:00:00','2026-10-07 09:00:00'),
  ('deal_00je','tnt_0016','Внедрение CRM — Acme Corp','org_00au','con_00dq','pip_00ac','stg_00ak',168396000,'UZS',100,'won','telegram','usr_000u','2026-10-01 09:00:00','2026-06-30 09:00:00',NULL,NULL,0,'2026-06-11 09:00:00','2026-10-08 09:00:00'),
  ('deal_00jh','tnt_0016','Windows приложение — Компания','org_00dx','con_00eh','pip_00ac','stg_00ag',133387000,'UZS',60,'open','api','usr_000w','2026-09-11 09:00:00',NULL,NULL,NULL,0,'2026-07-10 09:00:00','2026-10-06 09:00:00'),
  ('deal_00jl','tnt_0016','Аудит дизайн-системы — TechNova','org_00b6','con_00bq','pip_00ac','stg_00aj',81623000,'UZS',95,'won','exhibition','usr_000t','2026-09-26 09:00:00','2026-06-29 09:00:00',NULL,NULL,0,'2026-06-02 09:00:00','2026-10-06 09:00:00'),
  ('deal_00jo','tnt_0016','Разработка сайта — Initech LLC','org_00b9','con_00eb','pip_00ac','stg_00ai',79688000,'UZS',90,'open','cold_call','usr_000w','2026-09-10 09:00:00',NULL,NULL,NULL,0,'2026-05-12 09:00:00','2026-10-12 09:00:00'),
  ('deal_00jr','tnt_0016','Поддержка и развитие — Stark Industries','org_00be',NULL,'pip_00ac','stg_00ak',83621000,'UZS',100,'won','api','usr_000w','2026-10-25 09:00:00','2026-05-27 09:00:00',NULL,NULL,0,'2026-05-17 09:00:00','2026-10-06 09:00:00'),
  ('deal_00jv','tnt_0016','Поддержка и развитие — Initech LLC','org_00b9','con_00di','pip_00ac','stg_00af',155352000,'UZS',40,'open','campaign','usr_000w','2026-10-22 09:00:00',NULL,NULL,NULL,0,'2026-05-22 09:00:00','2026-10-07 09:00:00'),
  ('deal_00jz','tnt_0016','Интеграция API — Navoi Mining Tech','org_00d3',NULL,'pip_00ac','stg_00aj',63601000,'UZS',95,'won','exhibition','usr_0013','2026-09-11 09:00:00','2026-09-12 09:00:00',NULL,NULL,0,'2026-08-05 09:00:00','2026-10-07 09:00:00'),
  ('deal_00k3','tnt_0016','Android приложение — Компания','org_00dp','con_00em','pip_00ac','stg_00ad',58682000,'UZS',10,'open','api','usr_000s','2026-10-18 09:00:00',NULL,NULL,NULL,0,'2026-09-15 09:00:00','2026-10-12 09:00:00');
INSERT INTO deals VALUES
  ('deal_00k6','tnt_0016','Поддержка и развитие — BlueOrbit Media','org_00c6','con_00bk','pip_00ac','stg_00ae',10114000,'UZS',25,'open','referral','usr_000s','2026-09-25 09:00:00',NULL,NULL,NULL,0,'2026-07-02 09:00:00','2026-10-07 09:00:00'),
  ('deal_00ka','tnt_0016','AI голосовой агент — BrightWave Digital','org_00bm','con_00bk','pip_00ac','stg_00ah',177928000,'UZS',75,'open','telegram','usr_000w','2026-11-08 09:00:00',NULL,NULL,NULL,0,'2026-06-29 09:00:00','2026-10-09 09:00:00'),
  ('deal_00kc','tnt_0016','Windows приложение — Registan Bank','org_00cu','con_00bw','pip_00ac','stg_00aj',88822000,'UZS',95,'won','exhibition','usr_000r','2026-11-23 09:00:00','2026-07-23 09:00:00',NULL,NULL,1,'2026-06-18 09:00:00','2026-10-11 09:00:00'),
  ('deal_00kg','tnt_0016','Внедрение CRM — Компания','org_00ed','con_00el','pip_00ac','stg_00ak',174350000,'UZS',100,'won','instagram','usr_000r','2026-11-11 09:00:00','2026-07-15 09:00:00',NULL,NULL,0,'2026-06-19 09:00:00','2026-10-07 09:00:00'),
  ('deal_00ki','tnt_0016','Разработка сайта — Initech LLC','org_00b9','con_00el','pip_00ac','stg_00ai',13341000,'UZS',90,'open','website','usr_000r','2026-10-15 09:00:00',NULL,NULL,NULL,0,'2026-06-14 09:00:00','2026-10-11 09:00:00'),
  ('deal_00kk','tnt_0016','Android приложение — TechNova','org_00b6','con_00e7','pip_00ac','stg_00ah',89687000,'UZS',75,'open','campaign','usr_000x','2026-09-30 09:00:00',NULL,NULL,NULL,0,'2026-07-25 09:00:00','2026-10-10 09:00:00'),
  ('deal_00ko','tnt_0016','Интеграция API — Компания','org_00e2',NULL,'pip_00ac','stg_00ad',5732000,'UZS',10,'open','telegram','usr_000x','2026-11-10 09:00:00',NULL,NULL,NULL,0,'2026-09-12 09:00:00','2026-10-08 09:00:00'),
  ('deal_00ks','tnt_0016','Поддержка и развитие — Vertex Digital','org_00c0',NULL,'pip_00ac','stg_00aj',187879000,'UZS',95,'won','exhibition','usr_000t','2026-11-14 09:00:00','2026-07-28 09:00:00',NULL,NULL,0,'2026-07-07 09:00:00','2026-10-12 09:00:00'),
  ('deal_00kv','tnt_0016','Windows приложение — Initech LLC','org_00b9',NULL,'pip_00ac','stg_00aj',59363000,'UZS',95,'won','website','usr_000x','2026-11-19 09:00:00','2026-08-11 09:00:00',NULL,NULL,0,'2026-07-02 09:00:00','2026-10-09 09:00:00'),
  ('deal_00ky','tnt_0016','Аудит дизайн-системы — Aral Shipping','org_00cq',NULL,'pip_00ac','stg_00ad',144809000,'UZS',10,'open','api','usr_000t','2026-09-17 09:00:00',NULL,NULL,NULL,0,'2026-04-26 09:00:00','2026-10-06 09:00:00'),
  ('deal_00l0','tnt_0016','Внедрение CRM — TechNova','org_00b6','con_00dr','pip_00ac','stg_00ad',4527000,'UZS',10,'open','telegram','usr_000v','2026-10-26 09:00:00',NULL,NULL,NULL,1,'2026-10-03 09:00:00','2026-10-08 09:00:00'),
  ('deal_00l3','tnt_0016','Android приложение — Fergana Textile','org_00ce','con_00ek','pip_00ac','stg_00aj',74582000,'UZS',95,'won','referral','usr_000t','2026-11-26 09:00:00','2026-06-26 09:00:00',NULL,NULL,0,'2026-06-21 09:00:00','2026-10-08 09:00:00'),
  ('deal_00l7','tnt_0016','Разработка сайта — Компания','org_00dh','con_00e5','pip_00ac','stg_00ai',140308000,'UZS',90,'open','referral','usr_000w','2026-09-03 09:00:00',NULL,NULL,NULL,0,'2026-05-20 09:00:00','2026-10-11 09:00:00'),
  ('deal_00lb','tnt_0016','Android приложение — Компания','org_00e2',NULL,'pip_00ac','stg_00ai',174300000,'UZS',90,'open','exhibition','usr_000t','2026-09-19 09:00:00',NULL,NULL,NULL,0,'2026-09-09 09:00:00','2026-10-11 09:00:00'),
  ('deal_00ld','tnt_0016','Аудит дизайн-системы — Silk Road Logistics','org_00bx','con_00ea','pip_00ac','stg_00ah',191098000,'UZS',75,'open','referral','usr_000x','2026-09-14 09:00:00',NULL,NULL,NULL,0,'2026-05-28 09:00:00','2026-10-09 09:00:00'),
  ('deal_00lh','tnt_0016','Android приложение — Initech LLC','org_00b9',NULL,'pip_00ac','stg_00ai',143199000,'UZS',90,'open','cold_call','usr_000s','2026-09-24 09:00:00',NULL,NULL,NULL,1,'2026-06-18 09:00:00','2026-10-11 09:00:00'),
  ('deal_00ll','tnt_0016','Аудит дизайн-системы — Initech LLC','org_00b9',NULL,'pip_00ac','stg_00af',108020000,'UZS',40,'open','cold_call','usr_000y','2026-10-18 09:00:00',NULL,NULL,NULL,0,'2026-09-20 09:00:00','2026-10-07 09:00:00'),
  ('deal_00lq','tnt_0016','Разработка сайта — TechNova','org_00b6',NULL,'pip_00ac','stg_00ag',159179000,'UZS',60,'open','api','usr_000w','2026-11-22 09:00:00',NULL,NULL,NULL,0,'2026-08-01 09:00:00','2026-10-06 09:00:00'),
  ('deal_00ls','tnt_0016','Интеграция API — Orbit Systems','org_00b1','con_00cd','pip_00ac','stg_00aj',61989000,'UZS',95,'won','referral','usr_000x','2026-09-27 09:00:00','2026-10-28 09:00:00',NULL,NULL,0,'2026-09-23 09:00:00','2026-10-06 09:00:00'),
  ('deal_00lw','tnt_0016','AI голосовой агент — Компания','org_00du','con_00bg','pip_00ac','stg_00ai',106075000,'UZS',90,'open','api','usr_000x','2026-10-13 09:00:00',NULL,NULL,NULL,0,'2026-09-23 09:00:00','2026-10-12 09:00:00'),
  ('deal_00ly','tnt_0016','Android приложение — Orbit Systems','org_00b1','con_00dj','pip_00ac','stg_00ag',81035000,'UZS',60,'open','telegram','usr_000t','2026-09-27 09:00:00',NULL,NULL,NULL,0,'2026-08-23 09:00:00','2026-10-07 09:00:00'),
  ('deal_00m2','tnt_0016','Android приложение — PrimeCargo','org_00bu','con_00dw','pip_00ac','stg_00ah',130104000,'UZS',75,'open','telegram','usr_000y','2026-10-11 09:00:00',NULL,NULL,NULL,0,'2026-09-29 09:00:00','2026-10-10 09:00:00'),
  ('deal_00m4','tnt_0016','Windows приложение — Umbrella Co','org_00bh','con_00dg','pip_00ac','stg_00ah',20291000,'UZS',75,'open','referral','usr_000t','2026-11-19 09:00:00',NULL,NULL,NULL,0,'2026-05-11 09:00:00','2026-10-07 09:00:00'),
  ('deal_00m6','tnt_0016','Разработка сайта — Компания','org_00dk','con_00dn','pip_00ac','stg_00ag',202375000,'UZS',60,'open','cold_call','usr_000u','2026-11-09 09:00:00',NULL,NULL,NULL,0,'2026-09-22 09:00:00','2026-10-12 09:00:00'),
  ('deal_00m8','tnt_0016','Windows приложение — Компания','org_00e2','con_00cs','pip_00ac','stg_00ag',205139000,'UZS',60,'open','referral','usr_000x','2026-09-21 09:00:00',NULL,NULL,NULL,0,'2026-06-14 09:00:00','2026-10-11 09:00:00'),
  ('deal_00mc','tnt_0016','Внедрение CRM — Компания','org_00du','con_00b0','pip_00ac','stg_00ag',98997000,'UZS',60,'open','referral','usr_000x','2026-09-08 09:00:00',NULL,NULL,NULL,0,'2026-05-24 09:00:00','2026-10-07 09:00:00'),
  ('deal_00mg','tnt_0016','Windows приложение — Nova Retail Group','org_00c3','con_00bq','pip_00ac','stg_00aj',106361000,'UZS',95,'won','telegram','usr_000s','2026-11-16 09:00:00','2026-09-23 09:00:00',NULL,NULL,0,'2026-09-14 09:00:00','2026-10-09 09:00:00'),
  ('deal_00mj','tnt_0016','Внедрение CRM — BlueOrbit Media','org_00c6','con_00e0','pip_00ac','stg_00ae',5967000,'UZS',25,'open','referral','usr_000x','2026-09-02 09:00:00',NULL,NULL,NULL,1,'2026-08-08 09:00:00','2026-10-10 09:00:00'),
  ('deal_00mm','tnt_0017','Разработка сайта — Stark Industries','org_00f0','con_00ex','pip_00al','stg_00ap',34275000,'UZS',60,'open','campaign','usr_000x','2026-11-12 09:00:00',NULL,NULL,NULL,0,'2026-05-23 09:00:00','2026-10-06 09:00:00'),
  ('deal_00mq','tnt_0017','Разработка сайта — Stark Industries','org_00f0','con_00fe','pip_00al','stg_00at',29464000,'UZS',100,'won','website','usr_000s','2026-09-19 09:00:00','2026-11-07 09:00:00',NULL,NULL,0,'2026-10-06 09:00:00','2026-10-07 09:00:00'),
  ('deal_00mt','tnt_0017','Интеграция API — TechNova','org_00ev',NULL,'pip_00al','stg_00an',87387000,'UZS',25,'open','cold_call','usr_0013','2026-11-21 09:00:00',NULL,NULL,NULL,0,'2026-10-08 09:00:00','2026-10-06 09:00:00'),
  ('deal_00mx','tnt_0017','Разработка сайта — Zenith Soft','org_00f7','con_00f6','pip_00al','stg_00ao',34407000,'UZS',40,'open','website','usr_000t','2026-09-21 09:00:00',NULL,NULL,NULL,0,'2026-05-09 09:00:00','2026-10-11 09:00:00'),
  ('deal_00n1','tnt_0017','Разработка сайта — Zenith Soft','org_00f7','con_00ep','pip_00al','stg_00ap',69343000,'UZS',60,'open','website','usr_000u','2026-09-30 09:00:00',NULL,NULL,NULL,0,'2026-05-26 09:00:00','2026-10-06 09:00:00'),
  ('deal_00n3','tnt_0017','Внедрение CRM — Zenith Soft','org_00f7','con_00f3','pip_00al','stg_00an',87879000,'UZS',25,'open','exhibition','usr_000y','2026-09-17 09:00:00',NULL,NULL,NULL,0,'2026-07-07 09:00:00','2026-10-11 09:00:00'),
  ('deal_00n5','tnt_0017','Android приложение — Initech LLC','org_00ey',NULL,'pip_00al','stg_00ap',23396000,'UZS',60,'open','api','usr_000s','2026-11-14 09:00:00',NULL,NULL,NULL,0,'2026-05-07 09:00:00','2026-10-11 09:00:00'),
  ('deal_00n9','tnt_0017','Поддержка и развитие — Stark Industries','org_00f0','con_00ez','pip_00al','stg_00an',39992000,'UZS',25,'open','campaign','usr_000v','2026-09-09 09:00:00',NULL,NULL,NULL,1,'2026-08-26 09:00:00','2026-10-06 09:00:00'),
  ('deal_00nc','tnt_0017','AI голосовой агент — Vertex Digital','org_00ff','con_00eu','pip_00al','stg_00aq',10185000,'UZS',75,'open','telegram','usr_000u','2026-10-11 09:00:00',NULL,NULL,NULL,1,'2026-09-18 09:00:00','2026-10-06 09:00:00'),
  ('deal_00nh','tnt_0017','Разработка сайта — Silk Road Logistics','org_00fc',NULL,'pip_00al','stg_00at',19766000,'UZS',100,'won','cold_call','usr_000t','2026-09-25 09:00:00','2026-08-31 09:00:00',NULL,NULL,0,'2026-07-27 09:00:00','2026-10-06 09:00:00'),
  ('deal_00nj','tnt_0017','Windows приложение — Umbrella Co','org_00f2','con_00ez','pip_00al','stg_00ap',36136000,'UZS',60,'open','website','usr_000v','2026-11-22 09:00:00',NULL,NULL,NULL,0,'2026-09-23 09:00:00','2026-10-11 09:00:00'),
  ('deal_00nl','tnt_0017','AI голосовой агент — Acme Corp','org_00en','con_00eo','pip_00al','stg_00an',35582000,'UZS',25,'lost','api','usr_000v','2026-11-21 09:00:00',NULL,'2026-09-12 09:00:00','Выбрали конкурента',0,'2026-08-30 09:00:00','2026-10-06 09:00:00'),
  ('deal_00np','tnt_0017','Поддержка и развитие — Initech LLC','org_00ey',NULL,'pip_00al','stg_00ar',86543000,'UZS',90,'open','exhibition','usr_0013','2026-11-18 09:00:00',NULL,NULL,NULL,0,'2026-09-30 09:00:00','2026-10-07 09:00:00'),
  ('deal_00nr','tnt_0017','Поддержка и развитие — Vertex Digital','org_00ff','con_00fg','pip_00al','stg_00ao',42871000,'UZS',40,'open','telegram','usr_000t','2026-11-21 09:00:00',NULL,NULL,NULL,0,'2026-05-29 09:00:00','2026-10-09 09:00:00'),
  ('deal_00nv','tnt_0017','Поддержка и развитие — Umbrella Co','org_00f2','con_00ep','pip_00al','stg_00am',25770000,'UZS',10,'open','api','usr_000w','2026-09-24 09:00:00',NULL,NULL,NULL,1,'2026-08-05 09:00:00','2026-10-10 09:00:00'),
  ('deal_00nx','tnt_0017','Разработка сайта — Zenith Soft','org_00f7','con_00f4','pip_00al','stg_00ar',45454000,'UZS',90,'open','api','usr_000w','2026-09-29 09:00:00',NULL,NULL,NULL,0,'2026-09-28 09:00:00','2026-10-09 09:00:00'),
  ('deal_00o0','tnt_0017','Android приложение — Globex Industries','org_00eq','con_00f8','pip_00al','stg_00ao',73764000,'UZS',40,'open','telegram','usr_000v','2026-10-04 09:00:00',NULL,NULL,NULL,1,'2026-06-13 09:00:00','2026-10-10 09:00:00'),
  ('deal_00o4','tnt_0017','Android приложение — Stark Industries','org_00f0',NULL,'pip_00al','stg_00ao',83191000,'UZS',40,'open','exhibition','usr_000y','2026-10-06 09:00:00',NULL,NULL,NULL,0,'2026-05-27 09:00:00','2026-10-07 09:00:00'),
  ('deal_00o8','tnt_0017','Windows приложение — BrightWave Digital','org_00f5',NULL,'pip_00al','stg_00at',54132000,'UZS',100,'won','telegram','usr_000x','2026-09-07 09:00:00','2026-06-11 09:00:00',NULL,NULL,0,'2026-05-05 09:00:00','2026-10-10 09:00:00'),
  ('deal_00oa','tnt_0017','Поддержка и развитие — Orbit Systems','org_00et','con_00fd','pip_00al','stg_00ar',84062000,'UZS',90,'open','cold_call','usr_000x','2026-11-04 09:00:00',NULL,NULL,NULL,0,'2026-09-22 09:00:00','2026-10-12 09:00:00'),
  ('deal_00od','tnt_0017','Android приложение — Stark Industries','org_00f0',NULL,'pip_00al','stg_00as',71406000,'UZS',95,'won','api','usr_000v','2026-09-21 09:00:00','2026-08-22 09:00:00',NULL,NULL,0,'2026-08-18 09:00:00','2026-10-09 09:00:00'),
  ('deal_00og','tnt_0017','AI голосовой агент — PrimeCargo','org_00f9',NULL,'pip_00al','stg_00ar',13392000,'UZS',90,'open','exhibition','usr_000r','2026-09-13 09:00:00',NULL,NULL,NULL,0,'2026-09-13 09:00:00','2026-10-06 09:00:00');
INSERT INTO deals VALUES
  ('deal_00oi','tnt_0017','Аудит дизайн-системы — Globex Industries','org_00eq',NULL,'pip_00al','stg_00ap',48036000,'UZS',60,'open','referral','usr_000t','2026-10-21 09:00:00',NULL,NULL,NULL,0,'2026-07-01 09:00:00','2026-10-12 09:00:00'),
  ('deal_00ol','tnt_0017','Внедрение CRM — Vertex Digital','org_00ff','con_00ep','pip_00al','stg_00ap',29316000,'UZS',60,'lost','cold_call','usr_000t','2026-09-02 09:00:00',NULL,'2026-09-11 09:00:00','Нет бюджета',0,'2026-08-16 09:00:00','2026-10-06 09:00:00'),
  ('deal_00oo','tnt_0017','Интеграция API — Orbit Systems','org_00et','con_00eu','pip_00al','stg_00as',63190000,'UZS',95,'won','website','usr_000s','2026-11-11 09:00:00','2026-07-14 09:00:00',NULL,NULL,0,'2026-06-16 09:00:00','2026-10-10 09:00:00'),
  ('deal_00oq','tnt_0017','Поддержка и развитие — Zenith Soft','org_00f7','con_00f8','pip_00al','stg_00aq',23444000,'UZS',75,'open','instagram','usr_000s','2026-11-11 09:00:00',NULL,NULL,NULL,0,'2026-07-31 09:00:00','2026-10-09 09:00:00');

-- activities: 230 rows
INSERT INTO activities VALUES
  ('act_00fk','tnt_0016','email','Отправлено коммерческое предложение',NULL,'deal','deal_00fj','usr_000y','Zilola Karimova',NULL,'2026-08-13 09:00:00'),
  ('act_00fl','tnt_0016','note','Добавлена заметка',NULL,'deal','deal_00fj','usr_000y','Zilola Karimova',NULL,'2026-08-18 09:00:00'),
  ('act_00fn','tnt_0016','note','Добавлена заметка',NULL,'deal','deal_00fm','usr_000u','Kamila Ergasheva',NULL,'2026-08-20 09:00:00'),
  ('act_00fo','tnt_0016','stage_change','Стадия изменена на «Квалификация»',NULL,'deal','deal_00fm','usr_000u','Kamila Ergasheva',NULL,'2026-08-21 09:00:00'),
  ('act_00fq','tnt_0016','stage_change','Стадия изменена на «Переговоры»',NULL,'deal','deal_00fp','usr_000w','Nilufar Ahmedova',NULL,'2026-07-18 09:00:00'),
  ('act_00fs','tnt_0016','email','Отправлено коммерческое предложение',NULL,'deal','deal_00fr','usr_000w','Nilufar Ahmedova',NULL,'2026-10-02 09:00:00'),
  ('act_00ft','tnt_0016','note','Добавлена заметка',NULL,'deal','deal_00fr','usr_000w','Nilufar Ahmedova',NULL,'2026-10-04 09:00:00'),
  ('act_00fv','tnt_0016','stage_change','Стадия изменена на «Квалификация»',NULL,'deal','deal_00fu','usr_000s','Dilnoza Yusupova',NULL,'2026-07-16 09:00:00'),
  ('act_00fw','tnt_0016','meeting','Проведена встреча',NULL,'deal','deal_00fu','usr_000s','Dilnoza Yusupova',NULL,'2026-07-18 09:00:00'),
  ('act_00fx','tnt_0016','task','Создана задача',NULL,'deal','deal_00fu','usr_000s','Dilnoza Yusupova',NULL,'2026-07-24 09:00:00'),
  ('act_00fy','tnt_0016','call','Исходящий звонок',NULL,'deal','deal_00fu','usr_000s','Dilnoza Yusupova',NULL,'2026-07-19 09:00:00'),
  ('act_00g0','tnt_0016','call','Исходящий звонок',NULL,'deal','deal_00fz','usr_000w','Nilufar Ahmedova',NULL,'2026-10-07 09:00:00'),
  ('act_00g2','tnt_0016','call','Исходящий звонок',NULL,'deal','deal_00g1','usr_000y','Zilola Karimova',NULL,'2026-10-07 09:00:00'),
  ('act_00g3','tnt_0016','meeting','Проведена встреча',NULL,'deal','deal_00g1','usr_000y','Zilola Karimova',NULL,'2026-10-12 09:00:00'),
  ('act_00g5','tnt_0016','task','Создана задача',NULL,'deal','deal_00g4','usr_000w','Nilufar Ahmedova',NULL,'2026-05-04 09:00:00'),
  ('act_00g7','tnt_0016','call','Исходящий звонок',NULL,'deal','deal_00g6','usr_000x','Bekzod Rahimov',NULL,'2026-05-11 09:00:00'),
  ('act_00g8','tnt_0016','note','Добавлена заметка',NULL,'deal','deal_00g6','usr_000x','Bekzod Rahimov',NULL,'2026-05-17 09:00:00'),
  ('act_00ga','tnt_0016','note','Добавлена заметка',NULL,'deal','deal_00g9','usr_000x','Bekzod Rahimov',NULL,'2026-06-09 09:00:00'),
  ('act_00gb','tnt_0016','note','Добавлена заметка',NULL,'deal','deal_00g9','usr_000x','Bekzod Rahimov',NULL,'2026-06-12 09:00:00'),
  ('act_00gd','tnt_0016','email','Отправлено коммерческое предложение',NULL,'deal','deal_00gc','usr_000v','Sardor Toshmatov',NULL,'2026-05-11 09:00:00'),
  ('act_00ge','tnt_0016','meeting','Проведена встреча',NULL,'deal','deal_00gc','usr_000v','Sardor Toshmatov',NULL,'2026-05-13 09:00:00'),
  ('act_00gf','tnt_0016','meeting','Проведена встреча',NULL,'deal','deal_00gc','usr_000v','Sardor Toshmatov',NULL,'2026-05-17 09:00:00'),
  ('act_00gh','tnt_0016','note','Добавлена заметка',NULL,'deal','deal_00gg','usr_0013','Demo User',NULL,'2026-07-05 09:00:00'),
  ('act_00gj','tnt_0016','call','Исходящий звонок',NULL,'deal','deal_00gi','usr_000s','Dilnoza Yusupova',NULL,'2026-05-16 09:00:00'),
  ('act_00gk','tnt_0016','email','Отправлено коммерческое предложение',NULL,'deal','deal_00gi','usr_000s','Dilnoza Yusupova',NULL,'2026-05-18 09:00:00'),
  ('act_00gl','tnt_0016','meeting','Проведена встреча',NULL,'deal','deal_00gi','usr_000s','Dilnoza Yusupova',NULL,'2026-05-28 09:00:00'),
  ('act_00gm','tnt_0016','task','Создана задача',NULL,'deal','deal_00gi','usr_000s','Dilnoza Yusupova',NULL,'2026-05-19 09:00:00'),
  ('act_00go','tnt_0016','task','Создана задача',NULL,'deal','deal_00gn','usr_000t','Rustam Nazarov',NULL,'2026-07-04 09:00:00'),
  ('act_00gq','tnt_0016','task','Создана задача',NULL,'deal','deal_00gp','usr_000v','Sardor Toshmatov',NULL,'2026-06-01 09:00:00'),
  ('act_00gr','tnt_0016','meeting','Проведена встреча',NULL,'deal','deal_00gp','usr_000v','Sardor Toshmatov',NULL,'2026-06-07 09:00:00'),
  ('act_00gs','tnt_0016','email','Отправлено коммерческое предложение',NULL,'deal','deal_00gp','usr_000v','Sardor Toshmatov',NULL,'2026-06-03 09:00:00'),
  ('act_00gu','tnt_0016','note','Добавлена заметка',NULL,'deal','deal_00gt','usr_000x','Bekzod Rahimov',NULL,'2026-09-25 09:00:00'),
  ('act_00gv','tnt_0016','email','Отправлено коммерческое предложение',NULL,'deal','deal_00gt','usr_000x','Bekzod Rahimov',NULL,'2026-09-27 09:00:00'),
  ('act_00gw','tnt_0016','stage_change','Стадия изменена на «Заказ»',NULL,'deal','deal_00gt','usr_000x','Bekzod Rahimov',NULL,'2026-10-07 09:00:00'),
  ('act_00gy','tnt_0016','task','Создана задача',NULL,'deal','deal_00gx','usr_000x','Bekzod Rahimov',NULL,'2026-08-21 09:00:00'),
  ('act_00gz','tnt_0016','stage_change','Стадия изменена на «Новый лид»',NULL,'deal','deal_00gx','usr_000x','Bekzod Rahimov',NULL,'2026-08-22 09:00:00'),
  ('act_00h0','tnt_0016','note','Добавлена заметка',NULL,'deal','deal_00gx','usr_000x','Bekzod Rahimov',NULL,'2026-08-31 09:00:00'),
  ('act_00h2','tnt_0016','stage_change','Стадия изменена на «Выполнено»',NULL,'deal','deal_00h1','usr_000r','Jasher Deen',NULL,'2026-09-09 09:00:00'),
  ('act_00h3','tnt_0016','task','Создана задача',NULL,'deal','deal_00h1','usr_000r','Jasher Deen',NULL,'2026-09-14 09:00:00'),
  ('act_00h4','tnt_0016','email','Отправлено коммерческое предложение',NULL,'deal','deal_00h1','usr_000r','Jasher Deen',NULL,'2026-09-15 09:00:00'),
  ('act_00h6','tnt_0016','task','Создана задача',NULL,'deal','deal_00h5','usr_000v','Sardor Toshmatov',NULL,'2026-07-05 09:00:00'),
  ('act_00h8','tnt_0016','note','Добавлена заметка',NULL,'deal','deal_00h7','usr_000r','Jasher Deen',NULL,'2026-09-26 09:00:00'),
  ('act_00ha','tnt_0016','note','Добавлена заметка',NULL,'deal','deal_00h9','usr_000s','Dilnoza Yusupova',NULL,'2026-05-14 09:00:00'),
  ('act_00hb','tnt_0016','task','Создана задача',NULL,'deal','deal_00h9','usr_000s','Dilnoza Yusupova',NULL,'2026-05-19 09:00:00'),
  ('act_00hd','tnt_0016','task','Создана задача',NULL,'deal','deal_00hc','usr_000x','Bekzod Rahimov',NULL,'2026-06-28 09:00:00'),
  ('act_00he','tnt_0016','stage_change','Стадия изменена на «Предложение»',NULL,'deal','deal_00hc','usr_000x','Bekzod Rahimov',NULL,'2026-06-29 09:00:00'),
  ('act_00hg','tnt_0016','stage_change','Стадия изменена на «Переговоры»',NULL,'deal','deal_00hf','usr_000v','Sardor Toshmatov',NULL,'2026-05-14 09:00:00'),
  ('act_00hh','tnt_0016','note','Добавлена заметка',NULL,'deal','deal_00hf','usr_000v','Sardor Toshmatov',NULL,'2026-05-19 09:00:00'),
  ('act_00hj','tnt_0016','meeting','Проведена встреча',NULL,'deal','deal_00hi','usr_000t','Rustam Nazarov',NULL,'2026-07-29 09:00:00'),
  ('act_00hk','tnt_0016','email','Отправлено коммерческое предложение',NULL,'deal','deal_00hi','usr_000t','Rustam Nazarov',NULL,'2026-08-03 09:00:00');
INSERT INTO activities VALUES
  ('act_00hl','tnt_0016','note','Добавлена заметка',NULL,'deal','deal_00hi','usr_000t','Rustam Nazarov',NULL,'2026-08-04 09:00:00'),
  ('act_00hn','tnt_0016','meeting','Проведена встреча',NULL,'deal','deal_00hm','usr_000u','Kamila Ergasheva',NULL,'2026-06-02 09:00:00'),
  ('act_00ho','tnt_0016','call','Исходящий звонок',NULL,'deal','deal_00hm','usr_000u','Kamila Ergasheva',NULL,'2026-06-05 09:00:00'),
  ('act_00hq','tnt_0016','stage_change','Стадия изменена на «Контакт»',NULL,'deal','deal_00hp','usr_000s','Dilnoza Yusupova',NULL,'2026-10-02 09:00:00'),
  ('act_00hr','tnt_0016','call','Исходящий звонок',NULL,'deal','deal_00hp','usr_000s','Dilnoza Yusupova',NULL,'2026-10-03 09:00:00'),
  ('act_00ht','tnt_0016','meeting','Проведена встреча',NULL,'deal','deal_00hs','usr_000u','Kamila Ergasheva',NULL,'2026-08-16 09:00:00'),
  ('act_00hu','tnt_0016','note','Добавлена заметка',NULL,'deal','deal_00hs','usr_000u','Kamila Ergasheva',NULL,'2026-08-19 09:00:00'),
  ('act_00hw','tnt_0016','call','Исходящий звонок',NULL,'deal','deal_00hv','usr_000t','Rustam Nazarov',NULL,'2026-06-14 09:00:00'),
  ('act_00hx','tnt_0016','email','Отправлено коммерческое предложение',NULL,'deal','deal_00hv','usr_000t','Rustam Nazarov',NULL,'2026-06-15 09:00:00'),
  ('act_00hz','tnt_0016','task','Создана задача',NULL,'deal','deal_00hy','usr_000r','Jasher Deen',NULL,'2026-07-11 09:00:00'),
  ('act_00i0','tnt_0016','stage_change','Стадия изменена на «Согласовано»',NULL,'deal','deal_00hy','usr_000r','Jasher Deen',NULL,'2026-07-13 09:00:00'),
  ('act_00i2','tnt_0016','task','Создана задача',NULL,'deal','deal_00i1','usr_000t','Rustam Nazarov',NULL,'2026-06-23 09:00:00'),
  ('act_00i3','tnt_0016','email','Отправлено коммерческое предложение',NULL,'deal','deal_00i1','usr_000t','Rustam Nazarov',NULL,'2026-06-26 09:00:00'),
  ('act_00i4','tnt_0016','email','Отправлено коммерческое предложение',NULL,'deal','deal_00i1','usr_000t','Rustam Nazarov',NULL,'2026-06-25 09:00:00'),
  ('act_00i6','tnt_0016','task','Создана задача',NULL,'deal','deal_00i5','usr_000y','Zilola Karimova',NULL,'2026-07-09 09:00:00'),
  ('act_00i7','tnt_0016','call','Исходящий звонок',NULL,'deal','deal_00i5','usr_000y','Zilola Karimova',NULL,'2026-07-15 09:00:00'),
  ('act_00i8','tnt_0016','note','Добавлена заметка',NULL,'deal','deal_00i5','usr_000y','Zilola Karimova',NULL,'2026-07-13 09:00:00'),
  ('act_00i9','tnt_0016','task','Создана задача',NULL,'deal','deal_00i5','usr_000y','Zilola Karimova',NULL,'2026-07-21 09:00:00'),
  ('act_00ib','tnt_0016','meeting','Проведена встреча',NULL,'deal','deal_00ia','usr_000t','Rustam Nazarov',NULL,'2026-09-23 09:00:00'),
  ('act_00id','tnt_0016','email','Отправлено коммерческое предложение',NULL,'deal','deal_00ic','usr_000v','Sardor Toshmatov',NULL,'2026-05-24 09:00:00'),
  ('act_00ie','tnt_0016','stage_change','Стадия изменена на «Заказ»',NULL,'deal','deal_00ic','usr_000v','Sardor Toshmatov',NULL,'2026-05-29 09:00:00'),
  ('act_00if','tnt_0016','call','Исходящий звонок',NULL,'deal','deal_00ic','usr_000v','Sardor Toshmatov',NULL,'2026-06-03 09:00:00'),
  ('act_00ih','tnt_0016','call','Исходящий звонок',NULL,'deal','deal_00ig','usr_000u','Kamila Ergasheva',NULL,'2026-09-05 09:00:00'),
  ('act_00ii','tnt_0016','email','Отправлено коммерческое предложение',NULL,'deal','deal_00ig','usr_000u','Kamila Ergasheva',NULL,'2026-09-07 09:00:00'),
  ('act_00ik','tnt_0016','call','Исходящий звонок',NULL,'deal','deal_00ij','usr_000v','Sardor Toshmatov',NULL,'2026-05-29 09:00:00'),
  ('act_00il','tnt_0016','meeting','Проведена встреча',NULL,'deal','deal_00ij','usr_000v','Sardor Toshmatov',NULL,'2026-06-04 09:00:00'),
  ('act_00in','tnt_0016','email','Отправлено коммерческое предложение',NULL,'deal','deal_00im','usr_000w','Nilufar Ahmedova',NULL,'2026-09-26 09:00:00'),
  ('act_00io','tnt_0016','stage_change','Стадия изменена на «Предложение»',NULL,'deal','deal_00im','usr_000w','Nilufar Ahmedova',NULL,'2026-09-29 09:00:00'),
  ('act_00ip','tnt_0016','meeting','Проведена встреча',NULL,'deal','deal_00im','usr_000w','Nilufar Ahmedova',NULL,'2026-09-28 09:00:00'),
  ('act_00ir','tnt_0016','meeting','Проведена встреча',NULL,'deal','deal_00iq','usr_000t','Rustam Nazarov',NULL,'2026-06-29 09:00:00'),
  ('act_00is','tnt_0016','meeting','Проведена встреча',NULL,'deal','deal_00iq','usr_000t','Rustam Nazarov',NULL,'2026-07-05 09:00:00'),
  ('act_00it','tnt_0016','meeting','Проведена встреча',NULL,'deal','deal_00iq','usr_000t','Rustam Nazarov',NULL,'2026-07-07 09:00:00'),
  ('act_00iv','tnt_0016','email','Отправлено коммерческое предложение',NULL,'deal','deal_00iu','usr_000y','Zilola Karimova',NULL,'2026-09-27 09:00:00'),
  ('act_00iw','tnt_0016','call','Исходящий звонок',NULL,'deal','deal_00iu','usr_000y','Zilola Karimova',NULL,'2026-09-30 09:00:00'),
  ('act_00ix','tnt_0016','call','Исходящий звонок',NULL,'deal','deal_00iu','usr_000y','Zilola Karimova',NULL,'2026-10-07 09:00:00'),
  ('act_00iz','tnt_0016','call','Исходящий звонок',NULL,'deal','deal_00iy','usr_000r','Jasher Deen',NULL,'2026-06-12 09:00:00'),
  ('act_00j0','tnt_0016','stage_change','Стадия изменена на «Квалификация»',NULL,'deal','deal_00iy','usr_000r','Jasher Deen',NULL,'2026-06-13 09:00:00'),
  ('act_00j1','tnt_0016','email','Отправлено коммерческое предложение',NULL,'deal','deal_00iy','usr_000r','Jasher Deen',NULL,'2026-06-22 09:00:00'),
  ('act_00j2','tnt_0016','call','Исходящий звонок',NULL,'deal','deal_00iy','usr_000r','Jasher Deen',NULL,'2026-06-15 09:00:00'),
  ('act_00j4','tnt_0016','meeting','Проведена встреча',NULL,'deal','deal_00j3','usr_000x','Bekzod Rahimov',NULL,'2026-07-20 09:00:00'),
  ('act_00j5','tnt_0016','note','Добавлена заметка',NULL,'deal','deal_00j3','usr_000x','Bekzod Rahimov',NULL,'2026-07-22 09:00:00'),
  ('act_00j7','tnt_0016','email','Отправлено коммерческое предложение',NULL,'deal','deal_00j6','usr_000t','Rustam Nazarov',NULL,'2026-06-06 09:00:00'),
  ('act_00j8','tnt_0016','meeting','Проведена встреча',NULL,'deal','deal_00j6','usr_000t','Rustam Nazarov',NULL,'2026-06-10 09:00:00'),
  ('act_00j9','tnt_0016','stage_change','Стадия изменена на «Заказ»',NULL,'deal','deal_00j6','usr_000t','Rustam Nazarov',NULL,'2026-06-12 09:00:00'),
  ('act_00jb','tnt_0016','stage_change','Стадия изменена на «Новый лид»',NULL,'deal','deal_00ja','usr_0013','Demo User',NULL,'2026-05-09 09:00:00'),
  ('act_00jc','tnt_0016','note','Добавлена заметка',NULL,'deal','deal_00ja','usr_0013','Demo User',NULL,'2026-05-10 09:00:00'),
  ('act_00jd','tnt_0016','task','Создана задача',NULL,'deal','deal_00ja','usr_0013','Demo User',NULL,'2026-05-17 09:00:00'),
  ('act_00jf','tnt_0016','email','Отправлено коммерческое предложение',NULL,'deal','deal_00je','usr_000u','Kamila Ergasheva',NULL,'2026-06-11 09:00:00'),
  ('act_00jg','tnt_0016','stage_change','Стадия изменена на «Выполнено»',NULL,'deal','deal_00je','usr_000u','Kamila Ergasheva',NULL,'2026-06-14 09:00:00'),
  ('act_00ji','tnt_0016','call','Исходящий звонок',NULL,'deal','deal_00jh','usr_000w','Nilufar Ahmedova',NULL,'2026-07-10 09:00:00');
INSERT INTO activities VALUES
  ('act_00jj','tnt_0016','note','Добавлена заметка',NULL,'deal','deal_00jh','usr_000w','Nilufar Ahmedova',NULL,'2026-07-15 09:00:00'),
  ('act_00jk','tnt_0016','call','Исходящий звонок',NULL,'deal','deal_00jh','usr_000w','Nilufar Ahmedova',NULL,'2026-07-22 09:00:00'),
  ('act_00jm','tnt_0016','meeting','Проведена встреча',NULL,'deal','deal_00jl','usr_000t','Rustam Nazarov',NULL,'2026-06-02 09:00:00'),
  ('act_00jn','tnt_0016','call','Исходящий звонок',NULL,'deal','deal_00jl','usr_000t','Rustam Nazarov',NULL,'2026-06-07 09:00:00'),
  ('act_00jp','tnt_0016','task','Создана задача',NULL,'deal','deal_00jo','usr_000w','Nilufar Ahmedova',NULL,'2026-05-12 09:00:00'),
  ('act_00jq','tnt_0016','call','Исходящий звонок',NULL,'deal','deal_00jo','usr_000w','Nilufar Ahmedova',NULL,'2026-05-18 09:00:00'),
  ('act_00js','tnt_0016','meeting','Проведена встреча',NULL,'deal','deal_00jr','usr_000w','Nilufar Ahmedova',NULL,'2026-05-17 09:00:00'),
  ('act_00jt','tnt_0016','call','Исходящий звонок',NULL,'deal','deal_00jr','usr_000w','Nilufar Ahmedova',NULL,'2026-05-20 09:00:00'),
  ('act_00ju','tnt_0016','task','Создана задача',NULL,'deal','deal_00jr','usr_000w','Nilufar Ahmedova',NULL,'2026-05-19 09:00:00'),
  ('act_00jw','tnt_0016','note','Добавлена заметка',NULL,'deal','deal_00jv','usr_000w','Nilufar Ahmedova',NULL,'2026-05-22 09:00:00'),
  ('act_00jx','tnt_0016','meeting','Проведена встреча',NULL,'deal','deal_00jv','usr_000w','Nilufar Ahmedova',NULL,'2026-05-26 09:00:00'),
  ('act_00jy','tnt_0016','stage_change','Стадия изменена на «Контакт»',NULL,'deal','deal_00jv','usr_000w','Nilufar Ahmedova',NULL,'2026-05-28 09:00:00'),
  ('act_00k0','tnt_0016','meeting','Проведена встреча',NULL,'deal','deal_00jz','usr_0013','Demo User',NULL,'2026-08-05 09:00:00'),
  ('act_00k1','tnt_0016','email','Отправлено коммерческое предложение',NULL,'deal','deal_00jz','usr_0013','Demo User',NULL,'2026-08-06 09:00:00'),
  ('act_00k2','tnt_0016','call','Исходящий звонок',NULL,'deal','deal_00jz','usr_0013','Demo User',NULL,'2026-08-15 09:00:00'),
  ('act_00k4','tnt_0016','email','Отправлено коммерческое предложение',NULL,'deal','deal_00k3','usr_000s','Dilnoza Yusupova',NULL,'2026-09-15 09:00:00'),
  ('act_00k5','tnt_0016','stage_change','Стадия изменена на «Новый лид»',NULL,'deal','deal_00k3','usr_000s','Dilnoza Yusupova',NULL,'2026-09-16 09:00:00'),
  ('act_00k7','tnt_0016','call','Исходящий звонок',NULL,'deal','deal_00k6','usr_000s','Dilnoza Yusupova',NULL,'2026-07-02 09:00:00'),
  ('act_00k8','tnt_0016','meeting','Проведена встреча',NULL,'deal','deal_00k6','usr_000s','Dilnoza Yusupova',NULL,'2026-07-06 09:00:00'),
  ('act_00k9','tnt_0016','note','Добавлена заметка',NULL,'deal','deal_00k6','usr_000s','Dilnoza Yusupova',NULL,'2026-07-06 09:00:00'),
  ('act_00kb','tnt_0016','call','Исходящий звонок',NULL,'deal','deal_00ka','usr_000w','Nilufar Ahmedova',NULL,'2026-06-29 09:00:00'),
  ('act_00kd','tnt_0016','task','Создана задача',NULL,'deal','deal_00kc','usr_000r','Jasher Deen',NULL,'2026-06-18 09:00:00'),
  ('act_00ke','tnt_0016','call','Исходящий звонок',NULL,'deal','deal_00kc','usr_000r','Jasher Deen',NULL,'2026-06-20 09:00:00'),
  ('act_00kf','tnt_0016','call','Исходящий звонок',NULL,'deal','deal_00kc','usr_000r','Jasher Deen',NULL,'2026-06-20 09:00:00'),
  ('act_00kh','tnt_0016','task','Создана задача',NULL,'deal','deal_00kg','usr_000r','Jasher Deen',NULL,'2026-06-19 09:00:00'),
  ('act_00kj','tnt_0016','task','Создана задача',NULL,'deal','deal_00ki','usr_000r','Jasher Deen',NULL,'2026-06-14 09:00:00'),
  ('act_00kl','tnt_0016','task','Создана задача',NULL,'deal','deal_00kk','usr_000x','Bekzod Rahimov',NULL,'2026-07-25 09:00:00'),
  ('act_00km','tnt_0016','note','Добавлена заметка',NULL,'deal','deal_00kk','usr_000x','Bekzod Rahimov',NULL,'2026-07-27 09:00:00'),
  ('act_00kn','tnt_0016','stage_change','Стадия изменена на «Предложение»',NULL,'deal','deal_00kk','usr_000x','Bekzod Rahimov',NULL,'2026-07-29 09:00:00'),
  ('act_00kp','tnt_0016','meeting','Проведена встреча',NULL,'deal','deal_00ko','usr_000x','Bekzod Rahimov',NULL,'2026-09-12 09:00:00'),
  ('act_00kq','tnt_0016','stage_change','Стадия изменена на «Новый лид»',NULL,'deal','deal_00ko','usr_000x','Bekzod Rahimov',NULL,'2026-09-14 09:00:00'),
  ('act_00kr','tnt_0016','meeting','Проведена встреча',NULL,'deal','deal_00ko','usr_000x','Bekzod Rahimov',NULL,'2026-09-20 09:00:00'),
  ('act_00kt','tnt_0016','call','Исходящий звонок',NULL,'deal','deal_00ks','usr_000t','Rustam Nazarov',NULL,'2026-07-07 09:00:00'),
  ('act_00ku','tnt_0016','email','Отправлено коммерческое предложение',NULL,'deal','deal_00ks','usr_000t','Rustam Nazarov',NULL,'2026-07-10 09:00:00'),
  ('act_00kw','tnt_0016','call','Исходящий звонок',NULL,'deal','deal_00kv','usr_000x','Bekzod Rahimov',NULL,'2026-07-02 09:00:00'),
  ('act_00kx','tnt_0016','email','Отправлено коммерческое предложение',NULL,'deal','deal_00kv','usr_000x','Bekzod Rahimov',NULL,'2026-07-06 09:00:00'),
  ('act_00kz','tnt_0016','meeting','Проведена встреча',NULL,'deal','deal_00ky','usr_000t','Rustam Nazarov',NULL,'2026-04-26 09:00:00'),
  ('act_00l1','tnt_0016','stage_change','Стадия изменена на «Новый лид»',NULL,'deal','deal_00l0','usr_000v','Sardor Toshmatov',NULL,'2026-10-03 09:00:00'),
  ('act_00l2','tnt_0016','task','Создана задача',NULL,'deal','deal_00l0','usr_000v','Sardor Toshmatov',NULL,'2026-10-04 09:00:00'),
  ('act_00l4','tnt_0016','task','Создана задача',NULL,'deal','deal_00l3','usr_000t','Rustam Nazarov',NULL,'2026-06-21 09:00:00'),
  ('act_00l5','tnt_0016','email','Отправлено коммерческое предложение',NULL,'deal','deal_00l3','usr_000t','Rustam Nazarov',NULL,'2026-06-23 09:00:00'),
  ('act_00l6','tnt_0016','meeting','Проведена встреча',NULL,'deal','deal_00l3','usr_000t','Rustam Nazarov',NULL,'2026-07-03 09:00:00'),
  ('act_00l8','tnt_0016','stage_change','Стадия изменена на «Согласовано»',NULL,'deal','deal_00l7','usr_000w','Nilufar Ahmedova',NULL,'2026-05-20 09:00:00'),
  ('act_00l9','tnt_0016','stage_change','Стадия изменена на «Согласовано»',NULL,'deal','deal_00l7','usr_000w','Nilufar Ahmedova',NULL,'2026-05-26 09:00:00'),
  ('act_00la','tnt_0016','note','Добавлена заметка',NULL,'deal','deal_00l7','usr_000w','Nilufar Ahmedova',NULL,'2026-05-26 09:00:00'),
  ('act_00lc','tnt_0016','email','Отправлено коммерческое предложение',NULL,'deal','deal_00lb','usr_000t','Rustam Nazarov',NULL,'2026-09-09 09:00:00'),
  ('act_00le','tnt_0016','task','Создана задача',NULL,'deal','deal_00ld','usr_000x','Bekzod Rahimov',NULL,'2026-05-28 09:00:00'),
  ('act_00lf','tnt_0016','email','Отправлено коммерческое предложение',NULL,'deal','deal_00ld','usr_000x','Bekzod Rahimov',NULL,'2026-05-30 09:00:00'),
  ('act_00lg','tnt_0016','stage_change','Стадия изменена на «Предложение»',NULL,'deal','deal_00ld','usr_000x','Bekzod Rahimov',NULL,'2026-06-05 09:00:00'),
  ('act_00li','tnt_0016','meeting','Проведена встреча',NULL,'deal','deal_00lh','usr_000s','Dilnoza Yusupova',NULL,'2026-06-18 09:00:00');
INSERT INTO activities VALUES
  ('act_00lj','tnt_0016','meeting','Проведена встреча',NULL,'deal','deal_00lh','usr_000s','Dilnoza Yusupova',NULL,'2026-06-23 09:00:00'),
  ('act_00lk','tnt_0016','email','Отправлено коммерческое предложение',NULL,'deal','deal_00lh','usr_000s','Dilnoza Yusupova',NULL,'2026-06-20 09:00:00'),
  ('act_00lm','tnt_0016','task','Создана задача',NULL,'deal','deal_00ll','usr_000y','Zilola Karimova',NULL,'2026-09-20 09:00:00'),
  ('act_00ln','tnt_0016','meeting','Проведена встреча',NULL,'deal','deal_00ll','usr_000y','Zilola Karimova',NULL,'2026-09-24 09:00:00'),
  ('act_00lo','tnt_0016','email','Отправлено коммерческое предложение',NULL,'deal','deal_00ll','usr_000y','Zilola Karimova',NULL,'2026-09-30 09:00:00'),
  ('act_00lp','tnt_0016','stage_change','Стадия изменена на «Контакт»',NULL,'deal','deal_00ll','usr_000y','Zilola Karimova',NULL,'2026-10-02 09:00:00'),
  ('act_00lr','tnt_0016','call','Исходящий звонок',NULL,'deal','deal_00lq','usr_000w','Nilufar Ahmedova',NULL,'2026-08-01 09:00:00'),
  ('act_00lt','tnt_0016','email','Отправлено коммерческое предложение',NULL,'deal','deal_00ls','usr_000x','Bekzod Rahimov',NULL,'2026-09-23 09:00:00'),
  ('act_00lu','tnt_0016','note','Добавлена заметка',NULL,'deal','deal_00ls','usr_000x','Bekzod Rahimov',NULL,'2026-09-29 09:00:00'),
  ('act_00lv','tnt_0016','meeting','Проведена встреча',NULL,'deal','deal_00ls','usr_000x','Bekzod Rahimov',NULL,'2026-09-29 09:00:00'),
  ('act_00lx','tnt_0016','stage_change','Стадия изменена на «Согласовано»',NULL,'deal','deal_00lw','usr_000x','Bekzod Rahimov',NULL,'2026-09-23 09:00:00'),
  ('act_00lz','tnt_0016','task','Создана задача',NULL,'deal','deal_00ly','usr_000t','Rustam Nazarov',NULL,'2026-08-23 09:00:00'),
  ('act_00m0','tnt_0016','note','Добавлена заметка',NULL,'deal','deal_00ly','usr_000t','Rustam Nazarov',NULL,'2026-08-25 09:00:00'),
  ('act_00m1','tnt_0016','task','Создана задача',NULL,'deal','deal_00ly','usr_000t','Rustam Nazarov',NULL,'2026-09-04 09:00:00'),
  ('act_00m3','tnt_0016','call','Исходящий звонок',NULL,'deal','deal_00m2','usr_000y','Zilola Karimova',NULL,'2026-09-29 09:00:00'),
  ('act_00m5','tnt_0016','meeting','Проведена встреча',NULL,'deal','deal_00m4','usr_000t','Rustam Nazarov',NULL,'2026-05-11 09:00:00'),
  ('act_00m7','tnt_0016','meeting','Проведена встреча',NULL,'deal','deal_00m6','usr_000u','Kamila Ergasheva',NULL,'2026-09-22 09:00:00'),
  ('act_00m9','tnt_0016','call','Исходящий звонок',NULL,'deal','deal_00m8','usr_000x','Bekzod Rahimov',NULL,'2026-06-14 09:00:00'),
  ('act_00ma','tnt_0016','task','Создана задача',NULL,'deal','deal_00m8','usr_000x','Bekzod Rahimov',NULL,'2026-06-15 09:00:00'),
  ('act_00mb','tnt_0016','task','Создана задача',NULL,'deal','deal_00m8','usr_000x','Bekzod Rahimov',NULL,'2026-06-16 09:00:00'),
  ('act_00md','tnt_0016','meeting','Проведена встреча',NULL,'deal','deal_00mc','usr_000x','Bekzod Rahimov',NULL,'2026-05-24 09:00:00'),
  ('act_00me','tnt_0016','stage_change','Стадия изменена на «Переговоры»',NULL,'deal','deal_00mc','usr_000x','Bekzod Rahimov',NULL,'2026-05-27 09:00:00'),
  ('act_00mf','tnt_0016','stage_change','Стадия изменена на «Переговоры»',NULL,'deal','deal_00mc','usr_000x','Bekzod Rahimov',NULL,'2026-06-03 09:00:00'),
  ('act_00mh','tnt_0016','call','Исходящий звонок',NULL,'deal','deal_00mg','usr_000s','Dilnoza Yusupova',NULL,'2026-09-14 09:00:00'),
  ('act_00mi','tnt_0016','call','Исходящий звонок',NULL,'deal','deal_00mg','usr_000s','Dilnoza Yusupova',NULL,'2026-09-17 09:00:00'),
  ('act_00mk','tnt_0016','call','Исходящий звонок',NULL,'deal','deal_00mj','usr_000x','Bekzod Rahimov',NULL,'2026-08-08 09:00:00'),
  ('act_00ml','tnt_0016','email','Отправлено коммерческое предложение',NULL,'deal','deal_00mj','usr_000x','Bekzod Rahimov',NULL,'2026-08-09 09:00:00'),
  ('act_00mn','tnt_0017','email','Отправлено коммерческое предложение',NULL,'deal','deal_00mm','usr_000x','Bekzod Rahimov',NULL,'2026-05-23 09:00:00'),
  ('act_00mo','tnt_0017','call','Исходящий звонок',NULL,'deal','deal_00mm','usr_000x','Bekzod Rahimov',NULL,'2026-05-25 09:00:00'),
  ('act_00mp','tnt_0017','stage_change','Стадия изменена на «Переговоры»',NULL,'deal','deal_00mm','usr_000x','Bekzod Rahimov',NULL,'2026-05-29 09:00:00'),
  ('act_00mr','tnt_0017','meeting','Проведена встреча',NULL,'deal','deal_00mq','usr_000s','Dilnoza Yusupova',NULL,'2026-10-06 09:00:00'),
  ('act_00ms','tnt_0017','task','Создана задача',NULL,'deal','deal_00mq','usr_000s','Dilnoza Yusupova',NULL,'2026-10-10 09:00:00'),
  ('act_00mu','tnt_0017','email','Отправлено коммерческое предложение',NULL,'deal','deal_00mt','usr_0013','Demo User',NULL,'2026-10-08 09:00:00'),
  ('act_00mv','tnt_0017','task','Создана задача',NULL,'deal','deal_00mt','usr_0013','Demo User',NULL,'2026-10-14 09:00:00'),
  ('act_00mw','tnt_0017','email','Отправлено коммерческое предложение',NULL,'deal','deal_00mt','usr_0013','Demo User',NULL,'2026-10-18 09:00:00'),
  ('act_00my','tnt_0017','call','Исходящий звонок',NULL,'deal','deal_00mx','usr_000t','Rustam Nazarov',NULL,'2026-05-09 09:00:00'),
  ('act_00mz','tnt_0017','task','Создана задача',NULL,'deal','deal_00mx','usr_000t','Rustam Nazarov',NULL,'2026-05-13 09:00:00'),
  ('act_00n0','tnt_0017','stage_change','Стадия изменена на «Контакт»',NULL,'deal','deal_00mx','usr_000t','Rustam Nazarov',NULL,'2026-05-21 09:00:00'),
  ('act_00n2','tnt_0017','call','Исходящий звонок',NULL,'deal','deal_00n1','usr_000u','Kamila Ergasheva',NULL,'2026-05-26 09:00:00'),
  ('act_00n4','tnt_0017','call','Исходящий звонок',NULL,'deal','deal_00n3','usr_000y','Zilola Karimova',NULL,'2026-07-07 09:00:00'),
  ('act_00n6','tnt_0017','stage_change','Стадия изменена на «Переговоры»',NULL,'deal','deal_00n5','usr_000s','Dilnoza Yusupova',NULL,'2026-05-07 09:00:00'),
  ('act_00n7','tnt_0017','email','Отправлено коммерческое предложение',NULL,'deal','deal_00n5','usr_000s','Dilnoza Yusupova',NULL,'2026-05-10 09:00:00'),
  ('act_00n8','tnt_0017','note','Добавлена заметка',NULL,'deal','deal_00n5','usr_000s','Dilnoza Yusupova',NULL,'2026-05-09 09:00:00'),
  ('act_00na','tnt_0017','call','Исходящий звонок',NULL,'deal','deal_00n9','usr_000v','Sardor Toshmatov',NULL,'2026-08-26 09:00:00'),
  ('act_00nb','tnt_0017','note','Добавлена заметка',NULL,'deal','deal_00n9','usr_000v','Sardor Toshmatov',NULL,'2026-09-01 09:00:00'),
  ('act_00nd','tnt_0017','meeting','Проведена встреча',NULL,'deal','deal_00nc','usr_000u','Kamila Ergasheva',NULL,'2026-09-18 09:00:00'),
  ('act_00ne','tnt_0017','note','Добавлена заметка',NULL,'deal','deal_00nc','usr_000u','Kamila Ergasheva',NULL,'2026-09-24 09:00:00'),
  ('act_00nf','tnt_0017','task','Создана задача',NULL,'deal','deal_00nc','usr_000u','Kamila Ergasheva',NULL,'2026-09-26 09:00:00'),
  ('act_00ng','tnt_0017','stage_change','Стадия изменена на «Предложение»',NULL,'deal','deal_00nc','usr_000u','Kamila Ergasheva',NULL,'2026-10-03 09:00:00'),
  ('act_00ni','tnt_0017','meeting','Проведена встреча',NULL,'deal','deal_00nh','usr_000t','Rustam Nazarov',NULL,'2026-07-27 09:00:00');
INSERT INTO activities VALUES
  ('act_00nk','tnt_0017','meeting','Проведена встреча',NULL,'deal','deal_00nj','usr_000v','Sardor Toshmatov',NULL,'2026-09-23 09:00:00'),
  ('act_00nm','tnt_0017','meeting','Проведена встреча',NULL,'deal','deal_00nl','usr_000v','Sardor Toshmatov',NULL,'2026-08-30 09:00:00'),
  ('act_00nn','tnt_0017','note','Добавлена заметка',NULL,'deal','deal_00nl','usr_000v','Sardor Toshmatov',NULL,'2026-09-05 09:00:00'),
  ('act_00no','tnt_0017','task','Создана задача',NULL,'deal','deal_00nl','usr_000v','Sardor Toshmatov',NULL,'2026-09-03 09:00:00'),
  ('act_00nq','tnt_0017','meeting','Проведена встреча',NULL,'deal','deal_00np','usr_0013','Demo User',NULL,'2026-09-30 09:00:00'),
  ('act_00ns','tnt_0017','email','Отправлено коммерческое предложение',NULL,'deal','deal_00nr','usr_000t','Rustam Nazarov',NULL,'2026-05-29 09:00:00'),
  ('act_00nt','tnt_0017','note','Добавлена заметка',NULL,'deal','deal_00nr','usr_000t','Rustam Nazarov',NULL,'2026-06-02 09:00:00'),
  ('act_00nu','tnt_0017','task','Создана задача',NULL,'deal','deal_00nr','usr_000t','Rustam Nazarov',NULL,'2026-06-06 09:00:00'),
  ('act_00nw','tnt_0017','task','Создана задача',NULL,'deal','deal_00nv','usr_000w','Nilufar Ahmedova',NULL,'2026-08-05 09:00:00'),
  ('act_00ny','tnt_0017','note','Добавлена заметка',NULL,'deal','deal_00nx','usr_000w','Nilufar Ahmedova',NULL,'2026-09-28 09:00:00'),
  ('act_00nz','tnt_0017','note','Добавлена заметка',NULL,'deal','deal_00nx','usr_000w','Nilufar Ahmedova',NULL,'2026-10-04 09:00:00'),
  ('act_00o1','tnt_0017','call','Исходящий звонок',NULL,'deal','deal_00o0','usr_000v','Sardor Toshmatov',NULL,'2026-06-13 09:00:00'),
  ('act_00o2','tnt_0017','task','Создана задача',NULL,'deal','deal_00o0','usr_000v','Sardor Toshmatov',NULL,'2026-06-15 09:00:00'),
  ('act_00o3','tnt_0017','meeting','Проведена встреча',NULL,'deal','deal_00o0','usr_000v','Sardor Toshmatov',NULL,'2026-06-19 09:00:00'),
  ('act_00o5','tnt_0017','stage_change','Стадия изменена на «Контакт»',NULL,'deal','deal_00o4','usr_000y','Zilola Karimova',NULL,'2026-05-27 09:00:00'),
  ('act_00o6','tnt_0017','call','Исходящий звонок',NULL,'deal','deal_00o4','usr_000y','Zilola Karimova',NULL,'2026-05-28 09:00:00'),
  ('act_00o7','tnt_0017','email','Отправлено коммерческое предложение',NULL,'deal','deal_00o4','usr_000y','Zilola Karimova',NULL,'2026-05-31 09:00:00'),
  ('act_00o9','tnt_0017','task','Создана задача',NULL,'deal','deal_00o8','usr_000x','Bekzod Rahimov',NULL,'2026-05-05 09:00:00'),
  ('act_00ob','tnt_0017','note','Добавлена заметка',NULL,'deal','deal_00oa','usr_000x','Bekzod Rahimov',NULL,'2026-09-22 09:00:00'),
  ('act_00oc','tnt_0017','note','Добавлена заметка',NULL,'deal','deal_00oa','usr_000x','Bekzod Rahimov',NULL,'2026-09-25 09:00:00'),
  ('act_00oe','tnt_0017','task','Создана задача',NULL,'deal','deal_00od','usr_000v','Sardor Toshmatov',NULL,'2026-08-18 09:00:00'),
  ('act_00of','tnt_0017','note','Добавлена заметка',NULL,'deal','deal_00od','usr_000v','Sardor Toshmatov',NULL,'2026-08-22 09:00:00'),
  ('act_00oh','tnt_0017','meeting','Проведена встреча',NULL,'deal','deal_00og','usr_000r','Jasher Deen',NULL,'2026-09-13 09:00:00'),
  ('act_00oj','tnt_0017','meeting','Проведена встреча',NULL,'deal','deal_00oi','usr_000t','Rustam Nazarov',NULL,'2026-07-01 09:00:00'),
  ('act_00ok','tnt_0017','meeting','Проведена встреча',NULL,'deal','deal_00oi','usr_000t','Rustam Nazarov',NULL,'2026-07-04 09:00:00'),
  ('act_00om','tnt_0017','stage_change','Стадия изменена на «Переговоры»',NULL,'deal','deal_00ol','usr_000t','Rustam Nazarov',NULL,'2026-08-16 09:00:00'),
  ('act_00on','tnt_0017','note','Добавлена заметка',NULL,'deal','deal_00ol','usr_000t','Rustam Nazarov',NULL,'2026-08-18 09:00:00'),
  ('act_00op','tnt_0017','meeting','Проведена встреча',NULL,'deal','deal_00oo','usr_000s','Dilnoza Yusupova',NULL,'2026-06-16 09:00:00'),
  ('act_00or','tnt_0017','note','Добавлена заметка',NULL,'deal','deal_00oq','usr_000s','Dilnoza Yusupova',NULL,'2026-07-31 09:00:00'),
  ('act_00os','tnt_0017','meeting','Проведена встреча',NULL,'deal','deal_00oq','usr_000s','Dilnoza Yusupova',NULL,'2026-08-04 09:00:00');

-- leads: 84 rows
INSERT INTO leads VALUES
  ('lead_00ot','tnt_0016','Заявка с сайта — Farrux Karimov','Farrux Karimov',NULL,'farrux@mail.uz','+998 84 972 77 25','instagram','new',50,7267000,'UZS','usr_000t',NULL,NULL,'2026-10-12 09:00:00','2026-10-10 09:00:00'),
  ('lead_00ou','tnt_0016','Интерес к CRM — Gulnora Ergasheva','Gulnora Ergasheva',NULL,'gulnora@mail.uz','+998 86 404 98 46','api','contacted',76,14245000,'UZS','usr_000v',NULL,NULL,'2026-08-15 09:00:00','2026-10-11 09:00:00'),
  ('lead_00ov','tnt_0016','Запрос коммерческого предложения — Bekzod Yusupova','Bekzod Yusupova',NULL,'bekzod@mail.uz','+998 98 797 77 32','exhibition','new',30,18656000,'UZS','usr_000v',NULL,NULL,'2026-09-04 09:00:00','2026-10-09 09:00:00'),
  ('lead_00ow','tnt_0016','Консультация по AI-агентам — Farrux Bekmurodov','Farrux Bekmurodov',NULL,'farrux@mail.uz','+998 79 823 43 73','referral','disqualified',56,16659000,'UZS','usr_000v',NULL,NULL,'2026-09-06 09:00:00','2026-10-12 09:00:00'),
  ('lead_00ox','tnt_0016','Интерес к CRM — Sardor Mirzaeva','Sardor Mirzaeva',NULL,'sardor@mail.uz','+998 97 321 48 57','campaign','new',63,76321000,'UZS','usr_000t',NULL,NULL,'2026-09-27 09:00:00','2026-10-11 09:00:00'),
  ('lead_00oy','tnt_0016','Запрос коммерческого предложения — Lola Alimov','Lola Alimov',NULL,'lola@mail.uz','+998 90 708 35 22','api','new',77,25016000,'UZS','usr_000r',NULL,NULL,'2026-08-17 09:00:00','2026-10-08 09:00:00'),
  ('lead_00oz','tnt_0016','Нужна разработка приложения — Zilola Yusupova','Zilola Yusupova',NULL,'zilola@mail.uz','+998 98 960 90 17','campaign','new',40,7390000,'UZS','usr_000v',NULL,NULL,'2026-09-27 09:00:00','2026-10-07 09:00:00'),
  ('lead_00p0','tnt_0016','Заявка с сайта — Shohruh Ahmedova','Shohruh Ahmedova',NULL,'shohruh@mail.uz','+998 86 678 48 62','telegram','new',57,46517000,'UZS','usr_000w',NULL,NULL,'2026-09-06 09:00:00','2026-10-08 09:00:00'),
  ('lead_00p1','tnt_0016','Запрос коммерческого предложения — Shohruh Pulatov','Shohruh Pulatov',NULL,'shohruh@mail.uz','+998 95 350 23 83','referral','converted',63,44810000,'UZS','usr_000w',NULL,NULL,'2026-08-24 09:00:00','2026-10-10 09:00:00'),
  ('lead_00p2','tnt_0016','Заявка с сайта — Gulnora Bekmurodov','Gulnora Bekmurodov',NULL,'gulnora@mail.uz','+998 78 734 49 79','exhibition','contacted',50,79130000,'UZS','usr_000w',NULL,NULL,'2026-09-01 09:00:00','2026-10-12 09:00:00'),
  ('lead_00p3','tnt_0016','Нужна разработка приложения — Shohruh Mirzaeva','Shohruh Mirzaeva',NULL,'shohruh@mail.uz','+998 97 500 97 63','cold_call','new',77,55748000,'UZS','usr_0013',NULL,NULL,'2026-08-28 09:00:00','2026-10-09 09:00:00'),
  ('lead_00p4','tnt_0016','Нужна разработка приложения — Dilnoza Abdullaeva','Dilnoza Abdullaeva',NULL,'dilnoza@mail.uz','+998 88 877 64 73','campaign','contacted',12,70552000,'UZS','usr_000r',NULL,NULL,'2026-08-31 09:00:00','2026-10-08 09:00:00'),
  ('lead_00p5','tnt_0016','Интерес к CRM — Jasur Ergasheva','Jasur Ergasheva',NULL,'jasur@mail.uz','+998 73 662 19 24','telegram','new',91,27018000,'UZS','usr_000t',NULL,NULL,'2026-08-18 09:00:00','2026-10-11 09:00:00'),
  ('lead_00p6','tnt_0016','Заявка с сайта — Gulnora Ergasheva','Gulnora Ergasheva',NULL,'gulnora@mail.uz','+998 71 146 14 56','website','qualified',46,6803000,'UZS','usr_000v',NULL,NULL,'2026-09-07 09:00:00','2026-10-10 09:00:00'),
  ('lead_00p7','tnt_0016','Нужна разработка приложения — Farrux Isaeva','Farrux Isaeva',NULL,'farrux@mail.uz','+998 77 738 57 87','cold_call','converted',60,76671000,'UZS','usr_000x',NULL,NULL,'2026-09-24 09:00:00','2026-10-10 09:00:00'),
  ('lead_00p8','tnt_0016','Заявка с сайта — Aziz Karimova','Aziz Karimova',NULL,'aziz@mail.uz','+998 89 919 62 94','instagram','qualified',14,7882000,'UZS','usr_000u',NULL,NULL,'2026-09-27 09:00:00','2026-10-11 09:00:00'),
  ('lead_00p9','tnt_0016','Нужна разработка приложения — Kamila Bekmurodov','Kamila Bekmurodov',NULL,'kamila@mail.uz','+998 87 462 62 31','telegram','new',68,9209000,'UZS','usr_000y',NULL,NULL,'2026-09-16 09:00:00','2026-10-11 09:00:00'),
  ('lead_00pa','tnt_0016','Заявка с сайта — Zarina Abdullaeva','Zarina Abdullaeva',NULL,'zarina@mail.uz','+998 83 390 73 92','exhibition','new',67,11570000,'UZS','usr_000s',NULL,NULL,'2026-08-31 09:00:00','2026-10-09 09:00:00'),
  ('lead_00pb','tnt_0016','Запрос коммерческого предложения — Farrux Karimov','Farrux Karimov',NULL,'farrux@mail.uz','+998 93 204 92 13','website','converted',57,48698000,'UZS','usr_000s',NULL,NULL,'2026-10-08 09:00:00','2026-10-09 09:00:00'),
  ('lead_00pc','tnt_0016','Нужна разработка приложения — Sardor Umarov','Sardor Umarov',NULL,'sardor@mail.uz','+998 73 680 26 17','telegram','converted',46,54072000,'UZS','usr_000y',NULL,NULL,'2026-08-24 09:00:00','2026-10-08 09:00:00'),
  ('lead_00pd','tnt_0016','Интерес к CRM — Iskander Bekmurodov','Iskander Bekmurodov',NULL,'iskander@mail.uz','+998 88 618 81 26','instagram','contacted',19,23806000,'UZS','usr_000t',NULL,NULL,'2026-08-24 09:00:00','2026-10-09 09:00:00'),
  ('lead_00pe','tnt_0016','Нужна разработка приложения — Gulnora Pulatov','Gulnora Pulatov',NULL,'gulnora@mail.uz','+998 81 760 15 60','instagram','converted',14,42090000,'UZS','usr_000u',NULL,NULL,'2026-08-17 09:00:00','2026-10-11 09:00:00'),
  ('lead_00pf','tnt_0016','Консультация по AI-агентам — Lola Nazarov','Lola Nazarov',NULL,'lola@mail.uz','+998 79 137 99 75','referral','contacted',24,17069000,'UZS','usr_000x',NULL,NULL,'2026-08-23 09:00:00','2026-10-07 09:00:00'),
  ('lead_00pg','tnt_0016','Запрос коммерческого предложения — Shohruh Umarov','Shohruh Umarov',NULL,'shohruh@mail.uz','+998 81 384 57 86','campaign','qualified',37,2945000,'UZS','usr_000u',NULL,NULL,'2026-10-08 09:00:00','2026-10-08 09:00:00'),
  ('lead_00ph','tnt_0016','Запрос коммерческого предложения — Nargiza Ergasheva','Nargiza Ergasheva',NULL,'nargiza@mail.uz','+998 88 374 66 30','telegram','converted',15,10934000,'UZS','usr_000x',NULL,NULL,'2026-09-15 09:00:00','2026-10-07 09:00:00'),
  ('lead_00pi','tnt_0016','Нужна разработка приложения — Sardor Toshmatov','Sardor Toshmatov',NULL,'sardor@mail.uz','+998 80 335 45 77','cold_call','disqualified',23,42357000,'UZS','usr_000t',NULL,NULL,'2026-09-08 09:00:00','2026-10-11 09:00:00'),
  ('lead_00pj','tnt_0016','Консультация по AI-агентам — Farrux Karimov','Farrux Karimov',NULL,'farrux@mail.uz','+998 85 420 44 50','telegram','new',17,49718000,'UZS','usr_000x',NULL,NULL,'2026-09-02 09:00:00','2026-10-09 09:00:00'),
  ('lead_00pk','tnt_0016','Консультация по AI-агентам — Zarina Saidova','Zarina Saidova',NULL,'zarina@mail.uz','+998 96 899 78 80','referral','qualified',92,46571000,'UZS','usr_000y',NULL,NULL,'2026-09-09 09:00:00','2026-10-11 09:00:00'),
  ('lead_00pl','tnt_0016','Запрос коммерческого предложения — Zilola Karimova','Zilola Karimova',NULL,'zilola@mail.uz','+998 85 870 98 96','instagram','qualified',46,24120000,'UZS','usr_000x',NULL,NULL,'2026-08-18 09:00:00','2026-10-07 09:00:00'),
  ('lead_00pm','tnt_0016','Консультация по AI-агентам — Sardor Toshmatov','Sardor Toshmatov',NULL,'sardor@mail.uz','+998 81 788 82 75','instagram','qualified',26,57122000,'UZS','usr_000x',NULL,NULL,'2026-08-19 09:00:00','2026-10-10 09:00:00'),
  ('lead_00pn','tnt_0016','Нужна разработка приложения — Sevara Saidova','Sevara Saidova',NULL,'sevara@mail.uz','+998 94 680 25 17','cold_call','converted',67,40365000,'UZS','usr_000x',NULL,NULL,'2026-08-23 09:00:00','2026-10-10 09:00:00'),
  ('lead_00po','tnt_0016','Заявка с сайта — Lola Mirzaeva','Lola Mirzaeva',NULL,'lola@mail.uz','+998 74 351 64 97','api','new',70,76355000,'UZS','usr_0013',NULL,NULL,'2026-09-11 09:00:00','2026-10-08 09:00:00'),
  ('lead_00pp','tnt_0016','Консультация по AI-агентам — Malika Ergasheva','Malika Ergasheva',NULL,'malika@mail.uz','+998 76 241 91 85','cold_call','converted',35,55520000,'UZS','usr_000x',NULL,NULL,'2026-09-11 09:00:00','2026-10-10 09:00:00'),
  ('lead_00pq','tnt_0016','Нужна разработка приложения — Rustam Nazarov','Rustam Nazarov',NULL,'rustam@mail.uz','+998 75 486 29 43','website','converted',61,19380000,'UZS','usr_000t',NULL,NULL,'2026-09-15 09:00:00','2026-10-09 09:00:00'),
  ('lead_00pr','tnt_0016','Нужна разработка приложения — Zilola Nazarov','Zilola Nazarov',NULL,'zilola@mail.uz','+998 84 659 98 44','campaign','new',59,63093000,'UZS','usr_000s',NULL,NULL,'2026-09-15 09:00:00','2026-10-11 09:00:00'),
  ('lead_00ps','tnt_0016','Нужна разработка приложения — Jasur Isaeva','Jasur Isaeva',NULL,'jasur@mail.uz','+998 90 457 23 57','telegram','converted',24,34969000,'UZS','usr_000s',NULL,NULL,'2026-09-25 09:00:00','2026-10-10 09:00:00'),
  ('lead_00pt','tnt_0016','Интерес к CRM — Dilnoza Isaeva','Dilnoza Isaeva',NULL,'dilnoza@mail.uz','+998 77 112 39 15','instagram','contacted',84,47181000,'UZS','usr_000x',NULL,NULL,'2026-08-18 09:00:00','2026-10-07 09:00:00'),
  ('lead_00pu','tnt_0016','Запрос коммерческого предложения — Iskander Yusupova','Iskander Yusupova',NULL,'iskander@mail.uz','+998 89 158 54 37','referral','qualified',54,72329000,'UZS','usr_000v',NULL,NULL,'2026-08-15 09:00:00','2026-10-12 09:00:00'),
  ('lead_00pv','tnt_0016','Запрос коммерческого предложения — Kamila Rahimov','Kamila Rahimov',NULL,'kamila@mail.uz','+998 87 913 25 70','instagram','disqualified',12,60181000,'UZS','usr_000w',NULL,NULL,'2026-09-08 09:00:00','2026-10-12 09:00:00'),
  ('lead_00pw','tnt_0016','Интерес к CRM — Zilola Alimov','Zilola Alimov',NULL,'zilola@mail.uz','+998 98 218 21 53','referral','qualified',95,63562000,'UZS','usr_000u',NULL,NULL,'2026-08-30 09:00:00','2026-10-07 09:00:00'),
  ('lead_00px','tnt_0016','Заявка с сайта — Zilola Isaeva','Zilola Isaeva',NULL,'zilola@mail.uz','+998 83 726 54 35','cold_call','converted',64,79010000,'UZS','usr_000w',NULL,NULL,'2026-08-24 09:00:00','2026-10-10 09:00:00'),
  ('lead_00py','tnt_0016','Нужна разработка приложения — Zarina Pulatov','Zarina Pulatov',NULL,'zarina@mail.uz','+998 82 339 51 84','telegram','new',57,57085000,'UZS','usr_000s',NULL,NULL,'2026-10-02 09:00:00','2026-10-10 09:00:00'),
  ('lead_00pz','tnt_0016','Нужна разработка приложения — Zilola Bekmurodov','Zilola Bekmurodov',NULL,'zilola@mail.uz','+998 73 812 92 43','website','contacted',65,38791000,'UZS','usr_0013',NULL,NULL,'2026-09-11 09:00:00','2026-10-09 09:00:00'),
  ('lead_00q0','tnt_0016','Запрос коммерческого предложения — Iskander Umarov','Iskander Umarov',NULL,'iskander@mail.uz','+998 94 465 55 92','website','converted',48,42531000,'UZS','usr_000w',NULL,NULL,'2026-09-15 09:00:00','2026-10-12 09:00:00'),
  ('lead_00q1','tnt_0016','Консультация по AI-агентам — Kamila Abdullaeva','Kamila Abdullaeva',NULL,'kamila@mail.uz','+998 85 640 54 53','campaign','new',97,29685000,'UZS','usr_000t',NULL,NULL,'2026-10-12 09:00:00','2026-10-09 09:00:00'),
  ('lead_00q2','tnt_0016','Консультация по AI-агентам — Malika Toshmatov','Malika Toshmatov',NULL,'malika@mail.uz','+998 88 313 53 40','api','disqualified',36,2983000,'UZS','usr_000s',NULL,NULL,'2026-09-24 09:00:00','2026-10-07 09:00:00'),
  ('lead_00q3','tnt_0016','Запрос коммерческого предложения — Lola Nazarov','Lola Nazarov',NULL,'lola@mail.uz','+998 79 747 61 13','cold_call','new',23,77481000,'UZS','usr_000r',NULL,NULL,'2026-09-29 09:00:00','2026-10-12 09:00:00'),
  ('lead_00q4','tnt_0016','Интерес к CRM — Bekzod Karimov','Bekzod Karimov',NULL,'bekzod@mail.uz','+998 92 606 55 11','website','contacted',89,71039000,'UZS','usr_000u',NULL,NULL,'2026-08-23 09:00:00','2026-10-09 09:00:00'),
  ('lead_00q5','tnt_0016','Заявка с сайта — Jasur Toshmatov','Jasur Toshmatov',NULL,'jasur@mail.uz','+998 81 920 86 23','cold_call','contacted',57,57587000,'UZS','usr_000u',NULL,NULL,'2026-08-28 09:00:00','2026-10-11 09:00:00'),
  ('lead_00q6','tnt_0016','Консультация по AI-агентам — Malika Saidova','Malika Saidova',NULL,'malika@mail.uz','+998 70 902 40 96','campaign','disqualified',43,41151000,'UZS','usr_000v',NULL,NULL,'2026-08-15 09:00:00','2026-10-09 09:00:00');
INSERT INTO leads VALUES
  ('lead_00q7','tnt_0016','Заявка с сайта — Shohruh Nazarov','Shohruh Nazarov',NULL,'shohruh@mail.uz','+998 85 225 60 72','referral','new',48,73486000,'UZS','usr_000u',NULL,NULL,'2026-09-06 09:00:00','2026-10-07 09:00:00'),
  ('lead_00q8','tnt_0016','Заявка с сайта — Zarina Saidova','Zarina Saidova',NULL,'zarina@mail.uz','+998 74 965 83 42','website','disqualified',24,51946000,'UZS','usr_000t',NULL,NULL,'2026-10-08 09:00:00','2026-10-10 09:00:00'),
  ('lead_00q9','tnt_0016','Нужна разработка приложения — Shohruh Karimova','Shohruh Karimova',NULL,'shohruh@mail.uz','+998 75 211 14 79','exhibition','disqualified',60,18764000,'UZS','usr_000u',NULL,NULL,'2026-08-25 09:00:00','2026-10-10 09:00:00'),
  ('lead_00qa','tnt_0016','Интерес к CRM — Sardor Rahimov','Sardor Rahimov',NULL,'sardor@mail.uz','+998 70 963 37 95','cold_call','new',68,16125000,'UZS','usr_000x',NULL,NULL,'2026-08-22 09:00:00','2026-10-11 09:00:00'),
  ('lead_00qb','tnt_0016','Запрос коммерческого предложения — Alisher Pulatov','Alisher Pulatov',NULL,'alisher@mail.uz','+998 97 826 84 75','website','qualified',27,33284000,'UZS','usr_000s',NULL,NULL,'2026-09-25 09:00:00','2026-10-11 09:00:00'),
  ('lead_00qc','tnt_0016','Консультация по AI-агентам — Zarina Rahimov','Zarina Rahimov',NULL,'zarina@mail.uz','+998 95 141 87 72','telegram','disqualified',15,78291000,'UZS','usr_000s',NULL,NULL,'2026-10-08 09:00:00','2026-10-12 09:00:00'),
  ('lead_00qd','tnt_0016','Запрос коммерческого предложения — Jasur Pulatov','Jasur Pulatov',NULL,'jasur@mail.uz','+998 89 209 33 32','campaign','qualified',96,25775000,'UZS','usr_000x',NULL,NULL,'2026-09-03 09:00:00','2026-10-11 09:00:00'),
  ('lead_00qe','tnt_0016','Консультация по AI-агентам — Madina Rahimov','Madina Rahimov',NULL,'madina@mail.uz','+998 85 478 21 48','telegram','disqualified',80,39065000,'UZS','usr_000y',NULL,NULL,'2026-09-17 09:00:00','2026-10-09 09:00:00'),
  ('lead_00qf','tnt_0016','Заявка с сайта — Rustam Toshmatov','Rustam Toshmatov',NULL,'rustam@mail.uz','+998 92 840 89 67','referral','disqualified',56,11521000,'UZS','usr_000y',NULL,NULL,'2026-09-07 09:00:00','2026-10-12 09:00:00'),
  ('lead_00qg','tnt_0016','Консультация по AI-агентам — Otabek Alimov','Otabek Alimov',NULL,'otabek@mail.uz','+998 88 157 99 29','cold_call','new',95,35350000,'UZS','usr_0013',NULL,NULL,'2026-08-25 09:00:00','2026-10-10 09:00:00'),
  ('lead_00qh','tnt_0016','Интерес к CRM — Bekzod Karimov','Bekzod Karimov',NULL,'bekzod@mail.uz','+998 97 564 86 68','referral','new',88,16870000,'UZS','usr_000w',NULL,NULL,'2026-09-11 09:00:00','2026-10-10 09:00:00'),
  ('lead_00qi','tnt_0016','Нужна разработка приложения — Bekzod Mirzaeva','Bekzod Mirzaeva',NULL,'bekzod@mail.uz','+998 96 844 91 94','referral','qualified',63,72582000,'UZS','usr_000u',NULL,NULL,'2026-09-01 09:00:00','2026-10-08 09:00:00'),
  ('lead_00qj','tnt_0016','Интерес к CRM — Lola Ahmedova','Lola Ahmedova',NULL,'lola@mail.uz','+998 88 649 71 62','website','contacted',31,16852000,'UZS','usr_0013',NULL,NULL,'2026-10-02 09:00:00','2026-10-08 09:00:00'),
  ('lead_00qk','tnt_0016','Заявка с сайта — Nargiza Ahmedova','Nargiza Ahmedova',NULL,'nargiza@mail.uz','+998 72 495 77 74','website','new',55,62328000,'UZS','usr_0013',NULL,NULL,'2026-10-11 09:00:00','2026-10-11 09:00:00'),
  ('lead_00ql','tnt_0017','Интерес к CRM — Zilola Saidova','Zilola Saidova',NULL,'zilola@mail.uz','+998 82 985 35 26','instagram','new',37,65893000,'UZS','usr_000r',NULL,NULL,'2026-10-12 09:00:00','2026-10-08 09:00:00'),
  ('lead_00qm','tnt_0017','Интерес к CRM — Farrux Alimov','Farrux Alimov',NULL,'farrux@mail.uz','+998 77 278 89 25','exhibition','contacted',79,59591000,'UZS','usr_000v',NULL,NULL,'2026-10-01 09:00:00','2026-10-09 09:00:00'),
  ('lead_00qn','tnt_0017','Заявка с сайта — Otabek Umarov','Otabek Umarov',NULL,'otabek@mail.uz','+998 98 573 19 63','referral','contacted',50,43189000,'UZS','usr_000y',NULL,NULL,'2026-08-31 09:00:00','2026-10-12 09:00:00'),
  ('lead_00qo','tnt_0017','Заявка с сайта — Timur Nazarov','Timur Nazarov',NULL,'timur@mail.uz','+998 80 266 21 26','telegram','converted',98,18234000,'UZS','usr_000s',NULL,NULL,'2026-09-08 09:00:00','2026-10-11 09:00:00'),
  ('lead_00qp','tnt_0017','Запрос коммерческого предложения — Sevara Rahimov','Sevara Rahimov',NULL,'sevara@mail.uz','+998 93 804 81 95','api','disqualified',63,28389000,'UZS','usr_000y',NULL,NULL,'2026-09-30 09:00:00','2026-10-11 09:00:00'),
  ('lead_00qq','tnt_0017','Консультация по AI-агентам — Nargiza Nazarov','Nargiza Nazarov',NULL,'nargiza@mail.uz','+998 79 104 48 34','cold_call','new',62,75779000,'UZS','usr_000r',NULL,NULL,'2026-09-13 09:00:00','2026-10-09 09:00:00'),
  ('lead_00qr','tnt_0017','Запрос коммерческого предложения — Alisher Ergasheva','Alisher Ergasheva',NULL,'alisher@mail.uz','+998 88 527 78 17','exhibition','qualified',46,15495000,'UZS','usr_000r',NULL,NULL,'2026-10-11 09:00:00','2026-10-10 09:00:00'),
  ('lead_00qs','tnt_0017','Запрос коммерческого предложения — Sardor Rahimov','Sardor Rahimov',NULL,'sardor@mail.uz','+998 78 391 69 10','campaign','new',12,44530000,'UZS','usr_000r',NULL,NULL,'2026-08-14 09:00:00','2026-10-12 09:00:00'),
  ('lead_00qt','tnt_0017','Заявка с сайта — Timur Saidova','Timur Saidova',NULL,'timur@mail.uz','+998 77 497 15 45','cold_call','disqualified',37,29717000,'UZS','usr_000v',NULL,NULL,'2026-09-17 09:00:00','2026-10-08 09:00:00'),
  ('lead_00qu','tnt_0017','Нужна разработка приложения — Kamila Mirzaeva','Kamila Mirzaeva',NULL,'kamila@mail.uz','+998 87 628 92 86','telegram','contacted',69,69025000,'UZS','usr_000r',NULL,NULL,'2026-08-24 09:00:00','2026-10-08 09:00:00'),
  ('lead_00qv','tnt_0017','Консультация по AI-агентам — Timur Bekmurodov','Timur Bekmurodov',NULL,'timur@mail.uz','+998 85 889 36 58','exhibition','contacted',30,30138000,'UZS','usr_000r',NULL,NULL,'2026-09-04 09:00:00','2026-10-11 09:00:00'),
  ('lead_00qw','tnt_0017','Интерес к CRM — Zarina Yusupova','Zarina Yusupova',NULL,'zarina@mail.uz','+998 73 902 70 88','referral','converted',66,75566000,'UZS','usr_000y',NULL,NULL,'2026-10-10 09:00:00','2026-10-07 09:00:00'),
  ('lead_00qx','tnt_0017','Заявка с сайта — Dilnoza Ergasheva','Dilnoza Ergasheva',NULL,'dilnoza@mail.uz','+998 80 608 86 88','website','disqualified',46,7310000,'UZS','usr_000y',NULL,NULL,'2026-09-06 09:00:00','2026-10-11 09:00:00'),
  ('lead_00qy','tnt_0017','Заявка с сайта — Jasur Toshmatov','Jasur Toshmatov',NULL,'jasur@mail.uz','+998 96 786 92 33','campaign','qualified',92,38316000,'UZS','usr_000y',NULL,NULL,'2026-08-18 09:00:00','2026-10-09 09:00:00'),
  ('lead_00qz','tnt_0017','Интерес к CRM — Kamila Karimova','Kamila Karimova',NULL,'kamila@mail.uz','+998 82 982 53 77','cold_call','new',56,45236000,'UZS','usr_0013',NULL,NULL,'2026-09-25 09:00:00','2026-10-09 09:00:00'),
  ('lead_00r0','tnt_0017','Заявка с сайта — Nilufar Karimov','Nilufar Karimov',NULL,'nilufar@mail.uz','+998 76 144 55 15','campaign','new',44,60028000,'UZS','usr_000t',NULL,NULL,'2026-10-10 09:00:00','2026-10-09 09:00:00'),
  ('lead_00r1','tnt_0017','Заявка с сайта — Farrux Nazarov','Farrux Nazarov',NULL,'farrux@mail.uz','+998 84 642 25 77','website','disqualified',49,69732000,'UZS','usr_000w',NULL,NULL,'2026-08-13 09:00:00','2026-10-11 09:00:00'),
  ('lead_00r2','tnt_0017','Нужна разработка приложения — Sardor Pulatov','Sardor Pulatov',NULL,'sardor@mail.uz','+998 85 570 15 65','cold_call','disqualified',67,9046000,'UZS','usr_000w',NULL,NULL,'2026-08-23 09:00:00','2026-10-12 09:00:00'),
  ('lead_00r3','tnt_0017','Интерес к CRM — Timur Nazarov','Timur Nazarov',NULL,'timur@mail.uz','+998 85 801 68 67','instagram','contacted',91,5887000,'UZS','usr_000v',NULL,NULL,'2026-10-12 09:00:00','2026-10-12 09:00:00'),
  ('lead_00r4','tnt_0017','Заявка с сайта — Lola Pulatov','Lola Pulatov',NULL,'lola@mail.uz','+998 70 284 71 65','exhibition','disqualified',58,76009000,'UZS','usr_000v',NULL,NULL,'2026-09-25 09:00:00','2026-10-08 09:00:00');

-- products: 20 rows
INSERT INTO products VALUES
  ('prd_00r5','tnt_0016','Enterprise CRM License','CRM-ENT','Software','Enterprise CRM License — коммерческое предложение Nexio Lab',12000000,4800000,'UZS','monthly',1,'2026-03-26 09:00:00'),
  ('prd_00r6','tnt_0016','CRM Implementation','SRV-IMP','Services','CRM Implementation — коммерческое предложение Nexio Lab',35000000,18000000,'UZS','one_time',1,'2026-03-26 09:00:00'),
  ('prd_00r7','tnt_0016','Website Development','SRV-WEB','Services','Website Development — коммерческое предложение Nexio Lab',48000000,22000000,'UZS','one_time',1,'2026-03-26 09:00:00'),
  ('prd_00r8','tnt_0016','Android Application','SRV-AND','Services','Android Application — коммерческое предложение Nexio Lab',65000000,31000000,'UZS','one_time',1,'2026-03-26 09:00:00'),
  ('prd_00r9','tnt_0016','Windows Desktop App','SRV-WIN','Services','Windows Desktop App — коммерческое предложение Nexio Lab',72000000,35000000,'UZS','one_time',1,'2026-03-26 09:00:00'),
  ('prd_00ra','tnt_0016','API Integration Pack','SRV-API','Services','API Integration Pack — коммерческое предложение Nexio Lab',15000000,6000000,'UZS','one_time',1,'2026-03-26 09:00:00'),
  ('prd_00rb','tnt_0016','AI Voice Agent Setup','AI-VOICE','AI','AI Voice Agent Setup — коммерческое предложение Nexio Lab',24000000,9000000,'UZS','one_time',1,'2026-03-26 09:00:00'),
  ('prd_00rc','tnt_0016','AI Minutes Bundle (300)','AI-MIN300','AI','AI Minutes Bundle (300) — коммерческое предложение Nexio Lab',5400000,1800000,'UZS','monthly',1,'2026-03-26 09:00:00'),
  ('prd_00rd','tnt_0016','Technical Support (monthly)','SUP-STD','Support','Technical Support (monthly) — коммерческое предложение Nexio Lab',3500000,1200000,'UZS','monthly',1,'2026-03-26 09:00:00'),
  ('prd_00re','tnt_0016','Design System Audit','SRV-DSN','Services','Design System Audit — коммерческое предложение Nexio Lab',9800000,4200000,'UZS','one_time',1,'2026-03-26 09:00:00'),
  ('prd_00rf','tnt_0017','Enterprise CRM License','CRM-ENT','Software','Enterprise CRM License — коммерческое предложение Nexio Lab',12000000,4800000,'UZS','monthly',1,'2026-03-26 09:00:00'),
  ('prd_00rg','tnt_0017','CRM Implementation','SRV-IMP','Services','CRM Implementation — коммерческое предложение Nexio Lab',35000000,18000000,'UZS','one_time',1,'2026-03-26 09:00:00'),
  ('prd_00rh','tnt_0017','Website Development','SRV-WEB','Services','Website Development — коммерческое предложение Nexio Lab',48000000,22000000,'UZS','one_time',1,'2026-03-26 09:00:00'),
  ('prd_00ri','tnt_0017','Android Application','SRV-AND','Services','Android Application — коммерческое предложение Nexio Lab',65000000,31000000,'UZS','one_time',1,'2026-03-26 09:00:00'),
  ('prd_00rj','tnt_0017','Windows Desktop App','SRV-WIN','Services','Windows Desktop App — коммерческое предложение Nexio Lab',72000000,35000000,'UZS','one_time',1,'2026-03-26 09:00:00'),
  ('prd_00rk','tnt_0017','API Integration Pack','SRV-API','Services','API Integration Pack — коммерческое предложение Nexio Lab',15000000,6000000,'UZS','one_time',1,'2026-03-26 09:00:00'),
  ('prd_00rl','tnt_0017','AI Voice Agent Setup','AI-VOICE','AI','AI Voice Agent Setup — коммерческое предложение Nexio Lab',24000000,9000000,'UZS','one_time',1,'2026-03-26 09:00:00'),
  ('prd_00rm','tnt_0017','AI Minutes Bundle (300)','AI-MIN300','AI','AI Minutes Bundle (300) — коммерческое предложение Nexio Lab',5400000,1800000,'UZS','monthly',1,'2026-03-26 09:00:00'),
  ('prd_00rn','tnt_0017','Technical Support (monthly)','SUP-STD','Support','Technical Support (monthly) — коммерческое предложение Nexio Lab',3500000,1200000,'UZS','monthly',1,'2026-03-26 09:00:00'),
  ('prd_00ro','tnt_0017','Design System Audit','SRV-DSN','Services','Design System Audit — коммерческое предложение Nexio Lab',9800000,4200000,'UZS','one_time',1,'2026-03-26 09:00:00');

-- orders: 62 rows
INSERT INTO orders VALUES
  ('ord_00rr','tnt_0016','ORD-2026-2999','org_00b9','con_00cf','deal_00ic',NULL,'draft',243000000,'UZS','ai_agent','paid',NULL,'{"terms":"Оплата 50% предоплата, 50% по факту","currency":"UZS","tax_rate":12,"captured_at":"2026-06-16 09:00:00"}',NULL,'usr_000w','2026-06-15 09:00:00','2026-10-09 09:00:00'),
  ('ord_00rt','tnt_0016','ORD-2026-3001','org_00be','con_00bz','deal_00gn',NULL,'in_progress',16200000,'UZS','manual','partial','2026-07-08 09:00:00','{"terms":"Оплата 50% предоплата, 50% по факту","currency":"UZS","tax_rate":12,"captured_at":"2026-07-08 09:00:00"}',NULL,'usr_000u','2026-07-07 09:00:00','2026-10-07 09:00:00'),
  ('ord_00rx','tnt_0016','ORD-2026-3005','org_00c0',NULL,NULL,NULL,'in_progress',221400000,'UZS','manual','partial','2026-08-07 09:00:00','{"terms":"Оплата 50% предоплата, 50% по факту","currency":"UZS","tax_rate":12,"captured_at":"2026-08-07 09:00:00"}',NULL,'usr_0013','2026-08-06 09:00:00','2026-10-10 09:00:00'),
  ('ord_00s0','tnt_0016','ORD-2026-3008','org_00e9',NULL,'deal_00h7',NULL,'in_progress',168000000,'UZS','integration','paid','2026-09-29 09:00:00','{"terms":"Оплата 50% предоплата, 50% по факту","currency":"UZS","tax_rate":12,"captured_at":"2026-09-29 09:00:00"}',NULL,'usr_000u','2026-09-28 09:00:00','2026-10-11 09:00:00'),
  ('ord_00s2','tnt_0016','ORD-2026-3010','org_00dh','con_00dq','deal_00ka',NULL,'fulfilled',15000000,'UZS','integration','partial','2026-10-03 09:00:00','{"terms":"Оплата 50% предоплата, 50% по факту","currency":"UZS","tax_rate":12,"captured_at":"2026-10-03 09:00:00"}',NULL,'usr_000r','2026-10-02 09:00:00','2026-10-10 09:00:00'),
  ('ord_00s4','tnt_0016','ORD-2026-3012','org_00e9',NULL,'deal_00fp',NULL,'confirmed',105000000,'UZS','portal','unpaid','2026-05-26 09:00:00','{"terms":"Оплата 50% предоплата, 50% по факту","currency":"UZS","tax_rate":12,"captured_at":"2026-05-26 09:00:00"}',NULL,'usr_000v','2026-05-25 09:00:00','2026-10-11 09:00:00'),
  ('ord_00s6','tnt_0016','ORD-2026-3014','org_00e9',NULL,NULL,NULL,'draft',19600000,'UZS','portal','paid',NULL,'{"terms":"Оплата 50% предоплата, 50% по факту","currency":"UZS","tax_rate":12,"captured_at":"2026-06-22 09:00:00"}',NULL,'usr_000w','2026-06-21 09:00:00','2026-10-08 09:00:00'),
  ('ord_00s8','tnt_0016','ORD-2026-3016','org_00cq',NULL,'deal_00ko',NULL,'in_progress',144000000,'UZS','portal','partial','2026-06-05 09:00:00','{"terms":"Оплата 50% предоплата, 50% по факту","currency":"UZS","tax_rate":12,"captured_at":"2026-06-05 09:00:00"}',NULL,'usr_000u','2026-06-04 09:00:00','2026-10-09 09:00:00'),
  ('ord_00sa','tnt_0016','ORD-2026-3018','org_00dk',NULL,NULL,NULL,'in_progress',7000000,'UZS','manual','partial','2026-05-12 09:00:00','{"terms":"Оплата 50% предоплата, 50% по факту","currency":"UZS","tax_rate":12,"captured_at":"2026-05-12 09:00:00"}',NULL,'usr_000u','2026-05-11 09:00:00','2026-10-08 09:00:00'),
  ('ord_00sc','tnt_0016','ORD-2026-3020','org_00dx','con_00b2',NULL,NULL,'cancelled',15000000,'UZS','integration','unpaid','2026-06-04 09:00:00','{"terms":"Оплата 50% предоплата, 50% по факту","currency":"UZS","tax_rate":12,"captured_at":"2026-06-04 09:00:00"}',NULL,'usr_000x','2026-06-03 09:00:00','2026-10-12 09:00:00'),
  ('ord_00sf','tnt_0016','ORD-2026-3023','org_00e9','con_00bi',NULL,NULL,'fulfilled',88200000,'UZS','portal','partial','2026-09-30 09:00:00','{"terms":"Оплата 50% предоплата, 50% по факту","currency":"UZS","tax_rate":12,"captured_at":"2026-09-30 09:00:00"}',NULL,'usr_000s','2026-09-29 09:00:00','2026-10-07 09:00:00'),
  ('ord_00sj','tnt_0016','ORD-2026-3027','org_00e2','con_00c9',NULL,NULL,'in_progress',291000000,'UZS','manual','unpaid','2026-09-25 09:00:00','{"terms":"Оплата 50% предоплата, 50% по факту","currency":"UZS","tax_rate":12,"captured_at":"2026-09-25 09:00:00"}',NULL,'usr_000u','2026-09-24 09:00:00','2026-10-10 09:00:00'),
  ('ord_00sm','tnt_0016','ORD-2026-3030','org_00e6','con_00av',NULL,NULL,'confirmed',360000000,'UZS','ai_agent','paid','2026-10-10 09:00:00','{"terms":"Оплата 50% предоплата, 50% по факту","currency":"UZS","tax_rate":12,"captured_at":"2026-10-10 09:00:00"}',NULL,'usr_000x','2026-10-09 09:00:00','2026-10-09 09:00:00'),
  ('ord_00sp','tnt_0016','ORD-2026-3033','org_00be',NULL,'deal_00k6',NULL,'in_progress',60000000,'UZS','portal','unpaid','2026-09-12 09:00:00','{"terms":"Оплата 50% предоплата, 50% по факту","currency":"UZS","tax_rate":12,"captured_at":"2026-09-12 09:00:00"}',NULL,'usr_000t','2026-09-11 09:00:00','2026-10-08 09:00:00'),
  ('ord_00sr','tnt_0016','ORD-2026-3035','org_00ax','con_00b7','deal_00i1',NULL,'fulfilled',65000000,'UZS','portal','paid','2026-10-05 09:00:00','{"terms":"Оплата 50% предоплата, 50% по факту","currency":"UZS","tax_rate":12,"captured_at":"2026-10-05 09:00:00"}',NULL,'usr_000w','2026-10-04 09:00:00','2026-10-08 09:00:00'),
  ('ord_00su','tnt_0016','ORD-2026-3038','org_00bx','con_00bo',NULL,NULL,'draft',19700000,'UZS','ai_agent','unpaid',NULL,'{"terms":"Оплата 50% предоплата, 50% по факту","currency":"UZS","tax_rate":12,"captured_at":"2026-09-15 09:00:00"}',NULL,'usr_000x','2026-09-14 09:00:00','2026-10-08 09:00:00'),
  ('ord_00sx','tnt_0016','ORD-2026-3041','org_00cu',NULL,NULL,NULL,'in_progress',252000000,'UZS','manual','partial','2026-05-21 09:00:00','{"terms":"Оплата 50% предоплата, 50% по факту","currency":"UZS","tax_rate":12,"captured_at":"2026-05-21 09:00:00"}',NULL,'usr_000r','2026-05-20 09:00:00','2026-10-08 09:00:00'),
  ('ord_00t0','tnt_0016','ORD-2026-3044','org_00e6','con_00ef',NULL,NULL,'draft',48500000,'UZS','manual','paid',NULL,'{"terms":"Оплата 50% предоплата, 50% по факту","currency":"UZS","tax_rate":12,"captured_at":"2026-06-04 09:00:00"}',NULL,'usr_000t','2026-06-03 09:00:00','2026-10-07 09:00:00'),
  ('ord_00t2','tnt_0016','ORD-2026-3046','org_00ei','con_00bt','deal_00ly',NULL,'in_progress',216000000,'UZS','manual','unpaid','2026-06-27 09:00:00','{"terms":"Оплата 50% предоплата, 50% по факту","currency":"UZS","tax_rate":12,"captured_at":"2026-06-27 09:00:00"}',NULL,'usr_000x','2026-06-26 09:00:00','2026-10-07 09:00:00'),
  ('ord_00t4','tnt_0016','ORD-2026-3048','org_00ax',NULL,NULL,NULL,'in_progress',30000000,'UZS','portal','partial','2026-05-17 09:00:00','{"terms":"Оплата 50% предоплата, 50% по факту","currency":"UZS","tax_rate":12,"captured_at":"2026-05-17 09:00:00"}',NULL,'usr_000r','2026-05-16 09:00:00','2026-10-11 09:00:00'),
  ('ord_00t8','tnt_0016','ORD-2026-3052','org_00bu','con_00by',NULL,NULL,'fulfilled',295000000,'UZS','integration','paid','2026-05-29 09:00:00','{"terms":"Оплата 50% предоплата, 50% по факту","currency":"UZS","tax_rate":12,"captured_at":"2026-05-29 09:00:00"}',NULL,'usr_000s','2026-05-28 09:00:00','2026-10-08 09:00:00'),
  ('ord_00tc','tnt_0016','ORD-2026-3056','org_00dh','con_00dm','deal_00gp',NULL,'in_progress',198800000,'UZS','ai_agent','paid','2026-10-11 09:00:00','{"terms":"Оплата 50% предоплата, 50% по факту","currency":"UZS","tax_rate":12,"captured_at":"2026-10-11 09:00:00"}',NULL,'usr_000v','2026-10-10 09:00:00','2026-10-07 09:00:00'),
  ('ord_00te','tnt_0016','ORD-2026-3058','org_00ed','con_00dn',NULL,NULL,'cancelled',35000000,'UZS','portal','unpaid','2026-06-03 09:00:00','{"terms":"Оплата 50% предоплата, 50% по факту","currency":"UZS","tax_rate":12,"captured_at":"2026-06-03 09:00:00"}',NULL,'usr_000y','2026-06-02 09:00:00','2026-10-07 09:00:00'),
  ('ord_00th','tnt_0016','ORD-2026-3061','org_00cy',NULL,'deal_00mj',NULL,'confirmed',159000000,'UZS','integration','paid','2026-06-05 09:00:00','{"terms":"Оплата 50% предоплата, 50% по факту","currency":"UZS","tax_rate":12,"captured_at":"2026-06-05 09:00:00"}',NULL,'usr_0013','2026-06-04 09:00:00','2026-10-12 09:00:00'),
  ('ord_00tj','tnt_0016','ORD-2026-3063','org_00cb','con_00e5','deal_00ja',NULL,'confirmed',35000000,'UZS','portal','partial','2026-05-18 09:00:00','{"terms":"Оплата 50% предоплата, 50% по факту","currency":"UZS","tax_rate":12,"captured_at":"2026-05-18 09:00:00"}',NULL,'usr_000y','2026-05-17 09:00:00','2026-10-10 09:00:00'),
  ('ord_00tm','tnt_0016','ORD-2026-3066','org_00cy',NULL,NULL,NULL,'in_progress',135400000,'UZS','ai_agent','paid','2026-07-24 09:00:00','{"terms":"Оплата 50% предоплата, 50% по факту","currency":"UZS","tax_rate":12,"captured_at":"2026-07-24 09:00:00"}',NULL,'usr_000s','2026-07-23 09:00:00','2026-10-09 09:00:00'),
  ('ord_00to','tnt_0016','ORD-2026-3068','org_00cb','con_00d9','deal_00ly',NULL,'draft',48000000,'UZS','integration','unpaid',NULL,'{"terms":"Оплата 50% предоплата, 50% по факту","currency":"UZS","tax_rate":12,"captured_at":"2026-07-02 09:00:00"}',NULL,'usr_000w','2026-07-01 09:00:00','2026-10-08 09:00:00'),
  ('ord_00tq','tnt_0016','ORD-2026-3070','org_00cy','con_00d0',NULL,NULL,'draft',216000000,'UZS','manual','unpaid',NULL,'{"terms":"Оплата 50% предоплата, 50% по факту","currency":"UZS","tax_rate":12,"captured_at":"2026-05-10 09:00:00"}',NULL,'usr_000v','2026-05-09 09:00:00','2026-10-09 09:00:00'),
  ('ord_00tt','tnt_0016','ORD-2026-3073','org_00bh','con_00ba',NULL,NULL,'draft',96000000,'UZS','manual','paid',NULL,'{"terms":"Оплата 50% предоплата, 50% по факту","currency":"UZS","tax_rate":12,"captured_at":"2026-05-07 09:00:00"}',NULL,'usr_000t','2026-05-06 09:00:00','2026-10-10 09:00:00'),
  ('ord_00tw','tnt_0016','ORD-2026-3076','org_00dk',NULL,'deal_00hm',NULL,'in_progress',198500000,'UZS','portal','partial','2026-07-07 09:00:00','{"terms":"Оплата 50% предоплата, 50% по факту","currency":"UZS","tax_rate":12,"captured_at":"2026-07-07 09:00:00"}',NULL,'usr_000r','2026-07-06 09:00:00','2026-10-11 09:00:00'),
  ('ord_00ty','tnt_0016','ORD-2026-3078','org_00ed','con_00c1',NULL,NULL,'in_progress',12000000,'UZS','ai_agent','paid','2026-10-06 09:00:00','{"terms":"Оплата 50% предоплата, 50% по факту","currency":"UZS","tax_rate":12,"captured_at":"2026-10-06 09:00:00"}',NULL,'usr_0013','2026-10-05 09:00:00','2026-10-11 09:00:00'),
  ('ord_00u2','tnt_0016','ORD-2026-3082','org_00c6',NULL,NULL,NULL,'fulfilled',175000000,'UZS','integration','paid','2026-05-21 09:00:00','{"terms":"Оплата 50% предоплата, 50% по факту","currency":"UZS","tax_rate":12,"captured_at":"2026-05-21 09:00:00"}',NULL,'usr_0013','2026-05-20 09:00:00','2026-10-08 09:00:00'),
  ('ord_00u6','tnt_0016','ORD-2026-3086','org_00cn',NULL,'deal_00i5',NULL,'fulfilled',223000000,'UZS','manual','paid','2026-08-24 09:00:00','{"terms":"Оплата 50% предоплата, 50% по факту","currency":"UZS","tax_rate":12,"captured_at":"2026-08-24 09:00:00"}',NULL,'usr_000r','2026-08-23 09:00:00','2026-10-12 09:00:00'),
  ('ord_00u8','tnt_0016','ORD-2026-3088','org_00au',NULL,NULL,NULL,'confirmed',29400000,'UZS','portal','unpaid','2026-07-26 09:00:00','{"terms":"Оплата 50% предоплата, 50% по факту","currency":"UZS","tax_rate":12,"captured_at":"2026-07-26 09:00:00"}',NULL,'usr_000s','2026-07-25 09:00:00','2026-10-10 09:00:00'),
  ('ord_00ua','tnt_0016','ORD-2026-3090','org_00b9',NULL,'deal_00iy',NULL,'fulfilled',45000000,'UZS','ai_agent','paid','2026-08-16 09:00:00','{"terms":"Оплата 50% предоплата, 50% по факту","currency":"UZS","tax_rate":12,"captured_at":"2026-08-16 09:00:00"}',NULL,'usr_0013','2026-08-15 09:00:00','2026-10-09 09:00:00'),
  ('ord_00ud','tnt_0016','ORD-2026-3093','org_00c0','con_00bo',NULL,NULL,'cancelled',19000000,'UZS','portal','unpaid','2026-08-26 09:00:00','{"terms":"Оплата 50% предоплата, 50% по факту","currency":"UZS","tax_rate":12,"captured_at":"2026-08-26 09:00:00"}',NULL,'usr_000v','2026-08-25 09:00:00','2026-10-09 09:00:00'),
  ('ord_00ug','tnt_0016','ORD-2026-3096','org_00ce','con_00ds','deal_00j6',NULL,'confirmed',267000000,'UZS','portal','unpaid','2026-10-11 09:00:00','{"terms":"Оплата 50% предоплата, 50% по факту","currency":"UZS","tax_rate":12,"captured_at":"2026-10-11 09:00:00"}',NULL,'usr_000v','2026-10-10 09:00:00','2026-10-11 09:00:00'),
  ('ord_00uj','tnt_0016','ORD-2026-3099','org_00bx',NULL,NULL,NULL,'cancelled',38500000,'UZS','integration','unpaid','2026-08-25 09:00:00','{"terms":"Оплата 50% предоплата, 50% по факту","currency":"UZS","tax_rate":12,"captured_at":"2026-08-25 09:00:00"}',NULL,'usr_000r','2026-08-24 09:00:00','2026-10-09 09:00:00'),
  ('ord_00ul','tnt_0016','ORD-2026-3101','org_00dx',NULL,NULL,NULL,'fulfilled',10800000,'UZS','integration','paid','2026-07-13 09:00:00','{"terms":"Оплата 50% предоплата, 50% по факту","currency":"UZS","tax_rate":12,"captured_at":"2026-07-13 09:00:00"}',NULL,'usr_000t','2026-07-12 09:00:00','2026-10-09 09:00:00'),
  ('ord_00uo','tnt_0016','ORD-2026-3104','org_00au',NULL,NULL,NULL,'fulfilled',54600000,'UZS','ai_agent','partial','2026-08-09 09:00:00','{"terms":"Оплата 50% предоплата, 50% по факту","currency":"UZS","tax_rate":12,"captured_at":"2026-08-09 09:00:00"}',NULL,'usr_000y','2026-08-08 09:00:00','2026-10-09 09:00:00'),
  ('ord_00uq','tnt_0016','ORD-2026-3106','org_00e2','con_00dz','deal_00m6',NULL,'confirmed',10500000,'UZS','manual','partial','2026-05-10 09:00:00','{"terms":"Оплата 50% предоплата, 50% по факту","currency":"UZS","tax_rate":12,"captured_at":"2026-05-10 09:00:00"}',NULL,'usr_000w','2026-05-09 09:00:00','2026-10-11 09:00:00'),
  ('ord_00uu','tnt_0016','ORD-2026-3110','org_00dx',NULL,'deal_00im',NULL,'confirmed',318000000,'UZS','manual','unpaid','2026-05-17 09:00:00','{"terms":"Оплата 50% предоплата, 50% по факту","currency":"UZS","tax_rate":12,"captured_at":"2026-05-17 09:00:00"}',NULL,'usr_000r','2026-05-16 09:00:00','2026-10-12 09:00:00'),
  ('ord_00uw','tnt_0016','ORD-2026-3112','org_00ax',NULL,NULL,NULL,'fulfilled',48000000,'UZS','integration','paid','2026-05-11 09:00:00','{"terms":"Оплата 50% предоплата, 50% по факту","currency":"UZS","tax_rate":12,"captured_at":"2026-05-11 09:00:00"}',NULL,'usr_000t','2026-05-10 09:00:00','2026-10-10 09:00:00'),
  ('ord_00uz','tnt_0016','ORD-2026-3115','org_00au','con_00dm',NULL,NULL,'in_progress',110400000,'UZS','integration','paid','2026-08-30 09:00:00','{"terms":"Оплата 50% предоплата, 50% по факту","currency":"UZS","tax_rate":12,"captured_at":"2026-08-30 09:00:00"}',NULL,'usr_000v','2026-08-29 09:00:00','2026-10-10 09:00:00'),
  ('ord_00v3','tnt_0016','ORD-2026-3119','org_00bu','con_00dy',NULL,NULL,'confirmed',173000000,'UZS','portal','partial','2026-09-15 09:00:00','{"terms":"Оплата 50% предоплата, 50% по факту","currency":"UZS","tax_rate":12,"captured_at":"2026-09-15 09:00:00"}',NULL,'usr_000s','2026-09-14 09:00:00','2026-10-10 09:00:00'),
  ('ord_00v6','tnt_0016','ORD-2026-3122','org_00e6',NULL,NULL,NULL,'cancelled',264000000,'UZS','portal','unpaid','2026-09-18 09:00:00','{"terms":"Оплата 50% предоплата, 50% по факту","currency":"UZS","tax_rate":12,"captured_at":"2026-09-18 09:00:00"}',NULL,'usr_000x','2026-09-17 09:00:00','2026-10-08 09:00:00'),
  ('ord_00v8','tnt_0017','ORD-2026-3124','org_00et','con_00f4',NULL,NULL,'fulfilled',36000000,'UZS','ai_agent','paid','2026-09-27 09:00:00','{"terms":"Оплата 50% предоплата, 50% по факту","currency":"UZS","tax_rate":12,"captured_at":"2026-09-27 09:00:00"}',NULL,'usr_0013','2026-09-26 09:00:00','2026-10-09 09:00:00'),
  ('ord_00vb','tnt_0017','ORD-2026-3127','org_00et','con_00ez',NULL,NULL,'fulfilled',137000000,'UZS','portal','paid','2026-06-21 09:00:00','{"terms":"Оплата 50% предоплата, 50% по факту","currency":"UZS","tax_rate":12,"captured_at":"2026-06-21 09:00:00"}',NULL,'usr_000u','2026-06-20 09:00:00','2026-10-10 09:00:00'),
  ('ord_00ve','tnt_0017','ORD-2026-3130','org_00f5','con_00ez',NULL,NULL,'fulfilled',75000000,'UZS','ai_agent','paid','2026-06-06 09:00:00','{"terms":"Оплата 50% предоплата, 50% по факту","currency":"UZS","tax_rate":12,"captured_at":"2026-06-06 09:00:00"}',NULL,'usr_000r','2026-06-05 09:00:00','2026-10-10 09:00:00'),
  ('ord_00vg','tnt_0017','ORD-2026-3132','org_00en',NULL,NULL,NULL,'fulfilled',30000000,'UZS','integration','partial','2026-07-12 09:00:00','{"terms":"Оплата 50% предоплата, 50% по факту","currency":"UZS","tax_rate":12,"captured_at":"2026-07-12 09:00:00"}',NULL,'usr_000v','2026-07-11 09:00:00','2026-10-11 09:00:00');
INSERT INTO orders VALUES
  ('ord_00vj','tnt_0017','ORD-2026-3135','org_00ff','con_00f6',NULL,NULL,'cancelled',21600000,'UZS','portal','unpaid','2026-05-16 09:00:00','{"terms":"Оплата 50% предоплата, 50% по факту","currency":"UZS","tax_rate":12,"captured_at":"2026-05-16 09:00:00"}',NULL,'usr_000y','2026-05-15 09:00:00','2026-10-12 09:00:00'),
  ('ord_00vm','tnt_0017','ORD-2026-3138','org_00f0','con_00ew',NULL,NULL,'in_progress',54800000,'UZS','ai_agent','paid','2026-06-13 09:00:00','{"terms":"Оплата 50% предоплата, 50% по факту","currency":"UZS","tax_rate":12,"captured_at":"2026-06-13 09:00:00"}',NULL,'usr_0013','2026-06-12 09:00:00','2026-10-09 09:00:00'),
  ('ord_00vp','tnt_0017','ORD-2026-3141','org_00f2','con_00ez','deal_00od',NULL,'fulfilled',108000000,'UZS','manual','partial','2026-05-31 09:00:00','{"terms":"Оплата 50% предоплата, 50% по факту","currency":"UZS","tax_rate":12,"captured_at":"2026-05-31 09:00:00"}',NULL,'usr_000t','2026-05-30 09:00:00','2026-10-08 09:00:00'),
  ('ord_00vs','tnt_0017','ORD-2026-3144','org_00ff',NULL,NULL,NULL,'in_progress',151000000,'UZS','ai_agent','paid','2026-07-03 09:00:00','{"terms":"Оплата 50% предоплата, 50% по факту","currency":"UZS","tax_rate":12,"captured_at":"2026-07-03 09:00:00"}',NULL,'usr_000s','2026-07-02 09:00:00','2026-10-11 09:00:00'),
  ('ord_00vv','tnt_0017','ORD-2026-3147','org_00en','con_00fd',NULL,NULL,'in_progress',34800000,'UZS','integration','partial','2026-09-03 09:00:00','{"terms":"Оплата 50% предоплата, 50% по факту","currency":"UZS","tax_rate":12,"captured_at":"2026-09-03 09:00:00"}',NULL,'usr_000t','2026-09-02 09:00:00','2026-10-10 09:00:00'),
  ('ord_00vy','tnt_0017','ORD-2026-3150','org_00ey','con_00f8','deal_00o0',NULL,'draft',108500000,'UZS','integration','unpaid',NULL,'{"terms":"Оплата 50% предоплата, 50% по факту","currency":"UZS","tax_rate":12,"captured_at":"2026-08-19 09:00:00"}',NULL,'usr_000v','2026-08-18 09:00:00','2026-10-12 09:00:00'),
  ('ord_00w1','tnt_0017','ORD-2026-3153','org_00f2','con_00fg',NULL,NULL,'in_progress',120000000,'UZS','manual','partial','2026-09-16 09:00:00','{"terms":"Оплата 50% предоплата, 50% по факту","currency":"UZS","tax_rate":12,"captured_at":"2026-09-16 09:00:00"}',NULL,'usr_000s','2026-09-15 09:00:00','2026-10-07 09:00:00'),
  ('ord_00w4','tnt_0017','ORD-2026-3156','org_00ey','con_00fe','deal_00nh',NULL,'fulfilled',168000000,'UZS','ai_agent','paid','2026-07-22 09:00:00','{"terms":"Оплата 50% предоплата, 50% по факту","currency":"UZS","tax_rate":12,"captured_at":"2026-07-22 09:00:00"}',NULL,'usr_000y','2026-07-21 09:00:00','2026-10-07 09:00:00'),
  ('ord_00w8','tnt_0017','ORD-2026-3160','org_00et','con_00es','deal_00o8',NULL,'fulfilled',67000000,'UZS','integration','paid','2026-07-10 09:00:00','{"terms":"Оплата 50% предоплата, 50% по факту","currency":"UZS","tax_rate":12,"captured_at":"2026-07-10 09:00:00"}',NULL,'usr_000y','2026-07-09 09:00:00','2026-10-09 09:00:00'),
  ('ord_00wb','tnt_0017','ORD-2026-3163','org_00ey','con_00er','deal_00nr',NULL,'fulfilled',216000000,'UZS','manual','paid','2026-07-02 09:00:00','{"terms":"Оплата 50% предоплата, 50% по факту","currency":"UZS","tax_rate":12,"captured_at":"2026-07-02 09:00:00"}',NULL,'usr_000y','2026-07-01 09:00:00','2026-10-10 09:00:00'),
  ('ord_00wf','tnt_0017','ORD-2026-3167','org_00ff',NULL,NULL,NULL,'cancelled',228000000,'UZS','portal','unpaid','2026-05-16 09:00:00','{"terms":"Оплата 50% предоплата, 50% по факту","currency":"UZS","tax_rate":12,"captured_at":"2026-05-16 09:00:00"}',NULL,'usr_000y','2026-05-15 09:00:00','2026-10-11 09:00:00'),
  ('ord_00wi','tnt_0017','ORD-2026-3170','org_00fc','con_00f6',NULL,NULL,'in_progress',129000000,'UZS','integration','partial','2026-09-19 09:00:00','{"terms":"Оплата 50% предоплата, 50% по факту","currency":"UZS","tax_rate":12,"captured_at":"2026-09-19 09:00:00"}',NULL,'usr_000s','2026-09-18 09:00:00','2026-10-11 09:00:00');

-- order_items: 112 rows
INSERT INTO order_items VALUES
  ('oit_00rp','tnt_0016','ord_00rr','prd_00r7','Website Development',1,48000000,48000000),
  ('oit_00rq','tnt_0016','ord_00rr','prd_00r8','Android Application',3,65000000,195000000),
  ('oit_00rs','tnt_0016','ord_00rt','prd_00rc','AI Minutes Bundle (300)',3,5400000,16200000),
  ('oit_00ru','tnt_0016','ord_00rx','prd_00re','Design System Audit',3,9800000,29400000),
  ('oit_00rv','tnt_0016','ord_00rx','prd_00r7','Website Development',1,48000000,48000000),
  ('oit_00rw','tnt_0016','ord_00rx','prd_00r7','Website Development',3,48000000,144000000),
  ('oit_00ry','tnt_0016','ord_00s0','prd_00r7','Website Development',3,48000000,144000000),
  ('oit_00rz','tnt_0016','ord_00s0','prd_00rb','AI Voice Agent Setup',1,24000000,24000000),
  ('oit_00s1','tnt_0016','ord_00s2','prd_00ra','API Integration Pack',1,15000000,15000000),
  ('oit_00s3','tnt_0016','ord_00s4','prd_00r6','CRM Implementation',3,35000000,105000000),
  ('oit_00s5','tnt_0016','ord_00s6','prd_00re','Design System Audit',2,9800000,19600000),
  ('oit_00s7','tnt_0016','ord_00s8','prd_00r7','Website Development',3,48000000,144000000),
  ('oit_00s9','tnt_0016','ord_00sa','prd_00rd','Technical Support (monthly)',2,3500000,7000000),
  ('oit_00sb','tnt_0016','ord_00sc','prd_00ra','API Integration Pack',1,15000000,15000000),
  ('oit_00sd','tnt_0016','ord_00sf','prd_00r9','Windows Desktop App',1,72000000,72000000),
  ('oit_00se','tnt_0016','ord_00sf','prd_00rc','AI Minutes Bundle (300)',3,5400000,16200000),
  ('oit_00sg','tnt_0016','ord_00sj','prd_00r9','Windows Desktop App',1,72000000,72000000),
  ('oit_00sh','tnt_0016','ord_00sj','prd_00r8','Android Application',3,65000000,195000000),
  ('oit_00si','tnt_0016','ord_00sj','prd_00r5','Enterprise CRM License',2,12000000,24000000),
  ('oit_00sk','tnt_0016','ord_00sm','prd_00r9','Windows Desktop App',3,72000000,216000000),
  ('oit_00sl','tnt_0016','ord_00sm','prd_00r9','Windows Desktop App',2,72000000,144000000),
  ('oit_00sn','tnt_0016','ord_00sp','prd_00ra','API Integration Pack',1,15000000,15000000),
  ('oit_00so','tnt_0016','ord_00sp','prd_00ra','API Integration Pack',3,15000000,45000000),
  ('oit_00sq','tnt_0016','ord_00sr','prd_00r8','Android Application',1,65000000,65000000),
  ('oit_00ss','tnt_0016','ord_00su','prd_00rd','Technical Support (monthly)',1,3500000,3500000),
  ('oit_00st','tnt_0016','ord_00su','prd_00rc','AI Minutes Bundle (300)',3,5400000,16200000),
  ('oit_00sv','tnt_0016','ord_00sx','prd_00r9','Windows Desktop App',3,72000000,216000000),
  ('oit_00sw','tnt_0016','ord_00sx','prd_00r5','Enterprise CRM License',3,12000000,36000000),
  ('oit_00sy','tnt_0016','ord_00t0','prd_00rd','Technical Support (monthly)',1,3500000,3500000),
  ('oit_00sz','tnt_0016','ord_00t0','prd_00ra','API Integration Pack',3,15000000,45000000),
  ('oit_00t1','tnt_0016','ord_00t2','prd_00r9','Windows Desktop App',3,72000000,216000000),
  ('oit_00t3','tnt_0016','ord_00t4','prd_00ra','API Integration Pack',2,15000000,30000000),
  ('oit_00t5','tnt_0016','ord_00t8','prd_00r8','Android Application',3,65000000,195000000),
  ('oit_00t6','tnt_0016','ord_00t8','prd_00r8','Android Application',1,65000000,65000000),
  ('oit_00t7','tnt_0016','ord_00t8','prd_00r6','CRM Implementation',1,35000000,35000000),
  ('oit_00t9','tnt_0016','ord_00tc','prd_00re','Design System Audit',1,9800000,9800000),
  ('oit_00ta','tnt_0016','ord_00tc','prd_00r7','Website Development',3,48000000,144000000),
  ('oit_00tb','tnt_0016','ord_00tc','prd_00ra','API Integration Pack',3,15000000,45000000),
  ('oit_00td','tnt_0016','ord_00te','prd_00r6','CRM Implementation',1,35000000,35000000),
  ('oit_00tf','tnt_0016','ord_00th','prd_00r9','Windows Desktop App',2,72000000,144000000),
  ('oit_00tg','tnt_0016','ord_00th','prd_00ra','API Integration Pack',1,15000000,15000000),
  ('oit_00ti','tnt_0016','ord_00tj','prd_00r6','CRM Implementation',1,35000000,35000000),
  ('oit_00tk','tnt_0016','ord_00tm','prd_00r8','Android Application',2,65000000,130000000),
  ('oit_00tl','tnt_0016','ord_00tm','prd_00rc','AI Minutes Bundle (300)',1,5400000,5400000),
  ('oit_00tn','tnt_0016','ord_00to','prd_00r7','Website Development',1,48000000,48000000),
  ('oit_00tp','tnt_0016','ord_00tq','prd_00r9','Windows Desktop App',3,72000000,216000000),
  ('oit_00tr','tnt_0016','ord_00tt','prd_00r7','Website Development',1,48000000,48000000),
  ('oit_00ts','tnt_0016','ord_00tt','prd_00r7','Website Development',1,48000000,48000000),
  ('oit_00tu','tnt_0016','ord_00tw','prd_00rd','Technical Support (monthly)',1,3500000,3500000),
  ('oit_00tv','tnt_0016','ord_00tw','prd_00r8','Android Application',3,65000000,195000000);
INSERT INTO order_items VALUES
  ('oit_00tx','tnt_0016','ord_00ty','prd_00r5','Enterprise CRM License',1,12000000,12000000),
  ('oit_00tz','tnt_0016','ord_00u2','prd_00rd','Technical Support (monthly)',2,3500000,7000000),
  ('oit_00u0','tnt_0016','ord_00u2','prd_00rb','AI Voice Agent Setup',3,24000000,72000000),
  ('oit_00u1','tnt_0016','ord_00u2','prd_00r7','Website Development',2,48000000,96000000),
  ('oit_00u3','tnt_0016','ord_00u6','prd_00r9','Windows Desktop App',2,72000000,144000000),
  ('oit_00u4','tnt_0016','ord_00u6','prd_00r9','Windows Desktop App',1,72000000,72000000),
  ('oit_00u5','tnt_0016','ord_00u6','prd_00rd','Technical Support (monthly)',2,3500000,7000000),
  ('oit_00u7','tnt_0016','ord_00u8','prd_00re','Design System Audit',3,9800000,29400000),
  ('oit_00u9','tnt_0016','ord_00ua','prd_00ra','API Integration Pack',3,15000000,45000000),
  ('oit_00ub','tnt_0016','ord_00ud','prd_00rd','Technical Support (monthly)',2,3500000,7000000),
  ('oit_00uc','tnt_0016','ord_00ud','prd_00r5','Enterprise CRM License',1,12000000,12000000),
  ('oit_00ue','tnt_0016','ord_00ug','prd_00rb','AI Voice Agent Setup',3,24000000,72000000),
  ('oit_00uf','tnt_0016','ord_00ug','prd_00r8','Android Application',3,65000000,195000000),
  ('oit_00uh','tnt_0016','ord_00uj','prd_00rd','Technical Support (monthly)',1,3500000,3500000),
  ('oit_00ui','tnt_0016','ord_00uj','prd_00r6','CRM Implementation',1,35000000,35000000),
  ('oit_00uk','tnt_0016','ord_00ul','prd_00rc','AI Minutes Bundle (300)',2,5400000,10800000),
  ('oit_00um','tnt_0016','ord_00uo','prd_00re','Design System Audit',2,9800000,19600000),
  ('oit_00un','tnt_0016','ord_00uo','prd_00r6','CRM Implementation',1,35000000,35000000),
  ('oit_00up','tnt_0016','ord_00uq','prd_00rd','Technical Support (monthly)',3,3500000,10500000),
  ('oit_00ur','tnt_0016','ord_00uu','prd_00ra','API Integration Pack',2,15000000,30000000),
  ('oit_00us','tnt_0016','ord_00uu','prd_00r9','Windows Desktop App',2,72000000,144000000),
  ('oit_00ut','tnt_0016','ord_00uu','prd_00r7','Website Development',3,48000000,144000000),
  ('oit_00uv','tnt_0016','ord_00uw','prd_00rb','AI Voice Agent Setup',2,24000000,48000000),
  ('oit_00ux','tnt_0016','ord_00uz','prd_00rc','AI Minutes Bundle (300)',1,5400000,5400000),
  ('oit_00uy','tnt_0016','ord_00uz','prd_00r6','CRM Implementation',3,35000000,105000000),
  ('oit_00v0','tnt_0016','ord_00v3','prd_00r5','Enterprise CRM License',3,12000000,36000000),
  ('oit_00v1','tnt_0016','ord_00v3','prd_00r9','Windows Desktop App',1,72000000,72000000),
  ('oit_00v2','tnt_0016','ord_00v3','prd_00r8','Android Application',1,65000000,65000000),
  ('oit_00v4','tnt_0016','ord_00v6','prd_00r9','Windows Desktop App',3,72000000,216000000),
  ('oit_00v5','tnt_0016','ord_00v6','prd_00rb','AI Voice Agent Setup',2,24000000,48000000),
  ('oit_00v7','tnt_0017','ord_00v8','prd_00rf','Enterprise CRM License',3,12000000,36000000),
  ('oit_00v9','tnt_0017','ord_00vb','prd_00ri','Android Application',2,65000000,130000000),
  ('oit_00va','tnt_0017','ord_00vb','prd_00rn','Technical Support (monthly)',2,3500000,7000000),
  ('oit_00vc','tnt_0017','ord_00ve','prd_00rk','API Integration Pack',2,15000000,30000000),
  ('oit_00vd','tnt_0017','ord_00ve','prd_00rk','API Integration Pack',3,15000000,45000000),
  ('oit_00vf','tnt_0017','ord_00vg','prd_00rk','API Integration Pack',2,15000000,30000000),
  ('oit_00vh','tnt_0017','ord_00vj','prd_00rm','AI Minutes Bundle (300)',2,5400000,10800000),
  ('oit_00vi','tnt_0017','ord_00vj','prd_00rm','AI Minutes Bundle (300)',2,5400000,10800000),
  ('oit_00vk','tnt_0017','ord_00vm','prd_00ro','Design System Audit',1,9800000,9800000),
  ('oit_00vl','tnt_0017','ord_00vm','prd_00rk','API Integration Pack',3,15000000,45000000),
  ('oit_00vn','tnt_0017','ord_00vp','prd_00rh','Website Development',2,48000000,96000000),
  ('oit_00vo','tnt_0017','ord_00vp','prd_00rf','Enterprise CRM License',1,12000000,12000000),
  ('oit_00vq','tnt_0017','ord_00vs','prd_00rh','Website Development',3,48000000,144000000),
  ('oit_00vr','tnt_0017','ord_00vs','prd_00rn','Technical Support (monthly)',2,3500000,7000000),
  ('oit_00vt','tnt_0017','ord_00vv','prd_00ro','Design System Audit',3,9800000,29400000),
  ('oit_00vu','tnt_0017','ord_00vv','prd_00rm','AI Minutes Bundle (300)',1,5400000,5400000),
  ('oit_00vw','tnt_0017','ord_00vy','prd_00rn','Technical Support (monthly)',1,3500000,3500000),
  ('oit_00vx','tnt_0017','ord_00vy','prd_00rg','CRM Implementation',3,35000000,105000000),
  ('oit_00vz','tnt_0017','ord_00w1','prd_00rj','Windows Desktop App',1,72000000,72000000),
  ('oit_00w0','tnt_0017','ord_00w1','prd_00rh','Website Development',1,48000000,48000000);
INSERT INTO order_items VALUES
  ('oit_00w2','tnt_0017','ord_00w4','prd_00rj','Windows Desktop App',2,72000000,144000000),
  ('oit_00w3','tnt_0017','ord_00w4','prd_00rl','AI Voice Agent Setup',1,24000000,24000000),
  ('oit_00w5','tnt_0017','ord_00w8','prd_00rl','AI Voice Agent Setup',1,24000000,24000000),
  ('oit_00w6','tnt_0017','ord_00w8','prd_00rn','Technical Support (monthly)',2,3500000,7000000),
  ('oit_00w7','tnt_0017','ord_00w8','prd_00rf','Enterprise CRM License',3,12000000,36000000),
  ('oit_00w9','tnt_0017','ord_00wb','prd_00rl','AI Voice Agent Setup',3,24000000,72000000),
  ('oit_00wa','tnt_0017','ord_00wb','prd_00rj','Windows Desktop App',2,72000000,144000000),
  ('oit_00wc','tnt_0017','ord_00wf','prd_00rj','Windows Desktop App',2,72000000,144000000),
  ('oit_00wd','tnt_0017','ord_00wf','prd_00rl','AI Voice Agent Setup',3,24000000,72000000),
  ('oit_00we','tnt_0017','ord_00wf','prd_00rf','Enterprise CRM License',1,12000000,12000000),
  ('oit_00wg','tnt_0017','ord_00wi','prd_00rl','AI Voice Agent Setup',1,24000000,24000000),
  ('oit_00wh','tnt_0017','ord_00wi','prd_00rg','CRM Implementation',3,35000000,105000000);

-- projects: 15 rows
INSERT INTO projects VALUES
  ('prj_00wj','tnt_0016','CRM внедрение Acme','org_00bh','ord_00t2','active','critical',79,30703000,'UZS','usr_000w','2026-05-23 09:00:00','2026-11-02 09:00:00','CRM внедрение Acme — проект под ключ: аналитика, разработка, тестирование и запуск.','2026-05-23 09:00:00','2026-10-10 09:00:00'),
  ('prj_00wv','tnt_0016','Корпоративный сайт','org_00ce','ord_00s6','on_hold','high',59,137487000,'UZS','usr_0013','2026-05-21 09:00:00','2026-12-07 09:00:00','Корпоративный сайт — проект под ключ: аналитика, разработка, тестирование и запуск.','2026-05-21 09:00:00','2026-10-10 09:00:00'),
  ('prj_00x7','tnt_0016','Android приложение доставки','org_00c6','ord_00ul','done','medium',100,29770000,'UZS','usr_000w','2026-06-21 09:00:00','2026-11-16 09:00:00','Android приложение доставки — проект под ключ: аналитика, разработка, тестирование и запуск.','2026-06-21 09:00:00','2026-10-10 09:00:00'),
  ('prj_00xi','tnt_0016','AI голосовой агент — пилот','org_00c0','ord_00uj','active','low',71,38028000,'UZS','usr_000r','2026-07-13 09:00:00','2026-12-02 09:00:00','AI голосовой агент — пилот — проект под ключ: аналитика, разработка, тестирование и запуск.','2026-07-13 09:00:00','2026-10-06 09:00:00'),
  ('prj_00xu','tnt_0016','Windows клиент для склада','org_00cn','ord_00ul','review','medium',31,29478000,'UZS','usr_000u','2026-06-01 09:00:00','2026-12-06 09:00:00','Windows клиент для склада — проект под ключ: аналитика, разработка, тестирование и запуск.','2026-06-01 09:00:00','2026-10-09 09:00:00'),
  ('prj_00y6','tnt_0016','Интеграция с 1С','org_00ce','ord_00s6','active','critical',70,164941000,'UZS','usr_000s','2026-08-11 09:00:00','2026-10-07 09:00:00','Интеграция с 1С — проект под ключ: аналитика, разработка, тестирование и запуск.','2026-08-11 09:00:00','2026-10-05 09:00:00'),
  ('prj_00yj','tnt_0016','Ребрендинг и дизайн-система','org_00du',NULL,'planning','medium',1,38642000,'UZS','usr_000y','2026-06-10 09:00:00','2026-12-12 09:00:00','Ребрендинг и дизайн-система — проект под ключ: аналитика, разработка, тестирование и запуск.','2026-06-10 09:00:00','2026-10-11 09:00:00'),
  ('prj_00yu','tnt_0016','Портал клиента v2','org_00be','ord_00sf','active','medium',58,160924000,'UZS','usr_000x','2026-07-20 09:00:00','2026-12-24 09:00:00','Портал клиента v2 — проект под ключ: аналитика, разработка, тестирование и запуск.','2026-07-20 09:00:00','2026-10-09 09:00:00'),
  ('prj_00z8','tnt_0016','Миграция данных','org_00bu',NULL,'active','medium',30,176580000,'UZS','usr_000y','2026-05-10 09:00:00','2026-12-17 09:00:00','Миграция данных — проект под ключ: аналитика, разработка, тестирование и запуск.','2026-05-10 09:00:00','2026-10-09 09:00:00'),
  ('prj_00zk','tnt_0016','API шлюз для партнёров','org_00cb','ord_00s2','active','low',47,29479000,'UZS','usr_000s','2026-04-27 09:00:00','2026-10-06 09:00:00','API шлюз для партнёров — проект под ключ: аналитика, разработка, тестирование и запуск.','2026-04-27 09:00:00','2026-10-06 09:00:00'),
  ('prj_0105','tnt_0017','CRM внедрение Acme','org_00f5','ord_00vm','active','medium',25,37787000,'UZS','usr_000s','2026-04-22 09:00:00','2026-11-08 09:00:00','CRM внедрение Acme — проект под ключ: аналитика, разработка, тестирование и запуск.','2026-04-22 09:00:00','2026-10-11 09:00:00'),
  ('prj_010k','tnt_0017','Корпоративный сайт','org_00f0',NULL,'planning','medium',4,28617000,'UZS','usr_000s','2026-08-02 09:00:00','2026-11-13 09:00:00','Корпоративный сайт — проект под ключ: аналитика, разработка, тестирование и запуск.','2026-08-02 09:00:00','2026-10-09 09:00:00'),
  ('prj_010x','tnt_0017','Android приложение доставки','org_00f9',NULL,'on_hold','high',68,26453000,'UZS','usr_000u','2026-07-17 09:00:00','2026-12-27 09:00:00','Android приложение доставки — проект под ключ: аналитика, разработка, тестирование и запуск.','2026-07-17 09:00:00','2026-10-09 09:00:00'),
  ('prj_011a','tnt_0017','AI голосовой агент — пилот','org_00ff','ord_00wi','active','low',24,128604000,'UZS','usr_000u','2026-07-24 09:00:00','2026-12-23 09:00:00','AI голосовой агент — пилот — проект под ключ: аналитика, разработка, тестирование и запуск.','2026-07-24 09:00:00','2026-10-10 09:00:00'),
  ('prj_011l','tnt_0017','Windows клиент для склада','org_00ey','ord_00wf','on_hold','low',45,145388000,'UZS','usr_000x','2026-09-27 09:00:00','2026-10-08 09:00:00','Windows клиент для склада — проект под ключ: аналитика, разработка, тестирование и запуск.','2026-09-27 09:00:00','2026-10-06 09:00:00');

-- project_milestones: 75 rows
INSERT INTO project_milestones VALUES
  ('ms_00wk','tnt_0016','prj_00wj','Аналитика и ТЗ','done','2026-06-06 09:00:00',0),
  ('ms_00wl','tnt_0016','prj_00wj','Дизайн','in_progress','2026-06-20 09:00:00',1),
  ('ms_00wm','tnt_0016','prj_00wj','Разработка','pending','2026-07-04 09:00:00',2),
  ('ms_00wn','tnt_0016','prj_00wj','Тестирование','pending','2026-07-18 09:00:00',3),
  ('ms_00wo','tnt_0016','prj_00wj','Запуск','pending','2026-08-01 09:00:00',4),
  ('ms_00ww','tnt_0016','prj_00wv','Аналитика и ТЗ','done','2026-06-04 09:00:00',0),
  ('ms_00wx','tnt_0016','prj_00wv','Дизайн','done','2026-06-18 09:00:00',1),
  ('ms_00wy','tnt_0016','prj_00wv','Разработка','in_progress','2026-07-02 09:00:00',2),
  ('ms_00wz','tnt_0016','prj_00wv','Тестирование','pending','2026-07-16 09:00:00',3),
  ('ms_00x0','tnt_0016','prj_00wv','Запуск','pending','2026-07-30 09:00:00',4),
  ('ms_00x8','tnt_0016','prj_00x7','Аналитика и ТЗ','done','2026-07-05 09:00:00',0),
  ('ms_00x9','tnt_0016','prj_00x7','Дизайн','done','2026-07-19 09:00:00',1),
  ('ms_00xa','tnt_0016','prj_00x7','Разработка','done','2026-08-02 09:00:00',2),
  ('ms_00xb','tnt_0016','prj_00x7','Тестирование','done','2026-08-16 09:00:00',3),
  ('ms_00xc','tnt_0016','prj_00x7','Запуск','done','2026-08-30 09:00:00',4),
  ('ms_00xj','tnt_0016','prj_00xi','Аналитика и ТЗ','done','2026-07-27 09:00:00',0),
  ('ms_00xk','tnt_0016','prj_00xi','Дизайн','done','2026-08-10 09:00:00',1),
  ('ms_00xl','tnt_0016','prj_00xi','Разработка','done','2026-08-24 09:00:00',2),
  ('ms_00xm','tnt_0016','prj_00xi','Тестирование','in_progress','2026-09-07 09:00:00',3),
  ('ms_00xn','tnt_0016','prj_00xi','Запуск','pending','2026-09-21 09:00:00',4),
  ('ms_00xv','tnt_0016','prj_00xu','Аналитика и ТЗ','done','2026-06-15 09:00:00',0),
  ('ms_00xw','tnt_0016','prj_00xu','Дизайн','done','2026-06-29 09:00:00',1),
  ('ms_00xx','tnt_0016','prj_00xu','Разработка','in_progress','2026-07-13 09:00:00',2),
  ('ms_00xy','tnt_0016','prj_00xu','Тестирование','pending','2026-07-27 09:00:00',3),
  ('ms_00xz','tnt_0016','prj_00xu','Запуск','pending','2026-08-10 09:00:00',4),
  ('ms_00y7','tnt_0016','prj_00y6','Аналитика и ТЗ','done','2026-08-25 09:00:00',0),
  ('ms_00y8','tnt_0016','prj_00y6','Дизайн','done','2026-09-08 09:00:00',1),
  ('ms_00y9','tnt_0016','prj_00y6','Разработка','done','2026-09-22 09:00:00',2),
  ('ms_00ya','tnt_0016','prj_00y6','Тестирование','done','2026-10-06 09:00:00',3),
  ('ms_00yb','tnt_0016','prj_00y6','Запуск','in_progress','2026-10-20 09:00:00',4),
  ('ms_00yk','tnt_0016','prj_00yj','Аналитика и ТЗ','done','2026-06-24 09:00:00',0),
  ('ms_00yl','tnt_0016','prj_00yj','Дизайн','done','2026-07-08 09:00:00',1),
  ('ms_00ym','tnt_0016','prj_00yj','Разработка','done','2026-07-22 09:00:00',2),
  ('ms_00yn','tnt_0016','prj_00yj','Тестирование','done','2026-08-05 09:00:00',3),
  ('ms_00yo','tnt_0016','prj_00yj','Запуск','in_progress','2026-08-19 09:00:00',4),
  ('ms_00yv','tnt_0016','prj_00yu','Аналитика и ТЗ','done','2026-08-03 09:00:00',0),
  ('ms_00yw','tnt_0016','prj_00yu','Дизайн','done','2026-08-17 09:00:00',1),
  ('ms_00yx','tnt_0016','prj_00yu','Разработка','done','2026-08-31 09:00:00',2),
  ('ms_00yy','tnt_0016','prj_00yu','Тестирование','done','2026-09-14 09:00:00',3),
  ('ms_00yz','tnt_0016','prj_00yu','Запуск','in_progress','2026-09-28 09:00:00',4),
  ('ms_00z9','tnt_0016','prj_00z8','Аналитика и ТЗ','done','2026-05-24 09:00:00',0),
  ('ms_00za','tnt_0016','prj_00z8','Дизайн','done','2026-06-07 09:00:00',1),
  ('ms_00zb','tnt_0016','prj_00z8','Разработка','in_progress','2026-06-21 09:00:00',2),
  ('ms_00zc','tnt_0016','prj_00z8','Тестирование','pending','2026-07-05 09:00:00',3),
  ('ms_00zd','tnt_0016','prj_00z8','Запуск','pending','2026-07-19 09:00:00',4),
  ('ms_00zl','tnt_0016','prj_00zk','Аналитика и ТЗ','done','2026-05-11 09:00:00',0),
  ('ms_00zm','tnt_0016','prj_00zk','Дизайн','done','2026-05-25 09:00:00',1),
  ('ms_00zn','tnt_0016','prj_00zk','Разработка','in_progress','2026-06-08 09:00:00',2),
  ('ms_00zo','tnt_0016','prj_00zk','Тестирование','pending','2026-06-22 09:00:00',3),
  ('ms_00zp','tnt_0016','prj_00zk','Запуск','pending','2026-07-06 09:00:00',4);
INSERT INTO project_milestones VALUES
  ('ms_0106','tnt_0017','prj_0105','Аналитика и ТЗ','in_progress','2026-05-06 09:00:00',0),
  ('ms_0107','tnt_0017','prj_0105','Дизайн','pending','2026-05-20 09:00:00',1),
  ('ms_0108','tnt_0017','prj_0105','Разработка','pending','2026-06-03 09:00:00',2),
  ('ms_0109','tnt_0017','prj_0105','Тестирование','pending','2026-06-17 09:00:00',3),
  ('ms_010a','tnt_0017','prj_0105','Запуск','pending','2026-07-01 09:00:00',4),
  ('ms_010l','tnt_0017','prj_010k','Аналитика и ТЗ','done','2026-08-16 09:00:00',0),
  ('ms_010m','tnt_0017','prj_010k','Дизайн','in_progress','2026-08-30 09:00:00',1),
  ('ms_010n','tnt_0017','prj_010k','Разработка','pending','2026-09-13 09:00:00',2),
  ('ms_010o','tnt_0017','prj_010k','Тестирование','pending','2026-09-27 09:00:00',3),
  ('ms_010p','tnt_0017','prj_010k','Запуск','pending','2026-10-11 09:00:00',4),
  ('ms_010y','tnt_0017','prj_010x','Аналитика и ТЗ','done','2026-07-31 09:00:00',0),
  ('ms_010z','tnt_0017','prj_010x','Дизайн','done','2026-08-14 09:00:00',1),
  ('ms_0110','tnt_0017','prj_010x','Разработка','in_progress','2026-08-28 09:00:00',2),
  ('ms_0111','tnt_0017','prj_010x','Тестирование','pending','2026-09-11 09:00:00',3),
  ('ms_0112','tnt_0017','prj_010x','Запуск','pending','2026-09-25 09:00:00',4),
  ('ms_011b','tnt_0017','prj_011a','Аналитика и ТЗ','in_progress','2026-08-07 09:00:00',0),
  ('ms_011c','tnt_0017','prj_011a','Дизайн','pending','2026-08-21 09:00:00',1),
  ('ms_011d','tnt_0017','prj_011a','Разработка','pending','2026-09-04 09:00:00',2),
  ('ms_011e','tnt_0017','prj_011a','Тестирование','pending','2026-09-18 09:00:00',3),
  ('ms_011f','tnt_0017','prj_011a','Запуск','pending','2026-10-02 09:00:00',4),
  ('ms_011m','tnt_0017','prj_011l','Аналитика и ТЗ','in_progress','2026-10-11 09:00:00',0),
  ('ms_011n','tnt_0017','prj_011l','Дизайн','pending','2026-10-25 09:00:00',1),
  ('ms_011o','tnt_0017','prj_011l','Разработка','pending','2026-11-08 09:00:00',2),
  ('ms_011p','tnt_0017','prj_011l','Тестирование','pending','2026-11-22 09:00:00',3),
  ('ms_011q','tnt_0017','prj_011l','Запуск','pending','2026-12-06 09:00:00',4);

-- tasks: 113 rows
INSERT INTO tasks VALUES
  ('tsk_00wp','tnt_0016','Проверить мобильную версию',NULL,'prj_00wj',NULL,NULL,'usr_0013','todo','critical','2026-10-28 09:00:00',NULL,'usr_000w','2026-05-23 09:00:00','2026-10-10 09:00:00'),
  ('tsk_00wq','tnt_0016','Написать API endpoint',NULL,'prj_00wj',NULL,NULL,'usr_000w','todo','high','2026-11-01 09:00:00',NULL,'usr_000w','2026-05-23 09:00:00','2026-10-09 09:00:00'),
  ('tsk_00wr','tnt_0016','Проверить мобильную версию',NULL,'prj_00wj',NULL,NULL,'usr_000u','review','critical','2026-10-31 09:00:00',NULL,'usr_000w','2026-05-23 09:00:00','2026-10-11 09:00:00'),
  ('tsk_00ws','tnt_0016','Подготовить прототип',NULL,'prj_00wj',NULL,NULL,'usr_000y','todo','low','2026-10-07 09:00:00',NULL,'usr_000w','2026-05-23 09:00:00','2026-10-10 09:00:00'),
  ('tsk_00wt','tnt_0016','Согласовать макет с клиентом',NULL,'prj_00wj',NULL,NULL,'usr_000t','in_progress','medium','2026-10-25 09:00:00',NULL,'usr_000w','2026-05-23 09:00:00','2026-10-09 09:00:00'),
  ('tsk_00wu','tnt_0016','Проверить мобильную версию',NULL,'prj_00wj',NULL,NULL,'usr_000u','done','low','2026-10-11 09:00:00','2026-10-05 09:00:00','usr_000w','2026-05-23 09:00:00','2026-10-10 09:00:00'),
  ('tsk_00x1','tnt_0016','Проверить мобильную версию',NULL,'prj_00wv',NULL,NULL,'usr_000x','in_progress','low','2026-10-13 09:00:00',NULL,'usr_0013','2026-05-21 09:00:00','2026-10-11 09:00:00'),
  ('tsk_00x2','tnt_0016','Написать API endpoint',NULL,'prj_00wv',NULL,NULL,'usr_000v','todo','medium','2026-10-20 09:00:00',NULL,'usr_0013','2026-05-21 09:00:00','2026-10-12 09:00:00'),
  ('tsk_00x3','tnt_0016','Исправить замечания QA',NULL,'prj_00wv',NULL,NULL,'usr_000w','todo','critical','2026-10-24 09:00:00',NULL,'usr_0013','2026-05-21 09:00:00','2026-10-09 09:00:00'),
  ('tsk_00x4','tnt_0016','Исправить замечания QA',NULL,'prj_00wv',NULL,NULL,'usr_000r','done','high','2026-10-11 09:00:00','2026-10-03 09:00:00','usr_0013','2026-05-21 09:00:00','2026-10-09 09:00:00'),
  ('tsk_00x5','tnt_0016','Согласовать макет с клиентом',NULL,'prj_00wv',NULL,NULL,'usr_000y','todo','low','2026-10-05 09:00:00',NULL,'usr_0013','2026-05-21 09:00:00','2026-10-09 09:00:00'),
  ('tsk_00x6','tnt_0016','Собрать обратную связь',NULL,'prj_00wv',NULL,NULL,'usr_000u','done','critical','2026-10-09 09:00:00','2026-10-07 09:00:00','usr_0013','2026-05-21 09:00:00','2026-10-10 09:00:00'),
  ('tsk_00xd','tnt_0016','Подготовить прототип',NULL,'prj_00x7',NULL,NULL,'usr_000r','review','high','2026-10-20 09:00:00',NULL,'usr_000w','2026-06-21 09:00:00','2026-10-12 09:00:00'),
  ('tsk_00xe','tnt_0016','Обновить документацию',NULL,'prj_00x7',NULL,NULL,'usr_000u','review','medium','2026-10-24 09:00:00',NULL,'usr_000w','2026-06-21 09:00:00','2026-10-09 09:00:00'),
  ('tsk_00xf','tnt_0016','Написать API endpoint',NULL,'prj_00x7',NULL,NULL,'usr_000s','todo','medium','2026-10-30 09:00:00',NULL,'usr_000w','2026-06-21 09:00:00','2026-10-10 09:00:00'),
  ('tsk_00xg','tnt_0016','Проверить мобильную версию',NULL,'prj_00x7',NULL,NULL,'usr_000r','todo','low','2026-10-28 09:00:00',NULL,'usr_000w','2026-06-21 09:00:00','2026-10-12 09:00:00'),
  ('tsk_00xh','tnt_0016','Настроить CI/CD',NULL,'prj_00x7',NULL,NULL,'usr_0013','in_progress','high','2026-10-31 09:00:00',NULL,'usr_000w','2026-06-21 09:00:00','2026-10-09 09:00:00'),
  ('tsk_00xo','tnt_0016','Проверить мобильную версию',NULL,'prj_00xi',NULL,NULL,'usr_000r','done','medium','2026-10-20 09:00:00','2026-10-07 09:00:00','usr_000r','2026-07-13 09:00:00','2026-10-11 09:00:00'),
  ('tsk_00xp','tnt_0016','Исправить замечания QA',NULL,'prj_00xi',NULL,NULL,'usr_000v','done','high','2026-10-29 09:00:00','2026-10-11 09:00:00','usr_000r','2026-07-13 09:00:00','2026-10-10 09:00:00'),
  ('tsk_00xq','tnt_0016','Исправить замечания QA',NULL,'prj_00xi',NULL,NULL,'usr_000y','in_progress','critical','2026-10-20 09:00:00',NULL,'usr_000r','2026-07-13 09:00:00','2026-10-10 09:00:00'),
  ('tsk_00xr','tnt_0016','Согласовать макет с клиентом',NULL,'prj_00xi',NULL,NULL,'usr_0013','review','critical','2026-10-13 09:00:00',NULL,'usr_000r','2026-07-13 09:00:00','2026-10-09 09:00:00'),
  ('tsk_00xs','tnt_0016','Исправить замечания QA',NULL,'prj_00xi',NULL,NULL,'usr_000x','todo','medium','2026-10-15 09:00:00',NULL,'usr_000r','2026-07-13 09:00:00','2026-10-11 09:00:00'),
  ('tsk_00xt','tnt_0016','Проверить мобильную версию',NULL,'prj_00xi',NULL,NULL,'usr_000r','in_progress','medium','2026-10-28 09:00:00',NULL,'usr_000r','2026-07-13 09:00:00','2026-10-09 09:00:00'),
  ('tsk_00y0','tnt_0016','Проверить мобильную версию',NULL,'prj_00xu',NULL,NULL,'usr_000x','done','medium','2026-10-29 09:00:00','2026-10-07 09:00:00','usr_000u','2026-06-01 09:00:00','2026-10-12 09:00:00'),
  ('tsk_00y1','tnt_0016','Провести код-ревью',NULL,'prj_00xu',NULL,NULL,'usr_000w','in_progress','low','2026-10-18 09:00:00',NULL,'usr_000u','2026-06-01 09:00:00','2026-10-10 09:00:00'),
  ('tsk_00y2','tnt_0016','Обновить документацию',NULL,'prj_00xu',NULL,NULL,'usr_000y','done','medium','2026-10-20 09:00:00','2026-10-09 09:00:00','usr_000u','2026-06-01 09:00:00','2026-10-12 09:00:00'),
  ('tsk_00y3','tnt_0016','Согласовать макет с клиентом',NULL,'prj_00xu',NULL,NULL,'usr_000v','review','low','2026-10-29 09:00:00',NULL,'usr_000u','2026-06-01 09:00:00','2026-10-12 09:00:00'),
  ('tsk_00y4','tnt_0016','Обновить документацию',NULL,'prj_00xu',NULL,NULL,'usr_0013','done','low','2026-10-12 09:00:00','2026-10-07 09:00:00','usr_000u','2026-06-01 09:00:00','2026-10-12 09:00:00'),
  ('tsk_00y5','tnt_0016','Согласовать макет с клиентом',NULL,'prj_00xu',NULL,NULL,'usr_000x','in_progress','high','2026-10-28 09:00:00',NULL,'usr_000u','2026-06-01 09:00:00','2026-10-12 09:00:00'),
  ('tsk_00yc','tnt_0016','Настроить CI/CD',NULL,'prj_00y6',NULL,NULL,'usr_000x','done','medium','2026-10-08 09:00:00','2026-10-10 09:00:00','usr_000s','2026-08-11 09:00:00','2026-10-11 09:00:00'),
  ('tsk_00yd','tnt_0016','Обновить документацию',NULL,'prj_00y6',NULL,NULL,'usr_000s','done','high','2026-10-07 09:00:00','2026-10-05 09:00:00','usr_000s','2026-08-11 09:00:00','2026-10-09 09:00:00'),
  ('tsk_00ye','tnt_0016','Провести демо для клиента',NULL,'prj_00y6',NULL,NULL,'usr_000s','review','medium','2026-10-26 09:00:00',NULL,'usr_000s','2026-08-11 09:00:00','2026-10-10 09:00:00'),
  ('tsk_00yf','tnt_0016','Настроить CI/CD',NULL,'prj_00y6',NULL,NULL,'usr_000t','in_progress','critical','2026-10-08 09:00:00',NULL,'usr_000s','2026-08-11 09:00:00','2026-10-09 09:00:00'),
  ('tsk_00yg','tnt_0016','Исправить замечания QA',NULL,'prj_00y6',NULL,NULL,'usr_000w','in_progress','medium','2026-10-07 09:00:00',NULL,'usr_000s','2026-08-11 09:00:00','2026-10-11 09:00:00'),
  ('tsk_00yh','tnt_0016','Обновить документацию',NULL,'prj_00y6',NULL,NULL,'usr_000r','done','critical','2026-10-30 09:00:00','2026-10-10 09:00:00','usr_000s','2026-08-11 09:00:00','2026-10-12 09:00:00'),
  ('tsk_00yi','tnt_0016','Написать API endpoint',NULL,'prj_00y6',NULL,NULL,'usr_0013','done','medium','2026-10-22 09:00:00','2026-10-09 09:00:00','usr_000s','2026-08-11 09:00:00','2026-10-09 09:00:00'),
  ('tsk_00yp','tnt_0016','Проверить мобильную версию',NULL,'prj_00yj',NULL,NULL,'usr_000v','done','medium','2026-10-22 09:00:00','2026-10-05 09:00:00','usr_000y','2026-06-10 09:00:00','2026-10-10 09:00:00'),
  ('tsk_00yq','tnt_0016','Согласовать макет с клиентом',NULL,'prj_00yj',NULL,NULL,'usr_000y','todo','medium','2026-10-30 09:00:00',NULL,'usr_000y','2026-06-10 09:00:00','2026-10-11 09:00:00'),
  ('tsk_00yr','tnt_0016','Обновить документацию',NULL,'prj_00yj',NULL,NULL,'usr_0013','todo','medium','2026-10-21 09:00:00',NULL,'usr_000y','2026-06-10 09:00:00','2026-10-09 09:00:00'),
  ('tsk_00ys','tnt_0016','Проверить мобильную версию',NULL,'prj_00yj',NULL,NULL,'usr_0013','review','medium','2026-10-20 09:00:00',NULL,'usr_000y','2026-06-10 09:00:00','2026-10-12 09:00:00'),
  ('tsk_00yt','tnt_0016','Провести код-ревью',NULL,'prj_00yj',NULL,NULL,'usr_000v','review','medium','2026-10-26 09:00:00',NULL,'usr_000y','2026-06-10 09:00:00','2026-10-12 09:00:00'),
  ('tsk_00z0','tnt_0016','Настроить CI/CD',NULL,'prj_00yu',NULL,NULL,'usr_000w','todo','low','2026-10-26 09:00:00',NULL,'usr_000x','2026-07-20 09:00:00','2026-10-09 09:00:00'),
  ('tsk_00z1','tnt_0016','Провести демо для клиента',NULL,'prj_00yu',NULL,NULL,'usr_000r','in_progress','low','2026-10-29 09:00:00',NULL,'usr_000x','2026-07-20 09:00:00','2026-10-09 09:00:00'),
  ('tsk_00z2','tnt_0016','Исправить замечания QA',NULL,'prj_00yu',NULL,NULL,'usr_000x','done','medium','2026-10-20 09:00:00','2026-10-04 09:00:00','usr_000x','2026-07-20 09:00:00','2026-10-09 09:00:00'),
  ('tsk_00z3','tnt_0016','Обновить документацию',NULL,'prj_00yu',NULL,NULL,'usr_000w','review','critical','2026-10-27 09:00:00',NULL,'usr_000x','2026-07-20 09:00:00','2026-10-11 09:00:00'),
  ('tsk_00z4','tnt_0016','Провести код-ревью',NULL,'prj_00yu',NULL,NULL,'usr_000w','review','medium','2026-10-29 09:00:00',NULL,'usr_000x','2026-07-20 09:00:00','2026-10-11 09:00:00'),
  ('tsk_00z5','tnt_0016','Настроить CI/CD',NULL,'prj_00yu',NULL,NULL,'usr_000u','done','low','2026-10-19 09:00:00','2026-10-10 09:00:00','usr_000x','2026-07-20 09:00:00','2026-10-09 09:00:00'),
  ('tsk_00z6','tnt_0016','Написать API endpoint',NULL,'prj_00yu',NULL,NULL,'usr_000y','done','medium','2026-10-13 09:00:00','2026-10-02 09:00:00','usr_000x','2026-07-20 09:00:00','2026-10-11 09:00:00'),
  ('tsk_00z7','tnt_0016','Собрать обратную связь',NULL,'prj_00yu',NULL,NULL,'usr_0013','review','low','2026-10-17 09:00:00',NULL,'usr_000x','2026-07-20 09:00:00','2026-10-11 09:00:00'),
  ('tsk_00ze','tnt_0016','Проверить мобильную версию',NULL,'prj_00z8',NULL,NULL,'usr_000y','in_progress','medium','2026-10-17 09:00:00',NULL,'usr_000y','2026-05-10 09:00:00','2026-10-11 09:00:00');
INSERT INTO tasks VALUES
  ('tsk_00zf','tnt_0016','Согласовать макет с клиентом',NULL,'prj_00z8',NULL,NULL,'usr_000w','todo','low','2026-10-13 09:00:00',NULL,'usr_000y','2026-05-10 09:00:00','2026-10-10 09:00:00'),
  ('tsk_00zg','tnt_0016','Собрать обратную связь',NULL,'prj_00z8',NULL,NULL,'usr_000s','in_progress','low','2026-10-16 09:00:00',NULL,'usr_000y','2026-05-10 09:00:00','2026-10-11 09:00:00'),
  ('tsk_00zh','tnt_0016','Исправить замечания QA',NULL,'prj_00z8',NULL,NULL,'usr_0013','done','medium','2026-10-06 09:00:00','2026-10-08 09:00:00','usr_000y','2026-05-10 09:00:00','2026-10-12 09:00:00'),
  ('tsk_00zi','tnt_0016','Настроить CI/CD',NULL,'prj_00z8',NULL,NULL,'usr_000t','done','medium','2026-10-22 09:00:00','2026-10-08 09:00:00','usr_000y','2026-05-10 09:00:00','2026-10-10 09:00:00'),
  ('tsk_00zj','tnt_0016','Обновить документацию',NULL,'prj_00z8',NULL,NULL,'usr_000s','in_progress','high','2026-10-04 09:00:00',NULL,'usr_000y','2026-05-10 09:00:00','2026-10-11 09:00:00'),
  ('tsk_00zq','tnt_0016','Написать API endpoint',NULL,'prj_00zk',NULL,NULL,'usr_000w','review','medium','2026-10-06 09:00:00',NULL,'usr_000s','2026-04-27 09:00:00','2026-10-12 09:00:00'),
  ('tsk_00zr','tnt_0016','Написать API endpoint',NULL,'prj_00zk',NULL,NULL,'usr_000t','done','high','2026-10-22 09:00:00','2026-10-09 09:00:00','usr_000s','2026-04-27 09:00:00','2026-10-10 09:00:00'),
  ('tsk_00zs','tnt_0016','Проверить мобильную версию',NULL,'prj_00zk',NULL,NULL,'usr_000t','todo','medium','2026-10-28 09:00:00',NULL,'usr_000s','2026-04-27 09:00:00','2026-10-11 09:00:00'),
  ('tsk_00zt','tnt_0016','Обновить документацию',NULL,'prj_00zk',NULL,NULL,'usr_000v','done','low','2026-10-29 09:00:00','2026-10-08 09:00:00','usr_000s','2026-04-27 09:00:00','2026-10-10 09:00:00'),
  ('tsk_00zu','tnt_0016','Исправить замечания QA',NULL,'prj_00zk',NULL,NULL,'usr_000w','review','medium','2026-10-27 09:00:00',NULL,'usr_000s','2026-04-27 09:00:00','2026-10-09 09:00:00'),
  ('tsk_00zv','tnt_0016','Разобрать входящие заявки',NULL,NULL,NULL,NULL,'usr_000s','in_progress','medium','2026-10-09 09:00:00',NULL,'usr_000r','2026-09-04 09:00:00','2026-10-10 09:00:00'),
  ('tsk_00zw','tnt_0016','Обновить прайс-лист',NULL,NULL,NULL,NULL,'usr_0013','done','high','2026-10-15 09:00:00',NULL,'usr_000r','2026-09-26 09:00:00','2026-10-12 09:00:00'),
  ('tsk_00zx','tnt_0016','Отправить КП повторно',NULL,NULL,NULL,NULL,'usr_000v','todo','medium','2026-10-21 09:00:00',NULL,'usr_000r','2026-09-14 09:00:00','2026-10-09 09:00:00'),
  ('tsk_00zy','tnt_0016','Разобрать входящие заявки',NULL,NULL,NULL,NULL,'usr_000r','done','low','2026-10-16 09:00:00',NULL,'usr_000r','2026-10-07 09:00:00','2026-10-10 09:00:00'),
  ('tsk_00zz','tnt_0016','Отправить КП повторно',NULL,NULL,NULL,NULL,'usr_000s','done','medium','2026-10-12 09:00:00',NULL,'usr_000r','2026-09-24 09:00:00','2026-10-11 09:00:00'),
  ('tsk_0100','tnt_0016','Подготовить отчёт за квартал',NULL,NULL,NULL,NULL,'usr_000y','in_progress','low','2026-10-09 09:00:00',NULL,'usr_000r','2026-10-02 09:00:00','2026-10-09 09:00:00'),
  ('tsk_0101','tnt_0016','Отправить КП повторно',NULL,NULL,NULL,NULL,'usr_0013','done','high','2026-10-20 09:00:00',NULL,'usr_000r','2026-10-07 09:00:00','2026-10-09 09:00:00'),
  ('tsk_0102','tnt_0016','Обновить прайс-лист',NULL,NULL,NULL,NULL,'usr_000u','todo','medium','2026-10-12 09:00:00',NULL,'usr_000r','2026-09-14 09:00:00','2026-10-11 09:00:00'),
  ('tsk_0103','tnt_0016','Обновить прайс-лист',NULL,NULL,NULL,NULL,'usr_000r','in_progress','low','2026-10-21 09:00:00',NULL,'usr_000r','2026-09-21 09:00:00','2026-10-11 09:00:00'),
  ('tsk_0104','tnt_0016','Отправить КП повторно',NULL,NULL,NULL,NULL,'usr_000w','done','medium','2026-10-10 09:00:00',NULL,'usr_000r','2026-10-03 09:00:00','2026-10-12 09:00:00'),
  ('tsk_010b','tnt_0017','Согласовать макет с клиентом',NULL,'prj_0105',NULL,NULL,'usr_000u','todo','high','2026-10-19 09:00:00',NULL,'usr_000s','2026-04-22 09:00:00','2026-10-09 09:00:00'),
  ('tsk_010c','tnt_0017','Обновить документацию',NULL,'prj_0105',NULL,NULL,'usr_0013','review','high','2026-10-09 09:00:00',NULL,'usr_000s','2026-04-22 09:00:00','2026-10-11 09:00:00'),
  ('tsk_010d','tnt_0017','Настроить CI/CD',NULL,'prj_0105',NULL,NULL,'usr_000v','review','high','2026-10-20 09:00:00',NULL,'usr_000s','2026-04-22 09:00:00','2026-10-10 09:00:00'),
  ('tsk_010e','tnt_0017','Настроить CI/CD',NULL,'prj_0105',NULL,NULL,'usr_000t','done','critical','2026-10-17 09:00:00','2026-10-07 09:00:00','usr_000s','2026-04-22 09:00:00','2026-10-12 09:00:00'),
  ('tsk_010f','tnt_0017','Провести демо для клиента',NULL,'prj_0105',NULL,NULL,'usr_000t','done','high','2026-10-23 09:00:00','2026-10-07 09:00:00','usr_000s','2026-04-22 09:00:00','2026-10-10 09:00:00'),
  ('tsk_010g','tnt_0017','Проверить мобильную версию',NULL,'prj_0105',NULL,NULL,'usr_0013','review','low','2026-10-24 09:00:00',NULL,'usr_000s','2026-04-22 09:00:00','2026-10-12 09:00:00'),
  ('tsk_010h','tnt_0017','Собрать обратную связь',NULL,'prj_0105',NULL,NULL,'usr_0013','in_progress','low','2026-10-14 09:00:00',NULL,'usr_000s','2026-04-22 09:00:00','2026-10-10 09:00:00'),
  ('tsk_010i','tnt_0017','Провести код-ревью',NULL,'prj_0105',NULL,NULL,'usr_000w','in_progress','low','2026-10-12 09:00:00',NULL,'usr_000s','2026-04-22 09:00:00','2026-10-11 09:00:00'),
  ('tsk_010j','tnt_0017','Настроить CI/CD',NULL,'prj_0105',NULL,NULL,'usr_000r','in_progress','medium','2026-10-05 09:00:00',NULL,'usr_000s','2026-04-22 09:00:00','2026-10-10 09:00:00'),
  ('tsk_010q','tnt_0017','Настроить CI/CD',NULL,'prj_010k',NULL,NULL,'usr_000y','review','medium','2026-11-01 09:00:00',NULL,'usr_000s','2026-08-02 09:00:00','2026-10-09 09:00:00'),
  ('tsk_010r','tnt_0017','Согласовать макет с клиентом',NULL,'prj_010k',NULL,NULL,'usr_000x','todo','medium','2026-10-15 09:00:00',NULL,'usr_000s','2026-08-02 09:00:00','2026-10-12 09:00:00'),
  ('tsk_010s','tnt_0017','Провести код-ревью',NULL,'prj_010k',NULL,NULL,'usr_000w','in_progress','high','2026-10-17 09:00:00',NULL,'usr_000s','2026-08-02 09:00:00','2026-10-10 09:00:00'),
  ('tsk_010t','tnt_0017','Провести код-ревью',NULL,'prj_010k',NULL,NULL,'usr_000w','done','low','2026-10-15 09:00:00','2026-10-02 09:00:00','usr_000s','2026-08-02 09:00:00','2026-10-12 09:00:00'),
  ('tsk_010u','tnt_0017','Провести демо для клиента',NULL,'prj_010k',NULL,NULL,'usr_000u','review','critical','2026-10-21 09:00:00',NULL,'usr_000s','2026-08-02 09:00:00','2026-10-12 09:00:00'),
  ('tsk_010v','tnt_0017','Провести код-ревью',NULL,'prj_010k',NULL,NULL,'usr_000x','todo','medium','2026-10-28 09:00:00',NULL,'usr_000s','2026-08-02 09:00:00','2026-10-11 09:00:00'),
  ('tsk_010w','tnt_0017','Собрать обратную связь',NULL,'prj_010k',NULL,NULL,'usr_0013','in_progress','medium','2026-10-17 09:00:00',NULL,'usr_000s','2026-08-02 09:00:00','2026-10-09 09:00:00'),
  ('tsk_0113','tnt_0017','Проверить мобильную версию',NULL,'prj_010x',NULL,NULL,'usr_000x','todo','medium','2026-10-13 09:00:00',NULL,'usr_000u','2026-07-17 09:00:00','2026-10-09 09:00:00'),
  ('tsk_0114','tnt_0017','Провести код-ревью',NULL,'prj_010x',NULL,NULL,'usr_000x','todo','medium','2026-10-17 09:00:00',NULL,'usr_000u','2026-07-17 09:00:00','2026-10-11 09:00:00'),
  ('tsk_0115','tnt_0017','Проверить мобильную версию',NULL,'prj_010x',NULL,NULL,'usr_000t','done','medium','2026-10-20 09:00:00','2026-10-02 09:00:00','usr_000u','2026-07-17 09:00:00','2026-10-12 09:00:00'),
  ('tsk_0116','tnt_0017','Обновить документацию',NULL,'prj_010x',NULL,NULL,'usr_0013','done','critical','2026-10-25 09:00:00','2026-10-11 09:00:00','usr_000u','2026-07-17 09:00:00','2026-10-10 09:00:00'),
  ('tsk_0117','tnt_0017','Написать API endpoint',NULL,'prj_010x',NULL,NULL,'usr_000s','done','critical','2026-11-01 09:00:00','2026-10-03 09:00:00','usr_000u','2026-07-17 09:00:00','2026-10-10 09:00:00'),
  ('tsk_0118','tnt_0017','Подготовить прототип',NULL,'prj_010x',NULL,NULL,'usr_000u','done','low','2026-10-11 09:00:00','2026-10-07 09:00:00','usr_000u','2026-07-17 09:00:00','2026-10-09 09:00:00'),
  ('tsk_0119','tnt_0017','Подготовить прототип',NULL,'prj_010x',NULL,NULL,'usr_000r','in_progress','high','2026-10-11 09:00:00',NULL,'usr_000u','2026-07-17 09:00:00','2026-10-12 09:00:00'),
  ('tsk_011g','tnt_0017','Проверить мобильную версию',NULL,'prj_011a',NULL,NULL,'usr_000v','done','high','2026-11-01 09:00:00','2026-10-10 09:00:00','usr_000u','2026-07-24 09:00:00','2026-10-10 09:00:00'),
  ('tsk_011h','tnt_0017','Исправить замечания QA',NULL,'prj_011a',NULL,NULL,'usr_000v','in_progress','critical','2026-10-20 09:00:00',NULL,'usr_000u','2026-07-24 09:00:00','2026-10-11 09:00:00'),
  ('tsk_011i','tnt_0017','Настроить CI/CD',NULL,'prj_011a',NULL,NULL,'usr_000r','done','critical','2026-10-19 09:00:00','2026-10-10 09:00:00','usr_000u','2026-07-24 09:00:00','2026-10-11 09:00:00'),
  ('tsk_011j','tnt_0017','Обновить документацию',NULL,'prj_011a',NULL,NULL,'usr_000y','in_progress','critical','2026-10-04 09:00:00',NULL,'usr_000u','2026-07-24 09:00:00','2026-10-12 09:00:00'),
  ('tsk_011k','tnt_0017','Написать API endpoint',NULL,'prj_011a',NULL,NULL,'usr_000r','todo','high','2026-10-26 09:00:00',NULL,'usr_000u','2026-07-24 09:00:00','2026-10-12 09:00:00'),
  ('tsk_011r','tnt_0017','Настроить CI/CD',NULL,'prj_011l',NULL,NULL,'usr_000v','review','low','2026-10-28 09:00:00',NULL,'usr_000x','2026-09-27 09:00:00','2026-10-09 09:00:00'),
  ('tsk_011s','tnt_0017','Провести код-ревью',NULL,'prj_011l',NULL,NULL,'usr_000x','in_progress','medium','2026-10-20 09:00:00',NULL,'usr_000x','2026-09-27 09:00:00','2026-10-11 09:00:00');
INSERT INTO tasks VALUES
  ('tsk_011t','tnt_0017','Обновить документацию',NULL,'prj_011l',NULL,NULL,'usr_000v','in_progress','medium','2026-10-21 09:00:00',NULL,'usr_000x','2026-09-27 09:00:00','2026-10-10 09:00:00'),
  ('tsk_011u','tnt_0017','Собрать обратную связь',NULL,'prj_011l',NULL,NULL,'usr_000v','todo','medium','2026-10-13 09:00:00',NULL,'usr_000x','2026-09-27 09:00:00','2026-10-12 09:00:00'),
  ('tsk_011v','tnt_0017','Проверить мобильную версию',NULL,'prj_011l',NULL,NULL,'usr_000s','in_progress','low','2026-10-06 09:00:00',NULL,'usr_000x','2026-09-27 09:00:00','2026-10-11 09:00:00'),
  ('tsk_011w','tnt_0017','Позвонить контакту из списка',NULL,NULL,NULL,NULL,'usr_0013','in_progress','low','2026-10-07 09:00:00',NULL,'usr_000r','2026-10-01 09:00:00','2026-10-11 09:00:00'),
  ('tsk_011x','tnt_0017','Подготовить отчёт за квартал',NULL,NULL,NULL,NULL,'usr_000x','in_progress','high','2026-10-07 09:00:00',NULL,'usr_000r','2026-09-19 09:00:00','2026-10-11 09:00:00'),
  ('tsk_011y','tnt_0017','Разобрать входящие заявки',NULL,NULL,NULL,NULL,'usr_000r','todo','medium','2026-10-21 09:00:00',NULL,'usr_000r','2026-09-17 09:00:00','2026-10-09 09:00:00'),
  ('tsk_011z','tnt_0017','Обновить прайс-лист',NULL,NULL,NULL,NULL,'usr_000t','todo','high','2026-10-15 09:00:00',NULL,'usr_000r','2026-10-03 09:00:00','2026-10-11 09:00:00'),
  ('tsk_0120','tnt_0017','Отправить КП повторно',NULL,NULL,NULL,NULL,'usr_0013','todo','medium','2026-10-07 09:00:00',NULL,'usr_000r','2026-09-22 09:00:00','2026-10-09 09:00:00'),
  ('tsk_0121','tnt_0017','Подготовить отчёт за квартал',NULL,NULL,NULL,NULL,'usr_000x','todo','medium','2026-10-12 09:00:00',NULL,'usr_000r','2026-09-13 09:00:00','2026-10-10 09:00:00'),
  ('tsk_0122','tnt_0017','Отправить КП повторно',NULL,NULL,NULL,NULL,'usr_000s','todo','low','2026-10-11 09:00:00',NULL,'usr_000r','2026-09-27 09:00:00','2026-10-12 09:00:00'),
  ('tsk_0123','tnt_0017','Разобрать входящие заявки',NULL,NULL,NULL,NULL,'usr_0013','done','low','2026-10-17 09:00:00',NULL,'usr_000r','2026-09-29 09:00:00','2026-10-12 09:00:00'),
  ('tsk_0124','tnt_0017','Отправить КП повторно',NULL,NULL,NULL,NULL,'usr_000y','in_progress','high','2026-10-20 09:00:00',NULL,'usr_000r','2026-09-12 09:00:00','2026-10-09 09:00:00'),
  ('tsk_0125','tnt_0017','Позвонить контакту из списка',NULL,NULL,NULL,NULL,'usr_000y','todo','medium','2026-10-16 09:00:00',NULL,'usr_000r','2026-10-08 09:00:00','2026-10-09 09:00:00');

-- tickets: 30 rows
INSERT INTO tickets VALUES
  ('tkt_0126','tnt_0016','TCK-4374','Ошибка при экспорте отчёта','org_00ed','support@acmecorp.uz','phone','low','pending','2026-09-15 17:00:00','usr_000y','Обращение зафиксировано. Требуется проверка интеграции и подтверждение клиента.','2026-09-15 09:00:00','2026-10-09 09:00:00'),
  ('tkt_0127','tnt_0016','TCK-4375','Просьба добавить поле в карточку сделки','org_00c3','support@acmecorp.uz','email','high','open','2026-09-07 17:00:00','usr_000u','Обращение зафиксировано. Требуется проверка интеграции и подтверждение клиента.','2026-09-07 09:00:00','2026-10-12 09:00:00'),
  ('tkt_0128','tnt_0016','TCK-4376','Вопрос по счёту','org_00d3','support@acmecorp.uz','phone','normal','open','2026-08-29 17:00:00','usr_000t','Обращение зафиксировано. Требуется проверка интеграции и подтверждение клиента.','2026-08-29 09:00:00','2026-10-09 09:00:00'),
  ('tkt_0129','tnt_0016','TCK-4377','Не работает вход в клиентский портал','org_00c0','support@acmecorp.uz','phone','normal','open','2026-09-23 17:00:00','usr_000x','Обращение зафиксировано. Требуется проверка интеграции и подтверждение клиента.','2026-09-23 09:00:00','2026-10-12 09:00:00'),
  ('tkt_012a','tnt_0016','TCK-4378','Не подключается Telegram-бот','org_00dc','support@acmecorp.uz','email','high','open','2026-09-01 17:00:00','usr_000s','Обращение зафиксировано. Требуется проверка интеграции и подтверждение клиента.','2026-09-01 09:00:00','2026-10-10 09:00:00'),
  ('tkt_012b','tnt_0016','TCK-4379','Ошибка при экспорте отчёта','org_00dx','client@example.uz','phone','normal','resolved','2026-09-22 17:00:00','usr_000t','Обращение зафиксировано. Требуется проверка интеграции и подтверждение клиента.','2026-09-22 09:00:00','2026-10-12 09:00:00'),
  ('tkt_012c','tnt_0016','TCK-4380','Ошибка при экспорте отчёта','org_00ce','client@example.uz','phone','normal','pending','2026-10-04 17:00:00','usr_000t','Обращение зафиксировано. Требуется проверка интеграции и подтверждение клиента.','2026-10-04 09:00:00','2026-10-12 09:00:00'),
  ('tkt_012d','tnt_0016','TCK-4381','Не подключается Telegram-бот','org_00dx','client@example.uz','telegram','urgent','open','2026-09-15 17:00:00','usr_000y','Обращение зафиксировано. Требуется проверка интеграции и подтверждение клиента.','2026-09-15 09:00:00','2026-10-10 09:00:00'),
  ('tkt_012e','tnt_0016','TCK-4382','Просьба добавить поле в карточку сделки','org_00cq','support@acmecorp.uz','telegram','normal','open','2026-08-28 17:00:00','usr_000t','Обращение зафиксировано. Требуется проверка интеграции и подтверждение клиента.','2026-08-28 09:00:00','2026-10-11 09:00:00'),
  ('tkt_012f','tnt_0016','TCK-4383','Не работает вход в клиентский портал','org_00dc','client@example.uz','portal','urgent','resolved','2026-09-10 17:00:00','usr_000w','Обращение зафиксировано. Требуется проверка интеграции и подтверждение клиента.','2026-09-10 09:00:00','2026-10-11 09:00:00'),
  ('tkt_012g','tnt_0016','TCK-4384','Вопрос по счёту','org_00ed','support@acmecorp.uz','portal','high','open','2026-09-14 17:00:00','usr_000v','Обращение зафиксировано. Требуется проверка интеграции и подтверждение клиента.','2026-09-14 09:00:00','2026-10-12 09:00:00'),
  ('tkt_012h','tnt_0016','TCK-4385','Просьба добавить поле в карточку сделки','org_00b9','client@example.uz','email','normal','pending','2026-10-03 17:00:00','usr_000x','Обращение зафиксировано. Требуется проверка интеграции и подтверждение клиента.','2026-10-03 09:00:00','2026-10-12 09:00:00'),
  ('tkt_012i','tnt_0016','TCK-4386','Медленно загружается канбан','org_00ax','support@acmecorp.uz','email','normal','open','2026-10-08 17:00:00','usr_000r','Обращение зафиксировано. Требуется проверка интеграции и подтверждение клиента.','2026-10-08 09:00:00','2026-10-12 09:00:00'),
  ('tkt_012j','tnt_0016','TCK-4387','Просьба добавить поле в карточку сделки','org_00b1','client@example.uz','telegram','urgent','closed','2026-08-29 17:00:00','usr_000t','Обращение зафиксировано. Требуется проверка интеграции и подтверждение клиента.','2026-08-29 09:00:00','2026-10-10 09:00:00'),
  ('tkt_012k','tnt_0016','TCK-4388','Медленно загружается канбан','org_00bx','support@acmecorp.uz','email','normal','closed','2026-09-02 17:00:00','usr_0013','Обращение зафиксировано. Требуется проверка интеграции и подтверждение клиента.','2026-09-02 09:00:00','2026-10-09 09:00:00'),
  ('tkt_012l','tnt_0016','TCK-4389','Не подключается Telegram-бот','org_00d3','support@acmecorp.uz','phone','low','closed','2026-09-13 17:00:00','usr_000r','Обращение зафиксировано. Требуется проверка интеграции и подтверждение клиента.','2026-09-13 09:00:00','2026-10-12 09:00:00'),
  ('tkt_012m','tnt_0016','TCK-4390','Ошибка при экспорте отчёта','org_00bm','client@example.uz','telegram','high','closed','2026-08-29 17:00:00','usr_000v','Обращение зафиксировано. Требуется проверка интеграции и подтверждение клиента.','2026-08-29 09:00:00','2026-10-11 09:00:00'),
  ('tkt_012n','tnt_0016','TCK-4391','Просьба добавить поле в карточку сделки','org_00c3','support@acmecorp.uz','portal','low','open','2026-09-11 17:00:00','usr_000v','Обращение зафиксировано. Требуется проверка интеграции и подтверждение клиента.','2026-09-11 09:00:00','2026-10-12 09:00:00'),
  ('tkt_012o','tnt_0016','TCK-4392','Просьба добавить поле в карточку сделки','org_00d3','client@example.uz','phone','normal','open','2026-09-16 17:00:00','usr_000w','Обращение зафиксировано. Требуется проверка интеграции и подтверждение клиента.','2026-09-16 09:00:00','2026-10-10 09:00:00'),
  ('tkt_012p','tnt_0016','TCK-4393','Не работает вход в клиентский портал','org_00b9','client@example.uz','telegram','normal','closed','2026-09-01 17:00:00','usr_000t','Обращение зафиксировано. Требуется проверка интеграции и подтверждение клиента.','2026-09-01 09:00:00','2026-10-09 09:00:00'),
  ('tkt_012q','tnt_0016','TCK-4394','Не работает вход в клиентский портал','org_00b9','support@acmecorp.uz','phone','urgent','pending','2026-10-10 17:00:00','usr_000s','Обращение зафиксировано. Требуется проверка интеграции и подтверждение клиента.','2026-10-10 09:00:00','2026-10-09 09:00:00'),
  ('tkt_012r','tnt_0016','TCK-4395','Не подключается Telegram-бот','org_00ax','client@example.uz','telegram','urgent','open','2026-09-09 17:00:00','usr_000s','Обращение зафиксировано. Требуется проверка интеграции и подтверждение клиента.','2026-09-09 09:00:00','2026-10-09 09:00:00'),
  ('tkt_015f','tnt_0017','TCK-4491','Медленно загружается канбан','org_00f7','client@example.uz','telegram','low','open','2026-09-14 17:00:00','usr_000y','Обращение зафиксировано. Требуется проверка интеграции и подтверждение клиента.','2026-09-14 09:00:00','2026-10-10 09:00:00'),
  ('tkt_015g','tnt_0017','TCK-4492','Просьба добавить поле в карточку сделки','org_00f0','support@acmecorp.uz','phone','normal','open','2026-10-04 17:00:00','usr_000s','Обращение зафиксировано. Требуется проверка интеграции и подтверждение клиента.','2026-10-04 09:00:00','2026-10-10 09:00:00'),
  ('tkt_015h','tnt_0017','TCK-4493','Просьба добавить поле в карточку сделки','org_00et','client@example.uz','email','normal','closed','2026-09-10 17:00:00','usr_000r','Обращение зафиксировано. Требуется проверка интеграции и подтверждение клиента.','2026-09-10 09:00:00','2026-10-09 09:00:00'),
  ('tkt_015i','tnt_0017','TCK-4494','Не подключается Telegram-бот','org_00fc','support@acmecorp.uz','telegram','urgent','closed','2026-09-01 17:00:00','usr_000r','Обращение зафиксировано. Требуется проверка интеграции и подтверждение клиента.','2026-09-01 09:00:00','2026-10-10 09:00:00'),
  ('tkt_015j','tnt_0017','TCK-4495','Вопрос по счёту','org_00f0','support@acmecorp.uz','portal','normal','closed','2026-09-27 17:00:00','usr_000y','Обращение зафиксировано. Требуется проверка интеграции и подтверждение клиента.','2026-09-27 09:00:00','2026-10-12 09:00:00'),
  ('tkt_015k','tnt_0017','TCK-4496','Медленно загружается канбан','org_00f7','client@example.uz','telegram','low','open','2026-09-13 17:00:00','usr_000w','Обращение зафиксировано. Требуется проверка интеграции и подтверждение клиента.','2026-09-13 09:00:00','2026-10-12 09:00:00'),
  ('tkt_015l','tnt_0017','TCK-4497','Вопрос по счёту','org_00et','client@example.uz','telegram','normal','pending','2026-08-28 17:00:00','usr_000r','Обращение зафиксировано. Требуется проверка интеграции и подтверждение клиента.','2026-08-28 09:00:00','2026-10-09 09:00:00'),
  ('tkt_015m','tnt_0017','TCK-4498','Ошибка при экспорте отчёта','org_00ey','client@example.uz','phone','high','closed','2026-10-12 17:00:00','usr_000s','Обращение зафиксировано. Требуется проверка интеграции и подтверждение клиента.','2026-10-12 09:00:00','2026-10-10 09:00:00');

-- communications: 54 rows
INSERT INTO communications VALUES
  ('com_012s','tnt_0016','telegram','outbound','Приглашение на демо','Здравствуйте! Направляем материалы по вашему запросу. Готовы обсудить детали в удобное время.','org_00ax','con_00dn','usr_000r','read','2026-09-20 09:00:00'),
  ('com_012t','tnt_0016','whatsapp','outbound','Ответ на запрос','Здравствуйте! Направляем материалы по вашему запросу. Готовы обсудить детали в удобное время.','org_00e2','con_00ea','usr_000r','sent','2026-09-29 09:00:00'),
  ('com_012u','tnt_0016','portal','outbound','Напоминание об оплате','Здравствуйте! Направляем материалы по вашему запросу. Готовы обсудить детали в удобное время.','org_00d7','con_00dj','usr_000v','delivered','2026-10-11 09:00:00'),
  ('com_012v','tnt_0016','email','outbound','Ответ на запрос','Здравствуйте! Направляем материалы по вашему запросу. Готовы обсудить детали в удобное время.','org_00cb','con_00cj','usr_000x','delivered','2026-09-23 09:00:00'),
  ('com_012w','tnt_0016','phone','outbound','Запрос обратной связи','Здравствуйте! Направляем материалы по вашему запросу. Готовы обсудить детали в удобное время.','org_00e9','con_00b2','usr_000u','sent','2026-09-21 09:00:00'),
  ('com_012x','tnt_0016','email','inbound','Приглашение на демо','Здравствуйте! Направляем материалы по вашему запросу. Готовы обсудить детали в удобное время.','org_00du','con_00cc','usr_000x','sent','2026-09-07 09:00:00'),
  ('com_012y','tnt_0016','whatsapp','outbound','Запрос обратной связи','Здравствуйте! Направляем материалы по вашему запросу. Готовы обсудить детали в удобное время.','org_00bm','con_00br','usr_000u','read','2026-09-23 09:00:00'),
  ('com_012z','tnt_0016','telegram','inbound','Напоминание об оплате','Здравствуйте! Направляем материалы по вашему запросу. Готовы обсудить детали в удобное время.','org_00ei','con_00dg','usr_000s','read','2026-10-03 09:00:00'),
  ('com_0130','tnt_0016','telegram','inbound','Напоминание об оплате','Здравствуйте! Направляем материалы по вашему запросу. Готовы обсудить детали в удобное время.','org_00c0','con_00b5','usr_000v','delivered','2026-09-28 09:00:00'),
  ('com_0131','tnt_0016','phone','inbound','Запрос обратной связи','Здравствуйте! Направляем материалы по вашему запросу. Готовы обсудить детали в удобное время.','org_00c6','con_00ds','usr_0013','sent','2026-09-12 09:00:00'),
  ('com_0132','tnt_0016','whatsapp','outbound','Ответ на запрос','Здравствуйте! Направляем материалы по вашему запросу. Готовы обсудить детали в удобное время.','org_00cq','con_00c5','usr_000u','read','2026-09-18 09:00:00'),
  ('com_0133','tnt_0016','whatsapp','inbound','Коммерческое предложение','Здравствуйте! Направляем материалы по вашему запросу. Готовы обсудить детали в удобное время.','org_00d3','con_00el','usr_000s','sent','2026-10-02 09:00:00'),
  ('com_0134','tnt_0016','phone','inbound','Запрос обратной связи','Здравствуйте! Направляем материалы по вашему запросу. Готовы обсудить детали в удобное время.','org_00dh','con_00dg','usr_000u','read','2026-09-24 09:00:00'),
  ('com_0135','tnt_0016','telegram','outbound','Приглашение на демо','Здравствуйте! Направляем материалы по вашему запросу. Готовы обсудить детали в удобное время.','org_00dp','con_00e4','usr_000x','delivered','2026-09-14 09:00:00'),
  ('com_0136','tnt_0016','email','outbound','Коммерческое предложение','Здравствуйте! Направляем материалы по вашему запросу. Готовы обсудить детали в удобное время.','org_00ci','con_00by','usr_000t','delivered','2026-09-22 09:00:00'),
  ('com_0137','tnt_0016','whatsapp','outbound','Приглашение на демо','Здравствуйте! Направляем материалы по вашему запросу. Готовы обсудить детали в удобное время.','org_00d7','con_00bn','usr_000t','sent','2026-09-02 09:00:00'),
  ('com_0138','tnt_0016','telegram','inbound','Приглашение на демо','Здравствуйте! Направляем материалы по вашему запросу. Готовы обсудить детали в удобное время.','org_00dx','con_00d8','usr_000r','read','2026-09-06 09:00:00'),
  ('com_0139','tnt_0016','telegram','inbound','Коммерческое предложение','Здравствуйте! Направляем материалы по вашему запросу. Готовы обсудить детали в удобное время.','org_00bh','con_00by','usr_000u','sent','2026-09-29 09:00:00'),
  ('com_013a','tnt_0016','telegram','inbound','Ответ на запрос','Здравствуйте! Направляем материалы по вашему запросу. Готовы обсудить детали в удобное время.','org_00b1','con_00em','usr_000u','delivered','2026-09-20 09:00:00'),
  ('com_013b','tnt_0016','telegram','outbound','Напоминание об оплате','Здравствуйте! Направляем материалы по вашему запросу. Готовы обсудить детали в удобное время.','org_00ed','con_00dz','usr_000y','sent','2026-09-09 09:00:00'),
  ('com_013c','tnt_0016','phone','outbound','Напоминание об оплате','Здравствуйте! Направляем материалы по вашему запросу. Готовы обсудить детали в удобное время.','org_00dx','con_00d0','usr_000r','sent','2026-09-09 09:00:00'),
  ('com_013d','tnt_0016','whatsapp','inbound','Коммерческое предложение','Здравствуйте! Направляем материалы по вашему запросу. Готовы обсудить детали в удобное время.','org_00bu','con_00e5','usr_000w','delivered','2026-09-10 09:00:00'),
  ('com_013e','tnt_0016','phone','outbound','Коммерческое предложение','Здравствуйте! Направляем материалы по вашему запросу. Готовы обсудить детали в удобное время.','org_00c3','con_00ef','usr_000y','read','2026-09-18 09:00:00'),
  ('com_013f','tnt_0016','email','inbound','Ответ на запрос','Здравствуйте! Направляем материалы по вашему запросу. Готовы обсудить детали в удобное время.','org_00bu','con_00bq','usr_000s','delivered','2026-09-29 09:00:00'),
  ('com_013g','tnt_0016','telegram','inbound','Напоминание об оплате','Здравствуйте! Направляем материалы по вашему запросу. Готовы обсудить детали в удобное время.','org_00cq','con_00e4','usr_000u','read','2026-10-07 09:00:00'),
  ('com_013h','tnt_0016','portal','inbound','Приглашение на демо','Здравствуйте! Направляем материалы по вашему запросу. Готовы обсудить детали в удобное время.','org_00dp','con_00ej','usr_000u','read','2026-10-02 09:00:00'),
  ('com_013i','tnt_0016','email','outbound','Приглашение на демо','Здравствуйте! Направляем материалы по вашему запросу. Готовы обсудить детали в удобное время.','org_00ce','con_00dz','usr_000y','delivered','2026-09-05 09:00:00'),
  ('com_013j','tnt_0016','email','inbound','Приглашение на демо','Здравствуйте! Направляем материалы по вашему запросу. Готовы обсудить детали в удобное время.','org_00bh','con_00ca','usr_000x','sent','2026-10-02 09:00:00'),
  ('com_013k','tnt_0016','email','inbound','Запрос обратной связи','Здравствуйте! Направляем материалы по вашему запросу. Готовы обсудить детали в удобное время.','org_00b6','con_00bk','usr_000r','read','2026-09-22 09:00:00'),
  ('com_013l','tnt_0016','email','outbound','Ответ на запрос','Здравствуйте! Направляем материалы по вашему запросу. Готовы обсудить детали в удобное время.','org_00dc','con_00cg','usr_000y','delivered','2026-09-21 09:00:00'),
  ('com_013m','tnt_0016','telegram','outbound','Коммерческое предложение','Здравствуйте! Направляем материалы по вашему запросу. Готовы обсудить детали в удобное время.','org_00d3','con_00d6','usr_000t','sent','2026-09-20 09:00:00'),
  ('com_013n','tnt_0016','whatsapp','inbound','Коммерческое предложение','Здравствуйте! Направляем материалы по вашему запросу. Готовы обсудить детали в удобное время.','org_00bp','con_00e0','usr_000r','sent','2026-09-20 09:00:00'),
  ('com_013o','tnt_0016','telegram','outbound','Приглашение на демо','Здравствуйте! Направляем материалы по вашему запросу. Готовы обсудить детали в удобное время.','org_00c3','con_00c1','usr_000s','sent','2026-10-05 09:00:00'),
  ('com_013p','tnt_0016','telegram','inbound','Ответ на запрос','Здравствуйте! Направляем материалы по вашему запросу. Готовы обсудить детали в удобное время.','org_00cq','con_00cd','usr_000y','delivered','2026-09-18 09:00:00'),
  ('com_013q','tnt_0016','email','inbound','Ответ на запрос','Здравствуйте! Направляем материалы по вашему запросу. Готовы обсудить детали в удобное время.','org_00dh','con_00cp','usr_000x','read','2026-10-05 09:00:00'),
  ('com_013r','tnt_0016','portal','outbound','Запрос обратной связи','Здравствуйте! Направляем материалы по вашему запросу. Готовы обсудить детали в удобное время.','org_00ce','con_00dg','usr_000y','delivered','2026-09-12 09:00:00'),
  ('com_013s','tnt_0016','portal','outbound','Напоминание об оплате','Здравствуйте! Направляем материалы по вашему запросу. Готовы обсудить детали в удобное время.','org_00bh','con_00ef','usr_000r','read','2026-09-17 09:00:00'),
  ('com_013t','tnt_0016','phone','inbound','Приглашение на демо','Здравствуйте! Направляем материалы по вашему запросу. Готовы обсудить детали в удобное время.','org_00c0','con_00c8','usr_0013','sent','2026-09-23 09:00:00'),
  ('com_013u','tnt_0016','whatsapp','outbound','Приглашение на демо','Здравствуйте! Направляем материалы по вашему запросу. Готовы обсудить детали в удобное время.','org_00bp','con_00bc','usr_000r','sent','2026-09-06 09:00:00'),
  ('com_013v','tnt_0016','phone','outbound','Коммерческое предложение','Здравствуйте! Направляем материалы по вашему запросу. Готовы обсудить детали в удобное время.','org_00d7','con_00cx','usr_000u','sent','2026-10-05 09:00:00'),
  ('com_015n','tnt_0017','email','inbound','Ответ на запрос','Здравствуйте! Направляем материалы по вашему запросу. Готовы обсудить детали в удобное время.','org_00f0','con_00ez','usr_000s','delivered','2026-10-10 09:00:00'),
  ('com_015o','tnt_0017','portal','outbound','Приглашение на демо','Здравствуйте! Направляем материалы по вашему запросу. Готовы обсудить детали в удобное время.','org_00f9','con_00ex','usr_000x','sent','2026-10-11 09:00:00'),
  ('com_015p','tnt_0017','whatsapp','inbound','Ответ на запрос','Здравствуйте! Направляем материалы по вашему запросу. Готовы обсудить детали в удобное время.','org_00ey','con_00fe','usr_000t','read','2026-10-01 09:00:00'),
  ('com_015q','tnt_0017','phone','outbound','Приглашение на демо','Здравствуйте! Направляем материалы по вашему запросу. Готовы обсудить детали в удобное время.','org_00f7','con_00eo','usr_000t','read','2026-10-03 09:00:00'),
  ('com_015r','tnt_0017','phone','outbound','Напоминание об оплате','Здравствуйте! Направляем материалы по вашему запросу. Готовы обсудить детали в удобное время.','org_00fc','con_00ew','usr_000w','sent','2026-09-03 09:00:00'),
  ('com_015s','tnt_0017','telegram','outbound','Напоминание об оплате','Здравствуйте! Направляем материалы по вашему запросу. Готовы обсудить детали в удобное время.','org_00eq','con_00f3','usr_000w','sent','2026-09-12 09:00:00'),
  ('com_015t','tnt_0017','phone','outbound','Ответ на запрос','Здравствуйте! Направляем материалы по вашему запросу. Готовы обсудить детали в удобное время.','org_00f2','con_00ew','usr_000w','sent','2026-09-23 09:00:00'),
  ('com_015u','tnt_0017','whatsapp','inbound','Напоминание об оплате','Здравствуйте! Направляем материалы по вашему запросу. Готовы обсудить детали в удобное время.','org_00en','con_00eo','usr_000s','delivered','2026-09-12 09:00:00'),
  ('com_015v','tnt_0017','portal','inbound','Напоминание об оплате','Здравствуйте! Направляем материалы по вашему запросу. Готовы обсудить детали в удобное время.','org_00f9','con_00f8','usr_000s','sent','2026-09-06 09:00:00'),
  ('com_015w','tnt_0017','whatsapp','outbound','Коммерческое предложение','Здравствуйте! Направляем материалы по вашему запросу. Готовы обсудить детали в удобное время.','org_00et','con_00f8','usr_000x','sent','2026-09-26 09:00:00');
INSERT INTO communications VALUES
  ('com_015x','tnt_0017','phone','inbound','Приглашение на демо','Здравствуйте! Направляем материалы по вашему запросу. Готовы обсудить детали в удобное время.','org_00en','con_00fe','usr_000s','sent','2026-09-21 09:00:00'),
  ('com_015y','tnt_0017','telegram','outbound','Напоминание об оплате','Здравствуйте! Направляем материалы по вашему запросу. Готовы обсудить детали в удобное время.','org_00f0','con_00f6','usr_000s','sent','2026-09-08 09:00:00'),
  ('com_015z','tnt_0017','telegram','outbound','Коммерческое предложение','Здравствуйте! Направляем материалы по вашему запросу. Готовы обсудить детали в удобное время.','org_00f7','con_00fb','usr_0013','delivered','2026-09-04 09:00:00'),
  ('com_0160','tnt_0017','email','outbound','Коммерческое предложение','Здравствуйте! Направляем материалы по вашему запросу. Готовы обсудить детали в удобное время.','org_00f5','con_00fa','usr_0013','sent','2026-09-03 09:00:00');

-- documents: 19 rows
INSERT INTO documents VALUES
  ('doc_013w','tnt_0016','Счёт на оплату №242','invoice','org_00ei','ord_00v6',2,987,'sent','usr_000v','2026-09-17 09:00:00'),
  ('doc_013x','tnt_0016','Коммерческое предложение №459','proposal','org_00b6',NULL,1,1328,'sent','usr_0013','2026-08-12 09:00:00'),
  ('doc_013y','tnt_0016','Техническое задание №451','act','org_00ce','ord_00sc',3,1959,'archived','usr_000v','2026-09-27 09:00:00'),
  ('doc_013z','tnt_0016','Коммерческое предложение №363','proposal','org_00cb','ord_00sa',1,623,'sent','usr_000v','2026-08-31 09:00:00'),
  ('doc_0140','tnt_0016','Коммерческое предложение №287','act','org_00be','ord_00uq',1,3122,'sent','usr_000t','2026-08-31 09:00:00'),
  ('doc_0141','tnt_0016','Техническое задание №325','other','org_00cn','ord_00sa',2,1626,'archived','usr_000x','2026-09-03 09:00:00'),
  ('doc_0142','tnt_0016','Счёт на оплату №140','invoice','org_00e9','ord_00s4',2,2591,'signed','usr_000y','2026-09-06 09:00:00'),
  ('doc_0143','tnt_0016','Техническое задание №449','contract','org_00bp',NULL,1,2032,'signed','usr_000s','2026-07-20 09:00:00'),
  ('doc_0144','tnt_0016','Коммерческое предложение №651','invoice','org_00ce',NULL,3,2848,'signed','usr_0013','2026-09-10 09:00:00'),
  ('doc_0145','tnt_0016','Договор оказания услуг №123','contract','org_00be',NULL,2,1391,'signed','usr_000s','2026-08-18 09:00:00'),
  ('doc_0146','tnt_0016','Договор оказания услуг №224','invoice','org_00be',NULL,3,3140,'draft','usr_000s','2026-10-04 09:00:00'),
  ('doc_0147','tnt_0016','Счёт на оплату №656','other','org_00cu','ord_00tj',1,2976,'archived','usr_000y','2026-08-16 09:00:00'),
  ('doc_0148','tnt_0016','Договор оказания услуг №979','other','org_00bp',NULL,2,354,'archived','usr_000t','2026-07-29 09:00:00'),
  ('doc_0149','tnt_0016','Коммерческое предложение №344','invoice','org_00c3',NULL,3,1874,'archived','usr_000y','2026-08-16 09:00:00'),
  ('doc_0161','tnt_0017','Акт выполненных работ №411','other','org_00eq','ord_00vy',2,1699,'sent','usr_000r','2026-07-15 09:00:00'),
  ('doc_0162','tnt_0017','Коммерческое предложение №891','invoice','org_00ev',NULL,2,611,'sent','usr_000x','2026-08-27 09:00:00'),
  ('doc_0163','tnt_0017','Коммерческое предложение №185','contract','org_00ev','ord_00vg',1,834,'archived','usr_000v','2026-07-22 09:00:00'),
  ('doc_0164','tnt_0017','Акт выполненных работ №855','contract','org_00f5','ord_00vb',2,3864,'signed','usr_000v','2026-08-27 09:00:00'),
  ('doc_0165','tnt_0017','Акт выполненных работ №949','other','org_00et',NULL,3,2691,'draft','usr_000w','2026-09-13 09:00:00');

-- automations: 8 rows
INSERT INTO automations VALUES
  ('atm_014a','tnt_0016','Назначить менеджера новому лиду','lead.created','[{"field":"status","op":"eq","value":"new"}]','[{"type":"assign_owner","strategy":"round_robin"},{"type":"notify","target":"owner"}]','active',39,'2026-10-12 09:00:00','2026-06-14 09:00:00'),
  ('atm_014b','tnt_0016','Напоминание при отсутствии ответа 2 дня','no_reply.2d','[{"field":"status","op":"eq","value":"open"}]','[{"type":"create_task","title":"Повторный контакт"},{"type":"notify","target":"owner"}]','active',84,'2026-10-12 09:00:00','2026-07-14 09:00:00'),
  ('atm_014c','tnt_0016','Сделка выиграна → создать заказ','deal.won','[{"field":"status","op":"eq","value":"won"}]','[{"type":"create_order"},{"type":"create_project"}]','active',81,'2026-10-10 09:00:00','2026-07-24 09:00:00'),
  ('atm_014d','tnt_0016','Заказ оплачен → уведомить команду','order.paid','[]','[{"type":"notify","target":"team"}]','paused',23,'2026-10-12 09:00:00','2026-08-13 09:00:00'),
  ('atm_0166','tnt_0017','Назначить менеджера новому лиду','lead.created','[{"field":"status","op":"eq","value":"new"}]','[{"type":"assign_owner","strategy":"round_robin"},{"type":"notify","target":"owner"}]','active',311,'2026-10-10 09:00:00','2026-06-14 09:00:00'),
  ('atm_0167','tnt_0017','Напоминание при отсутствии ответа 2 дня','no_reply.2d','[{"field":"status","op":"eq","value":"open"}]','[{"type":"create_task","title":"Повторный контакт"},{"type":"notify","target":"owner"}]','active',139,'2026-10-09 09:00:00','2026-07-14 09:00:00'),
  ('atm_0168','tnt_0017','Сделка выиграна → создать заказ','deal.won','[{"field":"status","op":"eq","value":"won"}]','[{"type":"create_order"},{"type":"create_project"}]','active',56,'2026-10-08 09:00:00','2026-07-24 09:00:00'),
  ('atm_0169','tnt_0017','Заказ оплачен → уведомить команду','order.paid','[]','[{"type":"notify","target":"team"}]','paused',3,'2026-10-07 09:00:00','2026-08-13 09:00:00');

-- integrations: 16 rows
INSERT INTO integrations VALUES
  ('intg_014e','tnt_0016','telegram','Telegram Bot','connected','live','{}','2026-10-11 09:00:00',NULL,'2026-08-13 09:00:00','2026-08-13 09:00:00'),
  ('intg_014f','tnt_0016','web_form','Веб-форма на сайте','connected','live','{}','2026-10-10 09:00:00',NULL,'2026-08-13 09:00:00','2026-08-13 09:00:00'),
  ('intg_014g','tnt_0016','email_smtp','Email SMTP','connected','live','{}','2026-10-11 09:00:00',NULL,'2026-08-13 09:00:00','2026-08-13 09:00:00'),
  ('intg_014h','tnt_0016','telephony','Телефония SIP','sandbox','sandbox','{}',NULL,NULL,NULL,'2026-08-13 09:00:00'),
  ('intg_014i','tnt_0016','whatsapp','WhatsApp Business','available','sandbox','{}',NULL,NULL,NULL,'2026-08-13 09:00:00'),
  ('intg_014j','tnt_0016','payments','Платёжный провайдер','sandbox','sandbox','{}',NULL,NULL,NULL,'2026-08-13 09:00:00'),
  ('intg_014k','tnt_0016','webhook','Исходящие вебхуки','connected','live','{}','2026-10-11 09:00:00',NULL,'2026-08-13 09:00:00','2026-08-13 09:00:00'),
  ('intg_014l','tnt_0016','analytics','Google Analytics','available','sandbox','{}',NULL,NULL,NULL,'2026-08-13 09:00:00'),
  ('intg_016a','tnt_0017','telegram','Telegram Bot','connected','live','{}','2026-10-10 09:00:00',NULL,'2026-08-13 09:00:00','2026-08-13 09:00:00'),
  ('intg_016b','tnt_0017','web_form','Веб-форма на сайте','connected','live','{}','2026-10-12 09:00:00',NULL,'2026-08-13 09:00:00','2026-08-13 09:00:00'),
  ('intg_016c','tnt_0017','email_smtp','Email SMTP','connected','live','{}','2026-10-12 09:00:00',NULL,'2026-08-13 09:00:00','2026-08-13 09:00:00'),
  ('intg_016d','tnt_0017','telephony','Телефония SIP','sandbox','sandbox','{}',NULL,NULL,NULL,'2026-08-13 09:00:00'),
  ('intg_016e','tnt_0017','whatsapp','WhatsApp Business','available','sandbox','{}',NULL,NULL,NULL,'2026-08-13 09:00:00'),
  ('intg_016f','tnt_0017','payments','Платёжный провайдер','sandbox','sandbox','{}',NULL,NULL,NULL,'2026-08-13 09:00:00'),
  ('intg_016g','tnt_0017','webhook','Исходящие вебхуки','connected','live','{}','2026-10-12 09:00:00',NULL,'2026-08-13 09:00:00','2026-08-13 09:00:00'),
  ('intg_016h','tnt_0017','analytics','Google Analytics','available','sandbox','{}',NULL,NULL,NULL,'2026-08-13 09:00:00');

-- webhook_events: 40 rows
INSERT INTO webhook_events VALUES
  ('whk_014m','tnt_0016','invoice.paid','{}','delivered',3,'2026-10-11 09:00:00'),
  ('whk_014n','tnt_0016','ai.call.completed','{}','delivered',3,'2026-10-05 09:00:00'),
  ('whk_014o','tnt_0016','order.confirmed','{}','delivered',1,'2026-10-10 09:00:00'),
  ('whk_014p','tnt_0016','task.completed','{}','delivered',2,'2026-10-10 09:00:00'),
  ('whk_014q','tnt_0016','invoice.paid','{}','delivered',3,'2026-09-30 09:00:00'),
  ('whk_014r','tnt_0016','ai.call.completed','{}','delivered',1,'2026-10-04 09:00:00'),
  ('whk_014s','tnt_0016','invoice.paid','{}','delivered',1,'2026-10-06 09:00:00'),
  ('whk_014t','tnt_0016','ai.call.completed','{}','delivered',2,'2026-09-30 09:00:00'),
  ('whk_014u','tnt_0016','lead.created','{}','delivered',1,'2026-10-01 09:00:00'),
  ('whk_014v','tnt_0016','deal.stage_changed','{}','delivered',1,'2026-10-04 09:00:00'),
  ('whk_014w','tnt_0016','invoice.paid','{}','delivered',1,'2026-10-05 09:00:00'),
  ('whk_014x','tnt_0016','deal.stage_changed','{}','delivered',1,'2026-10-06 09:00:00'),
  ('whk_014y','tnt_0016','deal.stage_changed','{}','delivered',3,'2026-09-29 09:00:00'),
  ('whk_014z','tnt_0016','lead.created','{}','delivered',3,'2026-09-28 09:00:00'),
  ('whk_0150','tnt_0016','ai.call.completed','{}','delivered',1,'2026-10-03 09:00:00'),
  ('whk_0151','tnt_0016','lead.created','{}','delivered',3,'2026-10-06 09:00:00'),
  ('whk_0152','tnt_0016','deal.stage_changed','{}','delivered',2,'2026-10-10 09:00:00'),
  ('whk_0153','tnt_0016','lead.created','{}','delivered',1,'2026-09-30 09:00:00'),
  ('whk_0154','tnt_0016','deal.stage_changed','{}','delivered',3,'2026-10-10 09:00:00'),
  ('whk_0155','tnt_0016','ai.call.completed','{}','delivered',2,'2026-10-07 09:00:00'),
  ('whk_016i','tnt_0017','order.confirmed','{}','delivered',2,'2026-10-02 09:00:00'),
  ('whk_016j','tnt_0017','deal.stage_changed','{}','delivered',3,'2026-09-29 09:00:00'),
  ('whk_016k','tnt_0017','invoice.paid','{}','delivered',1,'2026-10-06 09:00:00'),
  ('whk_016l','tnt_0017','order.confirmed','{}','delivered',2,'2026-10-01 09:00:00'),
  ('whk_016m','tnt_0017','deal.stage_changed','{}','delivered',3,'2026-10-04 09:00:00'),
  ('whk_016n','tnt_0017','order.confirmed','{}','delivered',3,'2026-09-30 09:00:00'),
  ('whk_016o','tnt_0017','deal.stage_changed','{}','delivered',2,'2026-10-09 09:00:00'),
  ('whk_016p','tnt_0017','invoice.paid','{}','delivered',3,'2026-10-10 09:00:00'),
  ('whk_016q','tnt_0017','task.completed','{}','failed',3,'2026-10-05 09:00:00'),
  ('whk_016r','tnt_0017','invoice.paid','{}','failed',2,'2026-10-07 09:00:00'),
  ('whk_016s','tnt_0017','ai.call.completed','{}','delivered',3,'2026-10-03 09:00:00'),
  ('whk_016t','tnt_0017','task.completed','{}','delivered',3,'2026-10-05 09:00:00'),
  ('whk_016u','tnt_0017','deal.stage_changed','{}','delivered',1,'2026-10-06 09:00:00'),
  ('whk_016v','tnt_0017','task.completed','{}','delivered',3,'2026-09-28 09:00:00'),
  ('whk_016w','tnt_0017','lead.created','{}','failed',2,'2026-09-28 09:00:00'),
  ('whk_016x','tnt_0017','deal.stage_changed','{}','delivered',3,'2026-10-08 09:00:00'),
  ('whk_016y','tnt_0017','order.confirmed','{}','delivered',3,'2026-10-01 09:00:00'),
  ('whk_016z','tnt_0017','invoice.paid','{}','delivered',3,'2026-10-08 09:00:00'),
  ('whk_0170','tnt_0017','order.confirmed','{}','delivered',3,'2026-10-02 09:00:00'),
  ('whk_0171','tnt_0017','deal.stage_changed','{}','delivered',1,'2026-10-12 09:00:00');

-- nexio_service_requests: 12 rows
INSERT INTO nexio_service_requests VALUES
  ('nsr_0156','tnt_0016','android','Разработка корпоративного сайта','Нужна оценка сроков и стоимости. Требования описаны в отдельном документе.',24458000,'UZS','in_review','low','usr_000q','usr_0013','2026-09-10 09:00:00','2026-10-08 09:00:00'),
  ('nsr_0157','tnt_0016','website','Android приложение для агентов','Нужна оценка сроков и стоимости. Требования описаны в отдельном документе.',32491000,'UZS','in_progress','normal','usr_000q','usr_000u','2026-10-12 09:00:00','2026-10-09 09:00:00'),
  ('nsr_0158','tnt_0016','crm','Подключение внешнего API','Нужна оценка сроков и стоимости. Требования описаны в отдельном документе.',37959000,'UZS','submitted','high','usr_000q','usr_000y','2026-10-11 09:00:00','2026-10-12 09:00:00'),
  ('nsr_0159','tnt_0016','api','Разработка корпоративного сайта','Нужна оценка сроков и стоимости. Требования описаны в отдельном документе.',16749000,'UZS','in_review','normal','usr_000q','usr_000w','2026-09-24 09:00:00','2026-10-09 09:00:00'),
  ('nsr_015a','tnt_0016','windows','Android приложение для агентов','Нужна оценка сроков и стоимости. Требования описаны в отдельном документе.',93338000,'UZS','in_progress','normal','usr_000q','usr_000s','2026-10-06 09:00:00','2026-10-11 09:00:00'),
  ('nsr_015b','tnt_0016','api','Кастомная интеграция CRM','Нужна оценка сроков и стоимости. Требования описаны в отдельном документе.',15175000,'UZS','in_progress','high','usr_000q','usr_000y','2026-10-09 09:00:00','2026-10-12 09:00:00'),
  ('nsr_015c','tnt_0016','windows','Разработка корпоративного сайта','Нужна оценка сроков и стоимости. Требования описаны в отдельном документе.',104759000,'UZS','accepted','low','usr_000q','usr_000r','2026-09-30 09:00:00','2026-10-10 09:00:00'),
  ('nsr_015d','tnt_0016','api','Кастомная интеграция CRM','Нужна оценка сроков и стоимости. Требования описаны в отдельном документе.',72846000,'UZS','accepted','normal','usr_000q','usr_000r','2026-08-20 09:00:00','2026-10-12 09:00:00'),
  ('nsr_015e','tnt_0016','windows','Android приложение для агентов','Нужна оценка сроков и стоимости. Требования описаны в отдельном документе.',58772000,'UZS','in_progress','normal','usr_000q','usr_000r','2026-09-21 09:00:00','2026-10-12 09:00:00'),
  ('nsr_0172','tnt_0017','windows','Разработка корпоративного сайта','Нужна оценка сроков и стоимости. Требования описаны в отдельном документе.',45021000,'UZS','in_progress','low','usr_000q','usr_000u','2026-10-07 09:00:00','2026-10-09 09:00:00'),
  ('nsr_0173','tnt_0017','windows','Android приложение для агентов','Нужна оценка сроков и стоимости. Требования описаны в отдельном документе.',16451000,'UZS','in_review','normal','usr_000q','usr_000w','2026-10-11 09:00:00','2026-10-08 09:00:00'),
  ('nsr_0174','tnt_0017','ai_agent','Кастомная интеграция CRM','Нужна оценка сроков и стоимости. Требования описаны в отдельном документе.',68638000,'UZS','in_progress','high','usr_000q','usr_000y','2026-08-13 09:00:00','2026-10-09 09:00:00');

-- ai_agents: 8 rows
INSERT INTO ai_agents VALUES
  ('aag_0175','tnt_0016','Ava — AI Sales Rep','sales','Активный продавец: квалифицирует лиды, отрабатывает возражения, назначает встречи и оформляет заказы в рамках утверждённых прайсов.','sandbox','nexio-voice-1','ru-female-1','ru','Вы — ИИ-помощник компании Acme Corp. Вы всегда честно представляетесь искусственным интеллектом, не выдаёте себя за человека и не даёте гарантий, которых нет в базе знаний.','Приветствие → выявление потребности → квалификация (бюджет, срок, ЛПР) → презентация → возражения → следующий шаг.','active','supervised','{"quiet_hours":"20:00–09:00","max_attempts":3,"do_not_call_respected":true,"disclosure_required":true,"recording_policy":"announce_and_log","max_discount_percent":10,"approval_above":50000000}','[{"title":"Каталог и прайс","kind":"catalog"},{"title":"Условия оплаты","kind":"faq"},{"title":"FAQ по внедрению","kind":"faq"}]','["create_task","create_order_draft","send_proposal","schedule_callback","update_deal_stage"]',1500,129.07,0.09,'usr_000q','2026-06-14 09:00:00','2026-10-08 09:00:00'),
  ('aag_0176','tnt_0016','Data Analyst AI','analytics','Анализирует причины отказов, конверсию по стадиям и лучшие часы дозвона. Не заявляет причинность без данных.','sandbox','nexio-voice-1','ru-male-1','ru','Вы — ИИ-помощник компании Acme Corp. Вы всегда честно представляетесь искусственным интеллектом, не выдаёте себя за человека и не даёте гарантий, которых нет в базе знаний.','Приветствие → выявление потребности → квалификация (бюджет, срок, ЛПР) → презентация → возражения → следующий шаг.','active','assisted','{"quiet_hours":"20:00–09:00","max_attempts":3,"do_not_call_respected":true,"disclosure_required":true,"recording_policy":"announce_and_log","max_discount_percent":10,"approval_above":50000000}','[{"title":"Каталог и прайс","kind":"catalog"},{"title":"Условия оплаты","kind":"faq"},{"title":"FAQ по внедрению","kind":"faq"}]','["create_task","create_order_draft","send_proposal","schedule_callback","update_deal_stage"]',1500,358.84,0.09,'usr_000q','2026-07-04 09:00:00','2026-10-08 09:00:00'),
  ('aag_0177','tnt_0016','Support Assistant','support','Первая линия поддержки: отвечает по базе знаний, создаёт тикеты и эскалирует сложные обращения.','sandbox','nexio-voice-1','uz-female-1','ru','Вы — ИИ-помощник компании Acme Corp. Вы всегда честно представляетесь искусственным интеллектом, не выдаёте себя за человека и не даёте гарантий, которых нет в базе знаний.','Приветствие → выявление потребности → квалификация (бюджет, срок, ЛПР) → презентация → возражения → следующий шаг.','active','supervised','{"quiet_hours":"20:00–09:00","max_attempts":3,"do_not_call_respected":true,"disclosure_required":true,"recording_policy":"announce_and_log","max_discount_percent":10,"approval_above":50000000}','[{"title":"Каталог и прайс","kind":"catalog"},{"title":"Условия оплаты","kind":"faq"},{"title":"FAQ по внедрению","kind":"faq"}]','["create_task","create_order_draft","send_proposal","schedule_callback","update_deal_stage"]',1500,313.05,0.09,'usr_000q','2026-09-19 09:00:00','2026-10-12 09:00:00'),
  ('aag_0178','tnt_0016','Follow-up Bot','automation','Повторные касания по «остывшим» лидам и напоминания менеджерам.','sandbox','nexio-voice-1','ru-male-1','ru','Вы — ИИ-помощник компании Acme Corp. Вы всегда честно представляетесь искусственным интеллектом, не выдаёте себя за человека и не даёте гарантий, которых нет в базе знаний.','Приветствие → выявление потребности → квалификация (бюджет, срок, ЛПР) → презентация → возражения → следующий шаг.','paused','assisted','{"quiet_hours":"20:00–09:00","max_attempts":3,"do_not_call_respected":true,"disclosure_required":true,"recording_policy":"announce_and_log","max_discount_percent":10,"approval_above":50000000}','[{"title":"Каталог и прайс","kind":"catalog"},{"title":"Условия оплаты","kind":"faq"},{"title":"FAQ по внедрению","kind":"faq"}]','["create_task","create_order_draft","send_proposal","schedule_callback","update_deal_stage"]',1500,78.82,0.09,'usr_000q','2026-06-24 09:00:00','2026-10-12 09:00:00'),
  ('aag_0179','tnt_0016','Retention Agent','sales','Работа с действующими клиентами: продление подписки, апсейл модулей.','sandbox','nexio-voice-1','ru-female-1','ru','Вы — ИИ-помощник компании Acme Corp. Вы всегда честно представляетесь искусственным интеллектом, не выдаёте себя за человека и не даёте гарантий, которых нет в базе знаний.','Приветствие → выявление потребности → квалификация (бюджет, срок, ЛПР) → презентация → возражения → следующий шаг.','draft','supervised','{"quiet_hours":"20:00–09:00","max_attempts":3,"do_not_call_respected":true,"disclosure_required":true,"recording_policy":"announce_and_log","max_discount_percent":10,"approval_above":50000000}','[{"title":"Каталог и прайс","kind":"catalog"},{"title":"Условия оплаты","kind":"faq"},{"title":"FAQ по внедрению","kind":"faq"}]','["create_task","create_order_draft","send_proposal","schedule_callback","update_deal_stage"]',1500,221.01,0.09,'usr_000q','2026-07-27 09:00:00','2026-10-10 09:00:00'),
  ('aag_017a','tnt_0017','Ava — AI Sales Rep','sales','Активный продавец: квалифицирует лиды, отрабатывает возражения, назначает встречи и оформляет заказы в рамках утверждённых прайсов.','sandbox','nexio-voice-1','uz-female-1','ru','Вы — ИИ-помощник компании Nexio Lab. Вы всегда честно представляетесь искусственным интеллектом, не выдаёте себя за человека и не даёте гарантий, которых нет в базе знаний.','Приветствие → выявление потребности → квалификация (бюджет, срок, ЛПР) → презентация → возражения → следующий шаг.','active','supervised','{"quiet_hours":"20:00–09:00","max_attempts":3,"do_not_call_respected":true,"disclosure_required":true,"recording_policy":"announce_and_log","max_discount_percent":10,"approval_above":50000000}','[{"title":"Каталог и прайс","kind":"catalog"},{"title":"Условия оплаты","kind":"faq"},{"title":"FAQ по внедрению","kind":"faq"}]','["create_task","create_order_draft","send_proposal","schedule_callback","update_deal_stage"]',1500,239.65,0.09,'usr_000q','2026-07-08 09:00:00','2026-10-09 09:00:00'),
  ('aag_017b','tnt_0017','Data Analyst AI','analytics','Анализирует причины отказов, конверсию по стадиям и лучшие часы дозвона. Не заявляет причинность без данных.','sandbox','nexio-voice-1','ru-female-1','ru','Вы — ИИ-помощник компании Nexio Lab. Вы всегда честно представляетесь искусственным интеллектом, не выдаёте себя за человека и не даёте гарантий, которых нет в базе знаний.','Приветствие → выявление потребности → квалификация (бюджет, срок, ЛПР) → презентация → возражения → следующий шаг.','active','assisted','{"quiet_hours":"20:00–09:00","max_attempts":3,"do_not_call_respected":true,"disclosure_required":true,"recording_policy":"announce_and_log","max_discount_percent":10,"approval_above":50000000}','[{"title":"Каталог и прайс","kind":"catalog"},{"title":"Условия оплаты","kind":"faq"},{"title":"FAQ по внедрению","kind":"faq"}]','["create_task","create_order_draft","send_proposal","schedule_callback","update_deal_stage"]',1500,71.97,0.09,'usr_000q','2026-08-28 09:00:00','2026-10-09 09:00:00'),
  ('aag_017c','tnt_0017','Support Assistant','support','Первая линия поддержки: отвечает по базе знаний, создаёт тикеты и эскалирует сложные обращения.','sandbox','nexio-voice-1','uz-female-1','ru','Вы — ИИ-помощник компании Nexio Lab. Вы всегда честно представляетесь искусственным интеллектом, не выдаёте себя за человека и не даёте гарантий, которых нет в базе знаний.','Приветствие → выявление потребности → квалификация (бюджет, срок, ЛПР) → презентация → возражения → следующий шаг.','active','supervised','{"quiet_hours":"20:00–09:00","max_attempts":3,"do_not_call_respected":true,"disclosure_required":true,"recording_policy":"announce_and_log","max_discount_percent":10,"approval_above":50000000}','[{"title":"Каталог и прайс","kind":"catalog"},{"title":"Условия оплаты","kind":"faq"},{"title":"FAQ по внедрению","kind":"faq"}]','["create_task","create_order_draft","send_proposal","schedule_callback","update_deal_stage"]',1500,191.5,0.09,'usr_000q','2026-09-19 09:00:00','2026-10-10 09:00:00');

-- ai_campaigns: 6 rows
INSERT INTO ai_campaigns VALUES
  ('acp_017d','tnt_0016','aag_0175','Q3 Возврат «остывших» лидов','Квалифицировать контакты и назначить встречу с менеджером','running',735,529,299,44,18,'{"days":["mon","tue","wed","thu","fri"],"from":"10:00","to":"18:00","concurrency":5}','2026-09-30 09:00:00','2026-06-16 09:00:00'),
  ('acp_017e','tnt_0016','aag_0177','Лиды с сайта — быстрый контакт','Квалифицировать контакты и назначить встречу с менеджером','draft',780,687,536,106,32,'{"days":["mon","tue","wed","thu","fri"],"from":"10:00","to":"18:00","concurrency":5}','2026-09-15 09:00:00','2026-08-20 09:00:00'),
  ('acp_017f','tnt_0016','aag_0178','Продление подписок (retention)','Квалифицировать контакты и назначить встречу с менеджером','paused',222,126,72,19,4,'{"days":["mon","tue","wed","thu","fri"],"from":"10:00","to":"18:00","concurrency":5}','2026-08-20 09:00:00','2026-08-22 09:00:00'),
  ('acp_017g','tnt_0016','aag_0176','Промо тарифа Business','Квалифицировать контакты и назначить встречу с менеджером','completed',618,302,215,35,13,'{"days":["mon","tue","wed","thu","fri"],"from":"10:00","to":"18:00","concurrency":5}','2026-07-21 09:00:00','2026-09-26 09:00:00'),
  ('acp_017h','tnt_0017','aag_017a','Пилот: обзвон входящих','Квалифицировать контакты и назначить встречу с менеджером','running',517,372,274,80,16,'{"days":["mon","tue","wed","thu","fri"],"from":"10:00","to":"18:00","concurrency":5}','2026-09-30 09:00:00','2026-08-04 09:00:00'),
  ('acp_017i','tnt_0017','aag_017b','Напоминания о демо','Квалифицировать контакты и назначить встречу с менеджером','completed',670,595,305,87,18,'{"days":["mon","tue","wed","thu","fri"],"from":"10:00","to":"18:00","concurrency":5}','2026-09-04 09:00:00','2026-07-21 09:00:00');

-- ai_calls: 310 rows
INSERT INTO ai_calls VALUES
  ('acl_017j','tnt_0016','aag_0176','acp_017d','con_00da','org_00b1',NULL,NULL,'outbound','+998 95 521 87 55','completed','human_handoff',108,0.16,'consent.calls = true (зафиксировано при сборе заявки)',1,1,1,0.35,'Запросил связь с человеком, переведён на менеджера.','[ИИ] Здравствуйте, это ИИ-помощник компании Acme Corp. Звонок записывается для контроля качества.
[Клиент] Да, слушаю.
[ИИ] Удобно обсудить внедрение CRM на 2 минуты?
[Клиент] Давайте, расскажите.
[ИИ] Отлично, задам несколько уточняющих вопросов...',1,'2026-09-25 22:00:00','2026-09-25 22:01:48','2026-09-25 22:00:00'),
  ('acl_017l','tnt_0016','aag_0177','acp_017g','con_00c4','org_00bh',NULL,'ord_00tt','outbound','+998 75 253 37 56','completed','interested',119,0.18,'consent.calls = true (зафиксировано при сборе заявки)',1,1,1,0.89,'Клиент заинтересовался тарифом Business, запросил коммерческое предложение.','[ИИ] Здравствуйте, это ИИ-помощник компании Acme Corp. Звонок записывается для контроля качества.
[Клиент] Да, слушаю.
[ИИ] Удобно обсудить внедрение CRM на 2 минуты?
[Клиент] Давайте, расскажите.
[ИИ] Отлично, задам несколько уточняющих вопросов...',1,'2026-09-09 04:00:00','2026-09-09 04:01:59','2026-09-09 04:00:00'),
  ('acl_017n','tnt_0016','aag_0176','acp_017g','con_00cz','org_00d7',NULL,NULL,'outbound','+998 94 352 94 78','completed','opted_out',207,0.31,'consent.calls = true (зафиксировано при сборе заявки)',1,1,1,0.48,'Клиент запросил исключение из обзвона — добавлен в suppression list.','[ИИ] Здравствуйте, это ИИ-помощник компании Acme Corp. Звонок записывается для контроля качества.
[Клиент] Да, слушаю.
[ИИ] Удобно обсудить внедрение CRM на 2 минуты?
[Клиент] Давайте, расскажите.
[ИИ] Отлично, задам несколько уточняющих вопросов...',1,'2026-10-04 06:00:00','2026-10-04 06:03:27','2026-10-04 06:00:00'),
  ('acl_017p','tnt_0016','aag_0176','acp_017g','con_00cd','org_00dp',NULL,NULL,'outbound','+998 83 831 77 57','completed','interested',310,0.47,'consent.calls = true (зафиксировано при сборе заявки)',1,1,1,0.93,'Клиент заинтересовался тарифом Business, запросил коммерческое предложение.','[ИИ] Здравствуйте, это ИИ-помощник компании Acme Corp. Звонок записывается для контроля качества.
[Клиент] Да, слушаю.
[ИИ] Удобно обсудить внедрение CRM на 2 минуты?
[Клиент] Давайте, расскажите.
[ИИ] Отлично, задам несколько уточняющих вопросов...',1,'2026-09-25 20:00:00','2026-09-25 20:05:10','2026-09-25 20:00:00'),
  ('acl_017r','tnt_0016','aag_0179','acp_017e','con_00cr','org_00ci',NULL,NULL,'outbound','+998 78 916 93 17','completed','opted_out',338,0.51,'consent.calls = true (зафиксировано при сборе заявки)',1,0,0,0.46,'Клиент запросил исключение из обзвона — добавлен в suppression list.','[ИИ] Здравствуйте, это ИИ-помощник компании Acme Corp. Звонок записывается для контроля качества.
[Клиент] Да, слушаю.
[ИИ] Удобно обсудить внедрение CRM на 2 минуты?
[Клиент] Давайте, расскажите.
[ИИ] Отлично, задам несколько уточняющих вопросов...',1,'2026-09-07 03:00:00','2026-09-07 03:05:38','2026-09-07 03:00:00'),
  ('acl_017t','tnt_0016','aag_0177','acp_017g','con_00cw','org_00b6',NULL,NULL,'outbound','+998 89 151 47 99','completed','objection',233,0.35,'consent.calls = true (зафиксировано при сборе заявки)',1,0,0,0.62,'Возражение по цене. Агент привёл сравнение с текущими расходами, клиент попросил паузу.','[ИИ] Здравствуйте, это ИИ-помощник компании Acme Corp. Звонок записывается для контроля качества.
[Клиент] Да, слушаю.
[ИИ] Удобно обсудить внедрение CRM на 2 минуты?
[Клиент] Давайте, расскажите.
[ИИ] Отлично, задам несколько уточняющих вопросов...',1,'2026-10-04 06:00:00','2026-10-04 06:03:53','2026-10-04 06:00:00'),
  ('acl_017v','tnt_0016','aag_0176','acp_017g','con_00ee','org_00bp',NULL,NULL,'outbound','+998 75 110 44 42','completed','no_answer',19,0.03,'consent.calls = true (зафиксировано при сборе заявки)',1,0,0,0.51,'Нет ответа после 4 гудков, назначен повторный дозвон.',NULL,1,'2026-09-20 21:00:00','2026-09-20 21:00:19','2026-09-20 21:00:00'),
  ('acl_017w','tnt_0016','aag_0179','acp_017e','con_00ay','org_00b6',NULL,NULL,'outbound','+998 75 102 66 75','failed','qualified',236,0.35,'consent.calls = true (зафиксировано при сборе заявки)',1,1,1,0.78,'Квалифицирован: есть бюджет, потребность в интеграции и ЛПР на линии.','[ИИ] Здравствуйте, это ИИ-помощник компании Acme Corp. Звонок записывается для контроля качества.
[Клиент] Да, слушаю.
[ИИ] Удобно обсудить внедрение CRM на 2 минуты?
[Клиент] Давайте, расскажите.
[ИИ] Отлично, задам несколько уточняющих вопросов...',1,'2026-10-02 21:00:00','2026-10-02 21:03:56','2026-10-02 21:00:00'),
  ('acl_017y','tnt_0016','aag_0177','acp_017d','con_00dm','org_00be',NULL,NULL,'outbound','+998 90 368 75 18','completed','interested',382,0.57,'consent.calls = true (зафиксировано при сборе заявки)',1,1,1,0.85,'Клиент заинтересовался тарифом Business, запросил коммерческое предложение.','[ИИ] Здравствуйте, это ИИ-помощник компании Acme Corp. Звонок записывается для контроля качества.
[Клиент] Да, слушаю.
[ИИ] Удобно обсудить внедрение CRM на 2 минуты?
[Клиент] Давайте, расскажите.
[ИИ] Отлично, задам несколько уточняющих вопросов...',1,'2026-10-02 19:00:00','2026-10-02 19:06:22','2026-10-02 19:00:00'),
  ('acl_0180','tnt_0016','aag_0179','acp_017d','con_00do','org_00dp',NULL,NULL,'outbound','+998 70 316 20 78','completed','not_interested',329,0.49,'consent.calls = true (зафиксировано при сборе заявки)',1,0,0,0.6,'Клиент сообщил об отсутствии потребности на текущий момент.','[ИИ] Здравствуйте, это ИИ-помощник компании Acme Corp. Звонок записывается для контроля качества.
[Клиент] Да, слушаю.
[ИИ] Удобно обсудить внедрение CRM на 2 минуты?
[Клиент] Нам это сейчас не нужно.
[ИИ] Отлично, задам несколько уточняющих вопросов...',1,'2026-09-26 20:00:00','2026-09-26 20:05:29','2026-09-26 20:00:00'),
  ('acl_0182','tnt_0016','aag_0178','acp_017f','con_00ba','org_00au','deal_00iy',NULL,'outbound','+998 80 713 41 13','completed','not_interested',411,0.62,'consent.calls = true (зафиксировано при сборе заявки)',1,0,0,0.51,'Клиент сообщил об отсутствии потребности на текущий момент.','[ИИ] Здравствуйте, это ИИ-помощник компании Acme Corp. Звонок записывается для контроля качества.
[Клиент] Да, слушаю.
[ИИ] Удобно обсудить внедрение CRM на 2 минуты?
[Клиент] Нам это сейчас не нужно.
[ИИ] Отлично, задам несколько уточняющих вопросов...',1,'2026-08-15 05:00:00','2026-08-15 05:06:51','2026-08-15 05:00:00'),
  ('acl_0184','tnt_0016','aag_0175','acp_017e','con_00ds','org_00c6',NULL,NULL,'outbound','+998 72 561 81 53','completed','qualified',273,0.41,'consent.calls = true (зафиксировано при сборе заявки)',1,0,0,0.71,'Квалифицирован: есть бюджет, потребность в интеграции и ЛПР на линии.','[ИИ] Здравствуйте, это ИИ-помощник компании Acme Corp. Звонок записывается для контроля качества.
[Клиент] Да, слушаю.
[ИИ] Удобно обсудить внедрение CRM на 2 минуты?
[Клиент] Давайте, расскажите.
[ИИ] Отлично, задам несколько уточняющих вопросов...',1,'2026-09-19 23:00:00','2026-09-19 23:04:33','2026-09-19 23:00:00'),
  ('acl_0186','tnt_0016','aag_0175','acp_017e','con_00dt','org_00d7','deal_00kk',NULL,'outbound','+998 85 635 65 65','completed','no_answer',20,0.03,'consent.calls = true (зафиксировано при сборе заявки)',1,0,0,0.57,'Нет ответа после 4 гудков, назначен повторный дозвон.',NULL,1,'2026-09-15 01:00:00','2026-09-15 01:00:20','2026-09-15 01:00:00'),
  ('acl_0187','tnt_0016','aag_0176','acp_017d','con_00ef','org_00d7',NULL,NULL,'outbound','+998 95 459 63 94','completed','objection',80,0.12,'consent.calls = true (зафиксировано при сборе заявки)',1,1,1,0.62,'Возражение по цене. Агент привёл сравнение с текущими расходами, клиент попросил паузу.','[ИИ] Здравствуйте, это ИИ-помощник компании Acme Corp. Звонок записывается для контроля качества.
[Клиент] Да, слушаю.
[ИИ] Удобно обсудить внедрение CRM на 2 минуты?
[Клиент] Давайте, расскажите.
[ИИ] Отлично, задам несколько уточняющих вопросов...',1,'2026-09-22 13:00:00','2026-09-22 13:01:20','2026-09-22 13:00:00'),
  ('acl_0189','tnt_0016','aag_0177','acp_017d','con_00bt','org_00cn',NULL,'ord_00rt','outbound','+998 72 366 23 85','completed','interested',240,0.36,'consent.calls = true (зафиксировано при сборе заявки)',1,1,1,0.83,'Клиент заинтересовался тарифом Business, запросил коммерческое предложение.','[ИИ] Здравствуйте, это ИИ-помощник компании Acme Corp. Звонок записывается для контроля качества.
[Клиент] Да, слушаю.
[ИИ] Удобно обсудить внедрение CRM на 2 минуты?
[Клиент] Давайте, расскажите.
[ИИ] Отлично, задам несколько уточняющих вопросов...',1,'2026-09-06 05:00:00','2026-09-06 05:04:00','2026-09-06 05:00:00'),
  ('acl_018b','tnt_0016','aag_0177',NULL,'con_00c8','org_00ci','deal_00i5',NULL,'outbound','+998 89 782 66 64','failed','not_interested',383,0.57,'consent.calls = true (зафиксировано при сборе заявки)',1,1,1,0.46,'Клиент сообщил об отсутствии потребности на текущий момент.','[ИИ] Здравствуйте, это ИИ-помощник компании Acme Corp. Звонок записывается для контроля качества.
[Клиент] Да, слушаю.
[ИИ] Удобно обсудить внедрение CRM на 2 минуты?
[Клиент] Нам это сейчас не нужно.
[ИИ] Отлично, задам несколько уточняющих вопросов...',1,'2026-09-24 06:00:00','2026-09-24 06:06:23','2026-09-24 06:00:00'),
  ('acl_018d','tnt_0016','aag_0179',NULL,'con_00df','org_00c6','deal_00i1',NULL,'outbound','+998 72 244 66 75','completed','no_answer',9,0.01,'consent.calls = true (зафиксировано при сборе заявки)',0,0,0,0.42,'Нет ответа после 4 гудков, назначен повторный дозвон.',NULL,1,'2026-09-29 16:00:00','2026-09-29 16:00:09','2026-09-29 16:00:00'),
  ('acl_018e','tnt_0016','aag_0178','acp_017e','con_00d0','org_00dh',NULL,NULL,'outbound','+998 99 559 26 77','completed','no_answer',11,0.02,'consent.calls = true (зафиксировано при сборе заявки)',1,0,1,0.57,'Нет ответа после 4 гудков, назначен повторный дозвон.',NULL,1,'2026-09-29 19:00:00','2026-09-29 19:00:11','2026-09-29 19:00:00'),
  ('acl_018f','tnt_0016','aag_0179','acp_017d','con_00dj','org_00bp',NULL,NULL,'outbound','+998 82 721 16 97','completed','objection',221,0.33,'consent.calls = true (зафиксировано при сборе заявки)',1,1,1,0.46,'Возражение по цене. Агент привёл сравнение с текущими расходами, клиент попросил паузу.','[ИИ] Здравствуйте, это ИИ-помощник компании Acme Corp. Звонок записывается для контроля качества.
[Клиент] Да, слушаю.
[ИИ] Удобно обсудить внедрение CRM на 2 минуты?
[Клиент] Давайте, расскажите.
[ИИ] Отлично, задам несколько уточняющих вопросов...',1,'2026-08-31 03:00:00','2026-08-31 03:03:41','2026-08-31 03:00:00'),
  ('acl_018h','tnt_0016','aag_0175','acp_017d','con_00d1','org_00c0',NULL,NULL,'outbound','+998 80 312 20 70','completed','objection',228,0.34,'consent.calls = true (зафиксировано при сборе заявки)',1,1,1,0.37,'Возражение по цене. Агент привёл сравнение с текущими расходами, клиент попросил паузу.','[ИИ] Здравствуйте, это ИИ-помощник компании Acme Corp. Звонок записывается для контроля качества.
[Клиент] Да, слушаю.
[ИИ] Удобно обсудить внедрение CRM на 2 минуты?
[Клиент] Давайте, расскажите.
[ИИ] Отлично, задам несколько уточняющих вопросов...',1,'2026-09-19 09:00:00','2026-09-19 09:03:48','2026-09-19 09:00:00'),
  ('acl_018j','tnt_0016','aag_0179','acp_017d','con_00cp','org_00c6',NULL,NULL,'outbound','+998 70 170 96 38','completed','not_interested',303,0.45,'consent.calls = true (зафиксировано при сборе заявки)',1,1,1,0.33,'Клиент сообщил об отсутствии потребности на текущий момент.','[ИИ] Здравствуйте, это ИИ-помощник компании Acme Corp. Звонок записывается для контроля качества.
[Клиент] Да, слушаю.
[ИИ] Удобно обсудить внедрение CRM на 2 минуты?
[Клиент] Нам это сейчас не нужно.
[ИИ] Отлично, задам несколько уточняющих вопросов...',1,'2026-08-29 16:00:00','2026-08-29 16:05:03','2026-08-29 16:00:00'),
  ('acl_018l','tnt_0016','aag_0177','acp_017g','con_00bc','org_00ci',NULL,NULL,'outbound','+998 90 564 69 13','completed','no_answer',25,0.04,'consent.calls = true (зафиксировано при сборе заявки)',1,0,0,0.5,'Нет ответа после 4 гудков, назначен повторный дозвон.',NULL,1,'2026-09-25 02:00:00','2026-09-25 02:00:25','2026-09-25 02:00:00'),
  ('acl_018m','tnt_0016','aag_0177','acp_017g','con_00d0','org_00bm','deal_00kk',NULL,'outbound','+998 99 927 20 16','completed','not_interested',138,0.21,'consent.calls = true (зафиксировано при сборе заявки)',1,1,1,0.54,'Клиент сообщил об отсутствии потребности на текущий момент.','[ИИ] Здравствуйте, это ИИ-помощник компании Acme Corp. Звонок записывается для контроля качества.
[Клиент] Да, слушаю.
[ИИ] Удобно обсудить внедрение CRM на 2 минуты?
[Клиент] Нам это сейчас не нужно.
[ИИ] Отлично, задам несколько уточняющих вопросов...',1,'2026-08-14 23:00:00','2026-08-14 23:02:18','2026-08-14 23:00:00'),
  ('acl_018o','tnt_0016','aag_0179','acp_017e','con_00c2','org_00dx',NULL,NULL,'outbound','+998 94 993 22 29','completed','interested',414,0.62,'consent.calls = true (зафиксировано при сборе заявки)',1,1,1,0.85,'Клиент заинтересовался тарифом Business, запросил коммерческое предложение.','[ИИ] Здравствуйте, это ИИ-помощник компании Acme Corp. Звонок записывается для контроля качества.
[Клиент] Да, слушаю.
[ИИ] Удобно обсудить внедрение CRM на 2 минуты?
[Клиент] Давайте, расскажите.
[ИИ] Отлично, задам несколько уточняющих вопросов...',1,'2026-10-01 19:00:00','2026-10-01 19:06:54','2026-10-01 19:00:00'),
  ('acl_018q','tnt_0016','aag_0175','acp_017e','con_00ca','org_00dp',NULL,NULL,'outbound','+998 96 565 45 86','completed','qualified',164,0.25,'consent.calls = true (зафиксировано при сборе заявки)',1,1,1,0.89,'Квалифицирован: есть бюджет, потребность в интеграции и ЛПР на линии.','[ИИ] Здравствуйте, это ИИ-помощник компании Acme Corp. Звонок записывается для контроля качества.
[Клиент] Да, слушаю.
[ИИ] Удобно обсудить внедрение CRM на 2 минуты?
[Клиент] Давайте, расскажите.
[ИИ] Отлично, задам несколько уточняющих вопросов...',1,'2026-09-03 16:00:00','2026-09-03 16:02:44','2026-09-03 16:00:00'),
  ('acl_018s','tnt_0016','aag_0178','acp_017g','con_00bo','org_00e2','deal_00ll',NULL,'outbound','+998 85 357 36 71','completed','no_answer',12,0.02,'consent.calls = true (зафиксировано при сборе заявки)',1,0,1,0.55,'Нет ответа после 4 гудков, назначен повторный дозвон.',NULL,1,'2026-09-14 06:00:00','2026-09-14 06:00:12','2026-09-14 06:00:00'),
  ('acl_018t','tnt_0016','aag_0177','acp_017e','con_00b0','org_00du',NULL,NULL,'outbound','+998 71 991 25 65','completed','qualified',206,0.31,'consent.calls = true (зафиксировано при сборе заявки)',1,0,0,0.81,'Квалифицирован: есть бюджет, потребность в интеграции и ЛПР на линии.','[ИИ] Здравствуйте, это ИИ-помощник компании Acme Corp. Звонок записывается для контроля качества.
[Клиент] Да, слушаю.
[ИИ] Удобно обсудить внедрение CRM на 2 минуты?
[Клиент] Давайте, расскажите.
[ИИ] Отлично, задам несколько уточняющих вопросов...',1,'2026-09-29 19:00:00','2026-09-29 19:03:26','2026-09-29 19:00:00'),
  ('acl_018v','tnt_0016','aag_0179','acp_017d','con_00dt','org_00d3',NULL,NULL,'outbound','+998 88 519 59 16','completed','not_interested',101,0.15,'consent.calls = true (зафиксировано при сборе заявки)',1,0,1,0.42,'Клиент сообщил об отсутствии потребности на текущий момент.','[ИИ] Здравствуйте, это ИИ-помощник компании Acme Corp. Звонок записывается для контроля качества.
[Клиент] Да, слушаю.
[ИИ] Удобно обсудить внедрение CRM на 2 минуты?
[Клиент] Нам это сейчас не нужно.
[ИИ] Отлично, задам несколько уточняющих вопросов...',1,'2026-08-15 09:00:00','2026-08-15 09:01:41','2026-08-15 09:00:00'),
  ('acl_018x','tnt_0016','aag_0175','acp_017e','con_00bt','org_00cn','deal_00hp',NULL,'outbound','+998 94 898 69 82','completed','objection',337,0.51,'consent.calls = true (зафиксировано при сборе заявки)',1,1,1,0.5,'Возражение по цене. Агент привёл сравнение с текущими расходами, клиент попросил паузу.','[ИИ] Здравствуйте, это ИИ-помощник компании Acme Corp. Звонок записывается для контроля качества.
[Клиент] Да, слушаю.
[ИИ] Удобно обсудить внедрение CRM на 2 минуты?
[Клиент] Давайте, расскажите.
[ИИ] Отлично, задам несколько уточняющих вопросов...',1,'2026-09-02 23:00:00','2026-09-02 23:05:37','2026-09-02 23:00:00'),
  ('acl_018z','tnt_0016','aag_0178','acp_017d','con_00br','org_00bx',NULL,'ord_00uu','outbound','+998 82 521 95 23','completed','interested',238,0.36,'consent.calls = true (зафиксировано при сборе заявки)',1,1,1,0.87,'Клиент заинтересовался тарифом Business, запросил коммерческое предложение.','[ИИ] Здравствуйте, это ИИ-помощник компании Acme Corp. Звонок записывается для контроля качества.
[Клиент] Да, слушаю.
[ИИ] Удобно обсудить внедрение CRM на 2 минуты?
[Клиент] Давайте, расскажите.
[ИИ] Отлично, задам несколько уточняющих вопросов...',1,'2026-10-07 17:00:00','2026-10-07 17:03:58','2026-10-07 17:00:00'),
  ('acl_0191','tnt_0016','aag_0178',NULL,'con_00cz','org_00cb',NULL,NULL,'outbound','+998 86 347 53 26','completed','objection',135,0.2,'consent.calls = true (зафиксировано при сборе заявки)',1,1,1,0.6,'Возражение по цене. Агент привёл сравнение с текущими расходами, клиент попросил паузу.','[ИИ] Здравствуйте, это ИИ-помощник компании Acme Corp. Звонок записывается для контроля качества.
[Клиент] Да, слушаю.
[ИИ] Удобно обсудить внедрение CRM на 2 минуты?
[Клиент] Давайте, расскажите.
[ИИ] Отлично, задам несколько уточняющих вопросов...',1,'2026-09-22 09:00:00','2026-09-22 09:02:15','2026-09-22 09:00:00'),
  ('acl_0193','tnt_0016','aag_0178',NULL,'con_00bk','org_00bp','deal_00kg',NULL,'outbound','+998 98 990 41 98','completed','voicemail',13,0.02,'consent.calls = true (зафиксировано при сборе заявки)',1,0,1,0.36,'Ответил автоответчик, оставлено голосовое сообщение.',NULL,1,'2026-08-13 03:00:00','2026-08-13 03:00:13','2026-08-13 03:00:00'),
  ('acl_0194','tnt_0016','aag_0177','acp_017g','con_00eb','org_00du','deal_00iu',NULL,'outbound','+998 93 299 27 64','completed','voicemail',4,0.01,'consent.calls = true (зафиксировано при сборе заявки)',0,0,1,0.61,'Ответил автоответчик, оставлено голосовое сообщение.',NULL,1,'2026-09-17 09:00:00','2026-09-17 09:00:04','2026-09-17 09:00:00'),
  ('acl_0195','tnt_0016','aag_0177','acp_017d','con_00dz','org_00ci',NULL,NULL,'outbound','+998 91 334 37 21','completed','interested',398,0.6,'consent.calls = true (зафиксировано при сборе заявки)',1,0,0,0.9,'Клиент заинтересовался тарифом Business, запросил коммерческое предложение.','[ИИ] Здравствуйте, это ИИ-помощник компании Acme Corp. Звонок записывается для контроля качества.
[Клиент] Да, слушаю.
[ИИ] Удобно обсудить внедрение CRM на 2 минуты?
[Клиент] Давайте, расскажите.
[ИИ] Отлично, задам несколько уточняющих вопросов...',1,'2026-10-09 06:00:00','2026-10-09 06:06:38','2026-10-09 06:00:00'),
  ('acl_0197','tnt_0016','aag_0177','acp_017d','con_00c5','org_00bp','deal_00hv','ord_00ug','outbound','+998 97 566 15 70','completed','interested',84,0.13,'consent.calls = true (зафиксировано при сборе заявки)',1,1,1,0.89,'Клиент заинтересовался тарифом Business, запросил коммерческое предложение.','[ИИ] Здравствуйте, это ИИ-помощник компании Acme Corp. Звонок записывается для контроля качества.
[Клиент] Да, слушаю.
[ИИ] Удобно обсудить внедрение CRM на 2 минуты?
[Клиент] Давайте, расскажите.
[ИИ] Отлично, задам несколько уточняющих вопросов...',1,'2026-09-20 13:00:00','2026-09-20 13:01:24','2026-09-20 13:00:00'),
  ('acl_0199','tnt_0016','aag_0176','acp_017e','con_00cw','org_00e9',NULL,NULL,'outbound','+998 87 369 98 11','completed','not_interested',326,0.49,'consent.calls = true (зафиксировано при сборе заявки)',1,0,1,0.47,'Клиент сообщил об отсутствии потребности на текущий момент.','[ИИ] Здравствуйте, это ИИ-помощник компании Acme Corp. Звонок записывается для контроля качества.
[Клиент] Да, слушаю.
[ИИ] Удобно обсудить внедрение CRM на 2 минуты?
[Клиент] Нам это сейчас не нужно.
[ИИ] Отлично, задам несколько уточняющих вопросов...',1,'2026-10-09 09:00:00','2026-10-09 09:05:26','2026-10-09 09:00:00'),
  ('acl_019b','tnt_0016','aag_0179','acp_017e','con_00d0','org_00ed',NULL,NULL,'outbound','+998 87 937 66 31','completed','no_answer',4,0.01,'consent.calls = true (зафиксировано при сборе заявки)',0,0,0,0.61,'Нет ответа после 4 гудков, назначен повторный дозвон.',NULL,1,'2026-08-25 17:00:00','2026-08-25 17:00:04','2026-08-25 17:00:00'),
  ('acl_019c','tnt_0016','aag_0177','acp_017f','con_00cx','org_00ei','deal_00k3',NULL,'outbound','+998 75 697 75 67','completed','voicemail',0,0,'consent.calls = true (зафиксировано при сборе заявки)',0,0,1,0.44,'Ответил автоответчик, оставлено голосовое сообщение.',NULL,1,'2026-09-14 00:00:00','2026-09-14 00:00:00','2026-09-14 00:00:00'),
  ('acl_019d','tnt_0016','aag_0175','acp_017g','con_00bk','org_00bp','deal_00gp',NULL,'outbound','+998 75 748 92 21','completed','voicemail',25,0.04,'consent.calls = true (зафиксировано при сборе заявки)',1,0,0,0.45,'Ответил автоответчик, оставлено голосовое сообщение.',NULL,1,'2026-10-02 00:00:00','2026-10-02 00:00:25','2026-10-02 00:00:00'),
  ('acl_019e','tnt_0016','aag_0179','acp_017e','con_00ct','org_00dx','deal_00lh',NULL,'outbound','+998 73 597 45 52','completed','no_answer',11,0.02,'consent.calls = true (зафиксировано при сборе заявки)',1,0,1,0.66,'Нет ответа после 4 гудков, назначен повторный дозвон.',NULL,1,'2026-10-01 15:00:00','2026-10-01 15:00:11','2026-10-01 15:00:00'),
  ('acl_019f','tnt_0016','aag_0177','acp_017g','con_00dd','org_00bp',NULL,NULL,'outbound','+998 78 316 53 99','completed','interested',325,0.49,'consent.calls = true (зафиксировано при сборе заявки)',1,0,0,0.87,'Клиент заинтересовался тарифом Business, запросил коммерческое предложение.','[ИИ] Здравствуйте, это ИИ-помощник компании Acme Corp. Звонок записывается для контроля качества.
[Клиент] Да, слушаю.
[ИИ] Удобно обсудить внедрение CRM на 2 минуты?
[Клиент] Давайте, расскажите.
[ИИ] Отлично, задам несколько уточняющих вопросов...',1,'2026-08-30 14:00:00','2026-08-30 14:05:25','2026-08-30 14:00:00'),
  ('acl_019i','tnt_0016','aag_0178','acp_017e','con_00bv','org_00c3',NULL,NULL,'outbound','+998 87 909 93 59','completed','human_handoff',221,0.33,'consent.calls = true (зафиксировано при сборе заявки)',1,1,1,0.36,'Запросил связь с человеком, переведён на менеджера.','[ИИ] Здравствуйте, это ИИ-помощник компании Acme Corp. Звонок записывается для контроля качества.
[Клиент] Да, слушаю.
[ИИ] Удобно обсудить внедрение CRM на 2 минуты?
[Клиент] Давайте, расскажите.
[ИИ] Отлично, задам несколько уточняющих вопросов...',1,'2026-09-07 16:00:00','2026-09-07 16:03:41','2026-09-07 16:00:00'),
  ('acl_019k','tnt_0016','aag_0179','acp_017f','con_00d8','org_00e6',NULL,NULL,'outbound','+998 96 218 60 11','completed','qualified',161,0.24,'consent.calls = true (зафиксировано при сборе заявки)',1,0,1,0.76,'Квалифицирован: есть бюджет, потребность в интеграции и ЛПР на линии.','[ИИ] Здравствуйте, это ИИ-помощник компании Acme Corp. Звонок записывается для контроля качества.
[Клиент] Да, слушаю.
[ИИ] Удобно обсудить внедрение CRM на 2 минуты?
[Клиент] Давайте, расскажите.
[ИИ] Отлично, задам несколько уточняющих вопросов...',1,'2026-09-25 14:00:00','2026-09-25 14:02:41','2026-09-25 14:00:00'),
  ('acl_019m','tnt_0016','aag_0175','acp_017d','con_00dn','org_00dc','deal_00gx',NULL,'outbound','+998 73 746 78 83','completed','voicemail',10,0.02,'consent.calls = true (зафиксировано при сборе заявки)',0,0,1,0.5,'Ответил автоответчик, оставлено голосовое сообщение.',NULL,1,'2026-10-09 04:00:00','2026-10-09 04:00:10','2026-10-09 04:00:00'),
  ('acl_019n','tnt_0016','aag_0175','acp_017f','con_00dm','org_00b9',NULL,NULL,'outbound','+998 79 901 78 21','completed','voicemail',14,0.02,'consent.calls = true (зафиксировано при сборе заявки)',1,0,1,0.69,'Ответил автоответчик, оставлено голосовое сообщение.',NULL,1,'2026-08-17 05:00:00','2026-08-17 05:00:14','2026-08-17 05:00:00'),
  ('acl_019o','tnt_0016','aag_0176','acp_017d','con_00ee','org_00ei',NULL,NULL,'outbound','+998 79 959 56 92','completed','opted_out',395,0.59,'consent.calls = true (зафиксировано при сборе заявки)',1,1,1,0.68,'Клиент запросил исключение из обзвона — добавлен в suppression list.','[ИИ] Здравствуйте, это ИИ-помощник компании Acme Corp. Звонок записывается для контроля качества.
[Клиент] Да, слушаю.
[ИИ] Удобно обсудить внедрение CRM на 2 минуты?
[Клиент] Давайте, расскажите.
[ИИ] Отлично, задам несколько уточняющих вопросов...',1,'2026-10-07 20:00:00','2026-10-07 20:06:35','2026-10-07 20:00:00'),
  ('acl_019q','tnt_0016','aag_0177','acp_017g','con_00c9','org_00b1','deal_00ic',NULL,'outbound','+998 96 116 26 40','completed','qualified',411,0.62,'consent.calls = true (зафиксировано при сборе заявки)',1,1,1,0.76,'Квалифицирован: есть бюджет, потребность в интеграции и ЛПР на линии.','[ИИ] Здравствуйте, это ИИ-помощник компании Acme Corp. Звонок записывается для контроля качества.
[Клиент] Да, слушаю.
[ИИ] Удобно обсудить внедрение CRM на 2 минуты?
[Клиент] Давайте, расскажите.
[ИИ] Отлично, задам несколько уточняющих вопросов...',1,'2026-08-12 13:00:00','2026-08-12 13:06:51','2026-08-12 13:00:00'),
  ('acl_019s','tnt_0016','aag_0175','acp_017f','con_00dv','org_00dk',NULL,NULL,'outbound','+998 95 986 53 65','completed','objection',403,0.6,'consent.calls = true (зафиксировано при сборе заявки)',1,1,1,0.37,'Возражение по цене. Агент привёл сравнение с текущими расходами, клиент попросил паузу.','[ИИ] Здравствуйте, это ИИ-помощник компании Acme Corp. Звонок записывается для контроля качества.
[Клиент] Да, слушаю.
[ИИ] Удобно обсудить внедрение CRM на 2 минуты?
[Клиент] Давайте, расскажите.
[ИИ] Отлично, задам несколько уточняющих вопросов...',1,'2026-09-14 23:00:00','2026-09-14 23:06:43','2026-09-14 23:00:00'),
  ('acl_019u','tnt_0016','aag_0179','acp_017e','con_00d0','org_00bm','deal_00gg',NULL,'outbound','+998 82 982 73 58','completed','voicemail',22,0.03,'consent.calls = true (зафиксировано при сборе заявки)',1,0,0,0.62,'Ответил автоответчик, оставлено голосовое сообщение.',NULL,1,'2026-09-27 23:00:00','2026-09-27 23:00:22','2026-09-27 23:00:00'),
  ('acl_019v','tnt_0016','aag_0176',NULL,'con_00bf','org_00bp','deal_00fr',NULL,'outbound','+998 85 524 86 81','completed','opted_out',135,0.2,'consent.calls = true (зафиксировано при сборе заявки)',1,1,1,0.46,'Клиент запросил исключение из обзвона — добавлен в suppression list.','[ИИ] Здравствуйте, это ИИ-помощник компании Acme Corp. Звонок записывается для контроля качества.
[Клиент] Да, слушаю.
[ИИ] Удобно обсудить внедрение CRM на 2 минуты?
[Клиент] Давайте, расскажите.
[ИИ] Отлично, задам несколько уточняющих вопросов...',1,'2026-09-27 22:00:00','2026-09-27 22:02:15','2026-09-27 22:00:00');
INSERT INTO ai_calls VALUES
  ('acl_019x','tnt_0016','aag_0179','acp_017f','con_00dd','org_00dp',NULL,NULL,'outbound','+998 77 461 46 82','completed','objection',410,0.62,'consent.calls = true (зафиксировано при сборе заявки)',1,0,0,0.41,'Возражение по цене. Агент привёл сравнение с текущими расходами, клиент попросил паузу.','[ИИ] Здравствуйте, это ИИ-помощник компании Acme Corp. Звонок записывается для контроля качества.
[Клиент] Да, слушаю.
[ИИ] Удобно обсудить внедрение CRM на 2 минуты?
[Клиент] Давайте, расскажите.
[ИИ] Отлично, задам несколько уточняющих вопросов...',1,'2026-08-23 03:00:00','2026-08-23 03:06:50','2026-08-23 03:00:00'),
  ('acl_019z','tnt_0016','aag_0176','acp_017f','con_00cc','org_00dx',NULL,NULL,'outbound','+998 84 294 88 94','completed','interested',297,0.45,'consent.calls = true (зафиксировано при сборе заявки)',1,0,1,0.87,'Клиент заинтересовался тарифом Business, запросил коммерческое предложение.','[ИИ] Здравствуйте, это ИИ-помощник компании Acme Corp. Звонок записывается для контроля качества.
[Клиент] Да, слушаю.
[ИИ] Удобно обсудить внедрение CRM на 2 минуты?
[Клиент] Давайте, расскажите.
[ИИ] Отлично, задам несколько уточняющих вопросов...',1,'2026-10-05 16:00:00','2026-10-05 16:04:57','2026-10-05 16:00:00'),
  ('acl_01a1','tnt_0016','aag_0179','acp_017f','con_00b4','org_00e6','deal_00hp',NULL,'outbound','+998 99 339 60 96','completed','human_handoff',208,0.31,'consent.calls = true (зафиксировано при сборе заявки)',1,0,0,0.49,'Запросил связь с человеком, переведён на менеджера.','[ИИ] Здравствуйте, это ИИ-помощник компании Acme Corp. Звонок записывается для контроля качества.
[Клиент] Да, слушаю.
[ИИ] Удобно обсудить внедрение CRM на 2 минуты?
[Клиент] Давайте, расскажите.
[ИИ] Отлично, задам несколько уточняющих вопросов...',1,'2026-10-05 16:00:00','2026-10-05 16:03:28','2026-10-05 16:00:00'),
  ('acl_01a3','tnt_0016','aag_0176','acp_017e','con_00b5','org_00e2',NULL,'ord_00s8','outbound','+998 84 544 63 23','completed','interested',229,0.34,'consent.calls = true (зафиксировано при сборе заявки)',1,0,0,0.82,'Клиент заинтересовался тарифом Business, запросил коммерческое предложение.','[ИИ] Здравствуйте, это ИИ-помощник компании Acme Corp. Звонок записывается для контроля качества.
[Клиент] Да, слушаю.
[ИИ] Удобно обсудить внедрение CRM на 2 минуты?
[Клиент] Давайте, расскажите.
[ИИ] Отлично, задам несколько уточняющих вопросов...',1,'2026-08-28 18:00:00','2026-08-28 18:03:49','2026-08-28 18:00:00'),
  ('acl_01a6','tnt_0016','aag_0175','acp_017d','con_00b8','org_00d3',NULL,NULL,'outbound','+998 72 770 65 41','completed','interested',142,0.21,'consent.calls = true (зафиксировано при сборе заявки)',1,0,0,0.82,'Клиент заинтересовался тарифом Business, запросил коммерческое предложение.','[ИИ] Здравствуйте, это ИИ-помощник компании Acme Corp. Звонок записывается для контроля качества.
[Клиент] Да, слушаю.
[ИИ] Удобно обсудить внедрение CRM на 2 минуты?
[Клиент] Давайте, расскажите.
[ИИ] Отлично, задам несколько уточняющих вопросов...',1,'2026-09-17 05:00:00','2026-09-17 05:02:22','2026-09-17 05:00:00'),
  ('acl_01a8','tnt_0016','aag_0177','acp_017g','con_00ch','org_00c6','deal_00kk',NULL,'outbound','+998 86 332 24 79','completed','qualified',199,0.3,'consent.calls = true (зафиксировано при сборе заявки)',1,1,1,0.86,'Квалифицирован: есть бюджет, потребность в интеграции и ЛПР на линии.','[ИИ] Здравствуйте, это ИИ-помощник компании Acme Corp. Звонок записывается для контроля качества.
[Клиент] Да, слушаю.
[ИИ] Удобно обсудить внедрение CRM на 2 минуты?
[Клиент] Давайте, расскажите.
[ИИ] Отлично, задам несколько уточняющих вопросов...',1,'2026-10-11 18:00:00','2026-10-11 18:03:19','2026-10-11 18:00:00'),
  ('acl_01aa','tnt_0016','aag_0177','acp_017f','con_00cm','org_00cb',NULL,NULL,'outbound','+998 97 247 63 92','completed','not_interested',205,0.31,'consent.calls = true (зафиксировано при сборе заявки)',1,0,1,0.69,'Клиент сообщил об отсутствии потребности на текущий момент.','[ИИ] Здравствуйте, это ИИ-помощник компании Acme Corp. Звонок записывается для контроля качества.
[Клиент] Да, слушаю.
[ИИ] Удобно обсудить внедрение CRM на 2 минуты?
[Клиент] Нам это сейчас не нужно.
[ИИ] Отлично, задам несколько уточняющих вопросов...',1,'2026-10-09 15:00:00','2026-10-09 15:03:25','2026-10-09 15:00:00'),
  ('acl_01ac','tnt_0016','aag_0179',NULL,'con_00ef','org_00cu',NULL,NULL,'outbound','+998 98 639 23 65','completed','qualified',344,0.52,'consent.calls = true (зафиксировано при сборе заявки)',1,0,0,0.77,'Квалифицирован: есть бюджет, потребность в интеграции и ЛПР на линии.','[ИИ] Здравствуйте, это ИИ-помощник компании Acme Corp. Звонок записывается для контроля качества.
[Клиент] Да, слушаю.
[ИИ] Удобно обсудить внедрение CRM на 2 минуты?
[Клиент] Давайте, расскажите.
[ИИ] Отлично, задам несколько уточняющих вопросов...',1,'2026-09-04 18:00:00','2026-09-04 18:05:44','2026-09-04 18:00:00'),
  ('acl_01ae','tnt_0016','aag_0179','acp_017g','con_00bf','org_00cq','deal_00h9',NULL,'outbound','+998 80 949 98 84','completed','interested',50,0.08,'consent.calls = true (зафиксировано при сборе заявки)',1,1,1,0.94,'Клиент заинтересовался тарифом Business, запросил коммерческое предложение.','[ИИ] Здравствуйте, это ИИ-помощник компании Acme Corp. Звонок записывается для контроля качества.
[Клиент] Да, слушаю.
[ИИ] Удобно обсудить внедрение CRM на 2 минуты?
[Клиент] Давайте, расскажите.
[ИИ] Отлично, задам несколько уточняющих вопросов...',1,'2026-08-27 08:00:00','2026-08-27 08:00:50','2026-08-27 08:00:00'),
  ('acl_01ag','tnt_0016','aag_0176','acp_017e','con_00cw','org_00dk','deal_00kv',NULL,'outbound','+998 73 288 38 83','completed','voicemail',21,0.03,'consent.calls = true (зафиксировано при сборе заявки)',1,0,1,0.34,'Ответил автоответчик, оставлено голосовое сообщение.',NULL,1,'2026-09-15 02:00:00','2026-09-15 02:00:21','2026-09-15 02:00:00'),
  ('acl_01ah','tnt_0016','aag_0176','acp_017e','con_00cl','org_00dp',NULL,NULL,'outbound','+998 83 765 94 38','completed','human_handoff',400,0.6,'consent.calls = true (зафиксировано при сборе заявки)',1,0,0,0.61,'Запросил связь с человеком, переведён на менеджера.','[ИИ] Здравствуйте, это ИИ-помощник компании Acme Corp. Звонок записывается для контроля качества.
[Клиент] Да, слушаю.
[ИИ] Удобно обсудить внедрение CRM на 2 минуты?
[Клиент] Давайте, расскажите.
[ИИ] Отлично, задам несколько уточняющих вопросов...',1,'2026-10-09 19:00:00','2026-10-09 19:06:40','2026-10-09 19:00:00'),
  ('acl_01aj','tnt_0016','aag_0178','acp_017f','con_00dd','org_00ed',NULL,'ord_00tq','outbound','+998 89 677 56 95','completed','interested',395,0.59,'consent.calls = true (зафиксировано при сборе заявки)',1,0,0,0.85,'Клиент заинтересовался тарифом Business, запросил коммерческое предложение.','[ИИ] Здравствуйте, это ИИ-помощник компании Acme Corp. Звонок записывается для контроля качества.
[Клиент] Да, слушаю.
[ИИ] Удобно обсудить внедрение CRM на 2 минуты?
[Клиент] Давайте, расскажите.
[ИИ] Отлично, задам несколько уточняющих вопросов...',1,'2026-10-02 09:00:00','2026-10-02 09:06:35','2026-10-02 09:00:00'),
  ('acl_01al','tnt_0016','aag_0175',NULL,'con_00em','org_00bp',NULL,NULL,'outbound','+998 74 580 77 71','completed','interested',228,0.34,'consent.calls = true (зафиксировано при сборе заявки)',1,1,1,0.85,'Клиент заинтересовался тарифом Business, запросил коммерческое предложение.','[ИИ] Здравствуйте, это ИИ-помощник компании Acme Corp. Звонок записывается для контроля качества.
[Клиент] Да, слушаю.
[ИИ] Удобно обсудить внедрение CRM на 2 минуты?
[Клиент] Давайте, расскажите.
[ИИ] Отлично, задам несколько уточняющих вопросов...',1,'2026-09-24 02:00:00','2026-09-24 02:03:48','2026-09-24 02:00:00'),
  ('acl_01an','tnt_0016','aag_0179',NULL,'con_00c8','org_00c6',NULL,NULL,'outbound','+998 84 814 31 60','completed','not_interested',179,0.27,'consent.calls = true (зафиксировано при сборе заявки)',1,1,1,0.56,'Клиент сообщил об отсутствии потребности на текущий момент.','[ИИ] Здравствуйте, это ИИ-помощник компании Acme Corp. Звонок записывается для контроля качества.
[Клиент] Да, слушаю.
[ИИ] Удобно обсудить внедрение CRM на 2 минуты?
[Клиент] Нам это сейчас не нужно.
[ИИ] Отлично, задам несколько уточняющих вопросов...',1,'2026-09-25 04:00:00','2026-09-25 04:02:59','2026-09-25 04:00:00'),
  ('acl_01ap','tnt_0016','aag_0175','acp_017e','con_00cc','org_00e2',NULL,NULL,'outbound','+998 78 162 39 61','failed','human_handoff',296,0.44,'consent.calls = true (зафиксировано при сборе заявки)',1,0,0,0.47,'Запросил связь с человеком, переведён на менеджера.','[ИИ] Здравствуйте, это ИИ-помощник компании Acme Corp. Звонок записывается для контроля качества.
[Клиент] Да, слушаю.
[ИИ] Удобно обсудить внедрение CRM на 2 минуты?
[Клиент] Давайте, расскажите.
[ИИ] Отлично, задам несколько уточняющих вопросов...',1,'2026-09-13 00:00:00','2026-09-13 00:04:56','2026-09-13 00:00:00'),
  ('acl_01ar','tnt_0016','aag_0175','acp_017d','con_00ec','org_00dp',NULL,NULL,'outbound','+998 71 671 85 57','completed','opted_out',250,0.38,'consent.calls = true (зафиксировано при сборе заявки)',1,1,1,0.36,'Клиент запросил исключение из обзвона — добавлен в suppression list.','[ИИ] Здравствуйте, это ИИ-помощник компании Acme Corp. Звонок записывается для контроля качества.
[Клиент] Да, слушаю.
[ИИ] Удобно обсудить внедрение CRM на 2 минуты?
[Клиент] Давайте, расскажите.
[ИИ] Отлично, задам несколько уточняющих вопросов...',1,'2026-09-29 14:00:00','2026-09-29 14:04:10','2026-09-29 14:00:00'),
  ('acl_01at','tnt_0016','aag_0176','acp_017e','con_00da','org_00b1',NULL,NULL,'outbound','+998 85 286 64 92','completed','human_handoff',330,0.5,'consent.calls = true (зафиксировано при сборе заявки)',1,0,0,0.36,'Запросил связь с человеком, переведён на менеджера.','[ИИ] Здравствуйте, это ИИ-помощник компании Acme Corp. Звонок записывается для контроля качества.
[Клиент] Да, слушаю.
[ИИ] Удобно обсудить внедрение CRM на 2 минуты?
[Клиент] Давайте, расскажите.
[ИИ] Отлично, задам несколько уточняющих вопросов...',1,'2026-08-27 20:00:00','2026-08-27 20:05:30','2026-08-27 20:00:00'),
  ('acl_01av','tnt_0016','aag_0177','acp_017f','con_00c1','org_00dk',NULL,NULL,'outbound','+998 87 466 44 66','completed','opted_out',103,0.15,'consent.calls = true (зафиксировано при сборе заявки)',1,0,1,0.54,'Клиент запросил исключение из обзвона — добавлен в suppression list.','[ИИ] Здравствуйте, это ИИ-помощник компании Acme Corp. Звонок записывается для контроля качества.
[Клиент] Да, слушаю.
[ИИ] Удобно обсудить внедрение CRM на 2 минуты?
[Клиент] Давайте, расскажите.
[ИИ] Отлично, задам несколько уточняющих вопросов...',1,'2026-09-21 08:00:00','2026-09-21 08:01:43','2026-09-21 08:00:00'),
  ('acl_01ax','tnt_0016','aag_0179','acp_017e','con_00eh','org_00b6',NULL,'ord_00t8','outbound','+998 79 668 89 94','completed','interested',58,0.09,'consent.calls = true (зафиксировано при сборе заявки)',1,0,0,0.97,'Клиент заинтересовался тарифом Business, запросил коммерческое предложение.','[ИИ] Здравствуйте, это ИИ-помощник компании Acme Corp. Звонок записывается для контроля качества.
[Клиент] Да, слушаю.
[ИИ] Удобно обсудить внедрение CRM на 2 минуты?
[Клиент] Давайте, расскажите.
[ИИ] Отлично, задам несколько уточняющих вопросов...',1,'2026-08-21 06:00:00','2026-08-21 06:00:58','2026-08-21 06:00:00'),
  ('acl_01b0','tnt_0016','aag_0177','acp_017f','con_00d4','org_00e9',NULL,NULL,'outbound','+998 70 663 59 36','completed','interested',293,0.44,'consent.calls = true (зафиксировано при сборе заявки)',1,0,0,0.88,'Клиент заинтересовался тарифом Business, запросил коммерческое предложение.','[ИИ] Здравствуйте, это ИИ-помощник компании Acme Corp. Звонок записывается для контроля качества.
[Клиент] Да, слушаю.
[ИИ] Удобно обсудить внедрение CRM на 2 минуты?
[Клиент] Давайте, расскажите.
[ИИ] Отлично, задам несколько уточняющих вопросов...',1,'2026-09-21 15:00:00','2026-09-21 15:04:53','2026-09-21 15:00:00'),
  ('acl_01b2','tnt_0016','aag_0179','acp_017e','con_00bs','org_00d7',NULL,NULL,'outbound','+998 77 399 73 36','completed','not_interested',201,0.3,'consent.calls = true (зафиксировано при сборе заявки)',1,1,1,0.52,'Клиент сообщил об отсутствии потребности на текущий момент.','[ИИ] Здравствуйте, это ИИ-помощник компании Acme Corp. Звонок записывается для контроля качества.
[Клиент] Да, слушаю.
[ИИ] Удобно обсудить внедрение CRM на 2 минуты?
[Клиент] Нам это сейчас не нужно.
[ИИ] Отлично, задам несколько уточняющих вопросов...',1,'2026-09-07 22:00:00','2026-09-07 22:03:21','2026-09-07 22:00:00'),
  ('acl_01b4','tnt_0016','aag_0175','acp_017e','con_00e3','org_00bx',NULL,'ord_00tw','outbound','+998 88 495 74 66','completed','interested',172,0.26,'consent.calls = true (зафиксировано при сборе заявки)',1,1,1,0.9,'Клиент заинтересовался тарифом Business, запросил коммерческое предложение.','[ИИ] Здравствуйте, это ИИ-помощник компании Acme Corp. Звонок записывается для контроля качества.
[Клиент] Да, слушаю.
[ИИ] Удобно обсудить внедрение CRM на 2 минуты?
[Клиент] Давайте, расскажите.
[ИИ] Отлично, задам несколько уточняющих вопросов...',1,'2026-08-18 19:00:00','2026-08-18 19:02:52','2026-08-18 19:00:00'),
  ('acl_01b7','tnt_0016','aag_0179','acp_017e','con_00e4','org_00d7','deal_00hs',NULL,'outbound','+998 96 450 71 68','completed','opted_out',289,0.43,'consent.calls = true (зафиксировано при сборе заявки)',1,1,1,0.48,'Клиент запросил исключение из обзвона — добавлен в suppression list.','[ИИ] Здравствуйте, это ИИ-помощник компании Acme Corp. Звонок записывается для контроля качества.
[Клиент] Да, слушаю.
[ИИ] Удобно обсудить внедрение CRM на 2 минуты?
[Клиент] Давайте, расскажите.
[ИИ] Отлично, задам несколько уточняющих вопросов...',1,'2026-09-20 23:00:00','2026-09-20 23:04:49','2026-09-20 23:00:00'),
  ('acl_01b9','tnt_0016','aag_0176','acp_017e','con_00e3','org_00ci','deal_00hc',NULL,'outbound','+998 79 153 72 22','completed','interested',68,0.1,'consent.calls = true (зафиксировано при сборе заявки)',1,0,1,0.85,'Клиент заинтересовался тарифом Business, запросил коммерческое предложение.','[ИИ] Здравствуйте, это ИИ-помощник компании Acme Corp. Звонок записывается для контроля качества.
[Клиент] Да, слушаю.
[ИИ] Удобно обсудить внедрение CRM на 2 минуты?
[Клиент] Давайте, расскажите.
[ИИ] Отлично, задам несколько уточняющих вопросов...',1,'2026-08-14 03:00:00','2026-08-14 03:01:08','2026-08-14 03:00:00'),
  ('acl_01bc','tnt_0016','aag_0179','acp_017d','con_00ch','org_00e9','deal_00fu',NULL,'outbound','+998 77 672 77 12','completed','not_interested',407,0.61,'consent.calls = true (зафиксировано при сборе заявки)',1,0,1,0.49,'Клиент сообщил об отсутствии потребности на текущий момент.','[ИИ] Здравствуйте, это ИИ-помощник компании Acme Corp. Звонок записывается для контроля качества.
[Клиент] Да, слушаю.
[ИИ] Удобно обсудить внедрение CRM на 2 минуты?
[Клиент] Нам это сейчас не нужно.
[ИИ] Отлично, задам несколько уточняющих вопросов...',1,'2026-09-11 18:00:00','2026-09-11 18:06:47','2026-09-11 18:00:00'),
  ('acl_01be','tnt_0016','aag_0176','acp_017f','con_00c1','org_00cq',NULL,NULL,'outbound','+998 92 845 95 36','completed','objection',402,0.6,'consent.calls = true (зафиксировано при сборе заявки)',1,1,1,0.42,'Возражение по цене. Агент привёл сравнение с текущими расходами, клиент попросил паузу.','[ИИ] Здравствуйте, это ИИ-помощник компании Acme Corp. Звонок записывается для контроля качества.
[Клиент] Да, слушаю.
[ИИ] Удобно обсудить внедрение CRM на 2 минуты?
[Клиент] Давайте, расскажите.
[ИИ] Отлично, задам несколько уточняющих вопросов...',1,'2026-09-21 01:00:00','2026-09-21 01:06:42','2026-09-21 01:00:00'),
  ('acl_01bg','tnt_0016','aag_0177','acp_017d','con_00eb','org_00ce',NULL,NULL,'outbound','+998 76 651 72 29','completed','opted_out',339,0.51,'consent.calls = true (зафиксировано при сборе заявки)',1,0,1,0.38,'Клиент запросил исключение из обзвона — добавлен в suppression list.','[ИИ] Здравствуйте, это ИИ-помощник компании Acme Corp. Звонок записывается для контроля качества.
[Клиент] Да, слушаю.
[ИИ] Удобно обсудить внедрение CRM на 2 минуты?
[Клиент] Давайте, расскажите.
[ИИ] Отлично, задам несколько уточняющих вопросов...',1,'2026-10-01 14:00:00','2026-10-01 14:05:39','2026-10-01 14:00:00'),
  ('acl_01bi','tnt_0016','aag_0178','acp_017f','con_00cz','org_00e2',NULL,NULL,'outbound','+998 96 686 65 83','completed','no_answer',17,0.03,'consent.calls = true (зафиксировано при сборе заявки)',1,0,0,0.37,'Нет ответа после 4 гудков, назначен повторный дозвон.',NULL,1,'2026-09-15 09:00:00','2026-09-15 09:00:17','2026-09-15 09:00:00'),
  ('acl_01bj','tnt_0016','aag_0176',NULL,'con_00bt','org_00e9','deal_00m2',NULL,'outbound','+998 79 770 42 50','completed','objection',210,0.32,'consent.calls = true (зафиксировано при сборе заявки)',1,0,0,0.4,'Возражение по цене. Агент привёл сравнение с текущими расходами, клиент попросил паузу.','[ИИ] Здравствуйте, это ИИ-помощник компании Acme Corp. Звонок записывается для контроля качества.
[Клиент] Да, слушаю.
[ИИ] Удобно обсудить внедрение CRM на 2 минуты?
[Клиент] Давайте, расскажите.
[ИИ] Отлично, задам несколько уточняющих вопросов...',1,'2026-09-22 15:00:00','2026-09-22 15:03:30','2026-09-22 15:00:00'),
  ('acl_01bl','tnt_0016','aag_0179','acp_017f','con_00db','org_00ed',NULL,NULL,'outbound','+998 95 532 67 66','completed','voicemail',18,0.03,'consent.calls = true (зафиксировано при сборе заявки)',1,0,1,0.59,'Ответил автоответчик, оставлено голосовое сообщение.',NULL,1,'2026-09-23 20:00:00','2026-09-23 20:00:18','2026-09-23 20:00:00'),
  ('acl_01bm','tnt_0016','aag_0176',NULL,'con_00ee','org_00au','deal_00fu',NULL,'outbound','+998 97 841 69 21','completed','opted_out',324,0.49,'consent.calls = true (зафиксировано при сборе заявки)',1,0,0,0.34,'Клиент запросил исключение из обзвона — добавлен в suppression list.','[ИИ] Здравствуйте, это ИИ-помощник компании Acme Corp. Звонок записывается для контроля качества.
[Клиент] Да, слушаю.
[ИИ] Удобно обсудить внедрение CRM на 2 минуты?
[Клиент] Давайте, расскажите.
[ИИ] Отлично, задам несколько уточняющих вопросов...',1,'2026-08-25 06:00:00','2026-08-25 06:05:24','2026-08-25 06:00:00'),
  ('acl_01bo','tnt_0016','aag_0177','acp_017e','con_00dw','org_00e9','deal_00k3',NULL,'outbound','+998 97 209 19 73','completed','voicemail',11,0.02,'consent.calls = true (зафиксировано при сборе заявки)',1,0,1,0.59,'Ответил автоответчик, оставлено голосовое сообщение.',NULL,1,'2026-08-24 03:00:00','2026-08-24 03:00:11','2026-08-24 03:00:00'),
  ('acl_01bp','tnt_0016','aag_0177',NULL,'con_00d2','org_00dp',NULL,NULL,'outbound','+998 88 838 91 64','completed','no_answer',25,0.04,'consent.calls = true (зафиксировано при сборе заявки)',1,0,1,0.34,'Нет ответа после 4 гудков, назначен повторный дозвон.',NULL,1,'2026-08-29 23:00:00','2026-08-29 23:00:25','2026-08-29 23:00:00'),
  ('acl_01bq','tnt_0016','aag_0179',NULL,'con_00bq','org_00e2',NULL,NULL,'outbound','+998 79 978 71 41','completed','no_answer',7,0.01,'consent.calls = true (зафиксировано при сборе заявки)',0,0,1,0.33,'Нет ответа после 4 гудков, назначен повторный дозвон.',NULL,1,'2026-08-29 07:00:00','2026-08-29 07:00:07','2026-08-29 07:00:00'),
  ('acl_01br','tnt_0016','aag_0175','acp_017g','con_00bd','org_00bx','deal_00j6',NULL,'outbound','+998 82 526 81 25','completed','qualified',126,0.19,'consent.calls = true (зафиксировано при сборе заявки)',1,0,0,0.72,'Квалифицирован: есть бюджет, потребность в интеграции и ЛПР на линии.','[ИИ] Здравствуйте, это ИИ-помощник компании Acme Corp. Звонок записывается для контроля качества.
[Клиент] Да, слушаю.
[ИИ] Удобно обсудить внедрение CRM на 2 минуты?
[Клиент] Давайте, расскажите.
[ИИ] Отлично, задам несколько уточняющих вопросов...',1,'2026-09-11 14:00:00','2026-09-11 14:02:06','2026-09-11 14:00:00'),
  ('acl_01bt','tnt_0016','aag_0176','acp_017g','con_00b2','org_00ci',NULL,NULL,'outbound','+998 96 358 59 20','completed','qualified',303,0.45,'consent.calls = true (зафиксировано при сборе заявки)',1,0,0,0.75,'Квалифицирован: есть бюджет, потребность в интеграции и ЛПР на линии.','[ИИ] Здравствуйте, это ИИ-помощник компании Acme Corp. Звонок записывается для контроля качества.
[Клиент] Да, слушаю.
[ИИ] Удобно обсудить внедрение CRM на 2 минуты?
[Клиент] Давайте, расскажите.
[ИИ] Отлично, задам несколько уточняющих вопросов...',1,'2026-08-22 19:00:00','2026-08-22 19:05:03','2026-08-22 19:00:00'),
  ('acl_01bv','tnt_0016','aag_0177','acp_017f','con_00ch','org_00ci','deal_00h5',NULL,'outbound','+998 70 635 88 24','completed','human_handoff',165,0.25,'consent.calls = true (зафиксировано при сборе заявки)',1,0,0,0.59,'Запросил связь с человеком, переведён на менеджера.','[ИИ] Здравствуйте, это ИИ-помощник компании Acme Corp. Звонок записывается для контроля качества.
[Клиент] Да, слушаю.
[ИИ] Удобно обсудить внедрение CRM на 2 минуты?
[Клиент] Давайте, расскажите.
[ИИ] Отлично, задам несколько уточняющих вопросов...',1,'2026-09-08 00:00:00','2026-09-08 00:02:45','2026-09-08 00:00:00'),
  ('acl_01bx','tnt_0016','aag_0177','acp_017d','con_00ca','org_00ax',NULL,'ord_00sp','outbound','+998 83 262 79 54','completed','interested',156,0.23,'consent.calls = true (зафиксировано при сборе заявки)',1,1,1,0.84,'Клиент заинтересовался тарифом Business, запросил коммерческое предложение.','[ИИ] Здравствуйте, это ИИ-помощник компании Acme Corp. Звонок записывается для контроля качества.
[Клиент] Да, слушаю.
[ИИ] Удобно обсудить внедрение CRM на 2 минуты?
[Клиент] Давайте, расскажите.
[ИИ] Отлично, задам несколько уточняющих вопросов...',1,'2026-08-20 20:00:00','2026-08-20 20:02:36','2026-08-20 20:00:00'),
  ('acl_01bz','tnt_0016','aag_0175','acp_017e','con_00di','org_00e9',NULL,NULL,'outbound','+998 80 648 99 91','completed','opted_out',316,0.47,'consent.calls = true (зафиксировано при сборе заявки)',1,1,1,0.43,'Клиент запросил исключение из обзвона — добавлен в suppression list.','[ИИ] Здравствуйте, это ИИ-помощник компании Acme Corp. Звонок записывается для контроля качества.
[Клиент] Да, слушаю.
[ИИ] Удобно обсудить внедрение CRM на 2 минуты?
[Клиент] Давайте, расскажите.
[ИИ] Отлично, задам несколько уточняющих вопросов...',1,'2026-08-13 00:00:00','2026-08-13 00:05:16','2026-08-13 00:00:00'),
  ('acl_01c1','tnt_0016','aag_0178',NULL,'con_00eg','org_00bu','deal_00hc',NULL,'outbound','+998 99 609 50 45','completed','human_handoff',372,0.56,'consent.calls = true (зафиксировано при сборе заявки)',1,1,1,0.37,'Запросил связь с человеком, переведён на менеджера.','[ИИ] Здравствуйте, это ИИ-помощник компании Acme Corp. Звонок записывается для контроля качества.
[Клиент] Да, слушаю.
[ИИ] Удобно обсудить внедрение CRM на 2 минуты?
[Клиент] Давайте, расскажите.
[ИИ] Отлично, задам несколько уточняющих вопросов...',1,'2026-09-19 21:00:00','2026-09-19 21:06:12','2026-09-19 21:00:00'),
  ('acl_01c3','tnt_0016','aag_0175','acp_017e','con_00ee','org_00dh','deal_00ls',NULL,'outbound','+998 72 481 24 26','completed','voicemail',1,0,'consent.calls = true (зафиксировано при сборе заявки)',0,0,1,0.58,'Ответил автоответчик, оставлено голосовое сообщение.',NULL,1,'2026-09-26 18:00:00','2026-09-26 18:00:01','2026-09-26 18:00:00'),
  ('acl_01c4','tnt_0016','aag_0178','acp_017f','con_00b8','org_00b9',NULL,NULL,'outbound','+998 98 615 20 43','completed','not_interested',158,0.24,'consent.calls = true (зафиксировано при сборе заявки)',1,1,1,0.38,'Клиент сообщил об отсутствии потребности на текущий момент.','[ИИ] Здравствуйте, это ИИ-помощник компании Acme Corp. Звонок записывается для контроля качества.
[Клиент] Да, слушаю.
[ИИ] Удобно обсудить внедрение CRM на 2 минуты?
[Клиент] Нам это сейчас не нужно.
[ИИ] Отлично, задам несколько уточняющих вопросов...',1,'2026-10-04 01:00:00','2026-10-04 01:02:38','2026-10-04 01:00:00'),
  ('acl_01c6','tnt_0016','aag_0178',NULL,'con_00br','org_00b1',NULL,'ord_00ud','outbound','+998 86 588 97 25','completed','interested',217,0.33,'consent.calls = true (зафиксировано при сборе заявки)',1,0,0,0.93,'Клиент заинтересовался тарифом Business, запросил коммерческое предложение.','[ИИ] Здравствуйте, это ИИ-помощник компании Acme Corp. Звонок записывается для контроля качества.
[Клиент] Да, слушаю.
[ИИ] Удобно обсудить внедрение CRM на 2 минуты?
[Клиент] Давайте, расскажите.
[ИИ] Отлично, задам несколько уточняющих вопросов...',1,'2026-09-30 06:00:00','2026-09-30 06:03:37','2026-09-30 06:00:00'),
  ('acl_01c8','tnt_0016','aag_0175','acp_017d','con_00ej','org_00bm',NULL,NULL,'outbound','+998 94 848 66 68','completed','opted_out',220,0.33,'consent.calls = true (зафиксировано при сборе заявки)',1,1,1,0.49,'Клиент запросил исключение из обзвона — добавлен в suppression list.','[ИИ] Здравствуйте, это ИИ-помощник компании Acme Corp. Звонок записывается для контроля качества.
[Клиент] Да, слушаю.
[ИИ] Удобно обсудить внедрение CRM на 2 минуты?
[Клиент] Давайте, расскажите.
[ИИ] Отлично, задам несколько уточняющих вопросов...',1,'2026-09-09 22:00:00','2026-09-09 22:03:40','2026-09-09 22:00:00'),
  ('acl_01ca','tnt_0016','aag_0179',NULL,'con_00dd','org_00cq',NULL,'ord_00te','outbound','+998 93 995 82 77','completed','interested',76,0.11,'consent.calls = true (зафиксировано при сборе заявки)',1,1,1,0.85,'Клиент заинтересовался тарифом Business, запросил коммерческое предложение.','[ИИ] Здравствуйте, это ИИ-помощник компании Acme Corp. Звонок записывается для контроля качества.
[Клиент] Да, слушаю.
[ИИ] Удобно обсудить внедрение CRM на 2 минуты?
[Клиент] Давайте, расскажите.
[ИИ] Отлично, задам несколько уточняющих вопросов...',1,'2026-08-18 15:00:00','2026-08-18 15:01:16','2026-08-18 15:00:00'),
  ('acl_01cc','tnt_0016','aag_0179','acp_017f','con_00ck','org_00e9',NULL,'ord_00uw','outbound','+998 95 808 69 56','completed','interested',394,0.59,'consent.calls = true (зафиксировано при сборе заявки)',1,1,1,0.93,'Клиент заинтересовался тарифом Business, запросил коммерческое предложение.','[ИИ] Здравствуйте, это ИИ-помощник компании Acme Corp. Звонок записывается для контроля качества.
[Клиент] Да, слушаю.
[ИИ] Удобно обсудить внедрение CRM на 2 минуты?
[Клиент] Давайте, расскажите.
[ИИ] Отлично, задам несколько уточняющих вопросов...',1,'2026-09-09 23:00:00','2026-09-09 23:06:34','2026-09-09 23:00:00'),
  ('acl_01ce','tnt_0016','aag_0176','acp_017d','con_00e7','org_00bh',NULL,NULL,'outbound','+998 93 749 52 63','completed','qualified',281,0.42,'consent.calls = true (зафиксировано при сборе заявки)',1,1,1,0.85,'Квалифицирован: есть бюджет, потребность в интеграции и ЛПР на линии.','[ИИ] Здравствуйте, это ИИ-помощник компании Acme Corp. Звонок записывается для контроля качества.
[Клиент] Да, слушаю.
[ИИ] Удобно обсудить внедрение CRM на 2 минуты?
[Клиент] Давайте, расскажите.
[ИИ] Отлично, задам несколько уточняющих вопросов...',1,'2026-10-02 08:00:00','2026-10-02 08:04:41','2026-10-02 08:00:00'),
  ('acl_01cg','tnt_0016','aag_0179',NULL,'con_00e8','org_00b9','deal_00jr',NULL,'outbound','+998 98 169 68 51','completed','human_handoff',369,0.55,'consent.calls = true (зафиксировано при сборе заявки)',1,1,1,0.6,'Запросил связь с человеком, переведён на менеджера.','[ИИ] Здравствуйте, это ИИ-помощник компании Acme Corp. Звонок записывается для контроля качества.
[Клиент] Да, слушаю.
[ИИ] Удобно обсудить внедрение CRM на 2 минуты?
[Клиент] Давайте, расскажите.
[ИИ] Отлично, задам несколько уточняющих вопросов...',1,'2026-08-23 04:00:00','2026-08-23 04:06:09','2026-08-23 04:00:00'),
  ('acl_01ci','tnt_0016','aag_0178','acp_017e','con_00ea','org_00dc',NULL,NULL,'outbound','+998 97 381 74 64','completed','interested',417,0.63,'consent.calls = true (зафиксировано при сборе заявки)',1,0,0,0.91,'Клиент заинтересовался тарифом Business, запросил коммерческое предложение.','[ИИ] Здравствуйте, это ИИ-помощник компании Acme Corp. Звонок записывается для контроля качества.
[Клиент] Да, слушаю.
[ИИ] Удобно обсудить внедрение CRM на 2 минуты?
[Клиент] Давайте, расскажите.
[ИИ] Отлично, задам несколько уточняющих вопросов...',1,'2026-08-24 16:00:00','2026-08-24 16:06:57','2026-08-24 16:00:00'),
  ('acl_01ck','tnt_0016','aag_0176','acp_017d','con_00dd','org_00b1',NULL,NULL,'outbound','+998 86 266 71 68','completed','objection',168,0.25,'consent.calls = true (зафиксировано при сборе заявки)',1,0,1,0.46,'Возражение по цене. Агент привёл сравнение с текущими расходами, клиент попросил паузу.','[ИИ] Здравствуйте, это ИИ-помощник компании Acme Corp. Звонок записывается для контроля качества.
[Клиент] Да, слушаю.
[ИИ] Удобно обсудить внедрение CRM на 2 минуты?
[Клиент] Давайте, расскажите.
[ИИ] Отлично, задам несколько уточняющих вопросов...',1,'2026-08-17 08:00:00','2026-08-17 08:02:48','2026-08-17 08:00:00');
INSERT INTO ai_calls VALUES
  ('acl_01cm','tnt_0016','aag_0177','acp_017g','con_00br','org_00dx',NULL,NULL,'outbound','+998 73 312 81 76','completed','not_interested',363,0.54,'consent.calls = true (зафиксировано при сборе заявки)',1,0,0,0.48,'Клиент сообщил об отсутствии потребности на текущий момент.','[ИИ] Здравствуйте, это ИИ-помощник компании Acme Corp. Звонок записывается для контроля качества.
[Клиент] Да, слушаю.
[ИИ] Удобно обсудить внедрение CRM на 2 минуты?
[Клиент] Нам это сейчас не нужно.
[ИИ] Отлично, задам несколько уточняющих вопросов...',1,'2026-10-10 07:00:00','2026-10-10 07:06:03','2026-10-10 07:00:00'),
  ('acl_01co','tnt_0016','aag_0176','acp_017f','con_00ds','org_00dp','deal_00fp',NULL,'outbound','+998 80 165 71 78','completed','no_answer',17,0.03,'consent.calls = true (зафиксировано при сборе заявки)',1,0,1,0.53,'Нет ответа после 4 гудков, назначен повторный дозвон.',NULL,1,'2026-09-19 09:00:00','2026-09-19 09:00:17','2026-09-19 09:00:00'),
  ('acl_01cp','tnt_0016','aag_0178','acp_017f','con_00dn','org_00dp',NULL,NULL,'outbound','+998 87 704 92 53','completed','qualified',374,0.56,'consent.calls = true (зафиксировано при сборе заявки)',1,1,1,0.78,'Квалифицирован: есть бюджет, потребность в интеграции и ЛПР на линии.','[ИИ] Здравствуйте, это ИИ-помощник компании Acme Corp. Звонок записывается для контроля качества.
[Клиент] Да, слушаю.
[ИИ] Удобно обсудить внедрение CRM на 2 минуты?
[Клиент] Давайте, расскажите.
[ИИ] Отлично, задам несколько уточняющих вопросов...',1,'2026-09-05 08:00:00','2026-09-05 08:06:14','2026-09-05 08:00:00'),
  ('acl_01cr','tnt_0016','aag_0177','acp_017f','con_00dv','org_00cy',NULL,NULL,'outbound','+998 88 957 35 91','completed','no_answer',5,0.01,'consent.calls = true (зафиксировано при сборе заявки)',0,0,0,0.43,'Нет ответа после 4 гудков, назначен повторный дозвон.',NULL,1,'2026-09-25 06:00:00','2026-09-25 06:00:05','2026-09-25 06:00:00'),
  ('acl_01cs','tnt_0016','aag_0175','acp_017d','con_00dr','org_00bm',NULL,NULL,'outbound','+998 82 916 73 89','completed','qualified',329,0.49,'consent.calls = true (зафиксировано при сборе заявки)',1,0,1,0.8,'Квалифицирован: есть бюджет, потребность в интеграции и ЛПР на линии.','[ИИ] Здравствуйте, это ИИ-помощник компании Acme Corp. Звонок записывается для контроля качества.
[Клиент] Да, слушаю.
[ИИ] Удобно обсудить внедрение CRM на 2 минуты?
[Клиент] Давайте, расскажите.
[ИИ] Отлично, задам несколько уточняющих вопросов...',1,'2026-08-14 18:00:00','2026-08-14 18:05:29','2026-08-14 18:00:00'),
  ('acl_01cu','tnt_0016','aag_0177','acp_017g','con_00e4','org_00c6',NULL,NULL,'outbound','+998 78 546 28 87','completed','not_interested',62,0.09,'consent.calls = true (зафиксировано при сборе заявки)',1,1,1,0.35,'Клиент сообщил об отсутствии потребности на текущий момент.','[ИИ] Здравствуйте, это ИИ-помощник компании Acme Corp. Звонок записывается для контроля качества.
[Клиент] Да, слушаю.
[ИИ] Удобно обсудить внедрение CRM на 2 минуты?
[Клиент] Нам это сейчас не нужно.
[ИИ] Отлично, задам несколько уточняющих вопросов...',1,'2026-09-02 07:00:00','2026-09-02 07:01:02','2026-09-02 07:00:00'),
  ('acl_01cw','tnt_0016','aag_0176','acp_017d','con_00av','org_00dp','deal_00mj',NULL,'outbound','+998 99 511 38 60','completed','interested',419,0.63,'consent.calls = true (зафиксировано при сборе заявки)',1,0,1,0.9,'Клиент заинтересовался тарифом Business, запросил коммерческое предложение.','[ИИ] Здравствуйте, это ИИ-помощник компании Acme Corp. Звонок записывается для контроля качества.
[Клиент] Да, слушаю.
[ИИ] Удобно обсудить внедрение CRM на 2 минуты?
[Клиент] Давайте, расскажите.
[ИИ] Отлично, задам несколько уточняющих вопросов...',1,'2026-08-26 06:00:00','2026-08-26 06:06:59','2026-08-26 06:00:00'),
  ('acl_01cy','tnt_0016','aag_0177','acp_017g','con_00bw','org_00c0','deal_00g4',NULL,'outbound','+998 74 860 87 74','completed','no_answer',4,0.01,'consent.calls = true (зафиксировано при сборе заявки)',0,0,1,0.54,'Нет ответа после 4 гудков, назначен повторный дозвон.',NULL,1,'2026-09-02 04:00:00','2026-09-02 04:00:04','2026-09-02 04:00:00'),
  ('acl_01cz','tnt_0016','aag_0176','acp_017f','con_00dg','org_00b9','deal_00ic',NULL,'outbound','+998 95 121 73 64','completed','qualified',302,0.45,'consent.calls = true (зафиксировано при сборе заявки)',1,0,0,0.77,'Квалифицирован: есть бюджет, потребность в интеграции и ЛПР на линии.','[ИИ] Здравствуйте, это ИИ-помощник компании Acme Corp. Звонок записывается для контроля качества.
[Клиент] Да, слушаю.
[ИИ] Удобно обсудить внедрение CRM на 2 минуты?
[Клиент] Давайте, расскажите.
[ИИ] Отлично, задам несколько уточняющих вопросов...',1,'2026-09-25 14:00:00','2026-09-25 14:05:02','2026-09-25 14:00:00'),
  ('acl_01d1','tnt_0016','aag_0175',NULL,'con_00dg','org_00cn',NULL,'ord_00rr','outbound','+998 75 640 96 21','completed','interested',358,0.54,'consent.calls = true (зафиксировано при сборе заявки)',1,0,0,0.87,'Клиент заинтересовался тарифом Business, запросил коммерческое предложение.','[ИИ] Здравствуйте, это ИИ-помощник компании Acme Corp. Звонок записывается для контроля качества.
[Клиент] Да, слушаю.
[ИИ] Удобно обсудить внедрение CRM на 2 минуты?
[Клиент] Давайте, расскажите.
[ИИ] Отлично, задам несколько уточняющих вопросов...',1,'2026-08-30 03:00:00','2026-08-30 03:05:58','2026-08-30 03:00:00'),
  ('acl_01d3','tnt_0016','aag_0179','acp_017f','con_00ee','org_00dk','deal_00g9',NULL,'outbound','+998 84 792 51 31','completed','no_answer',23,0.03,'consent.calls = true (зафиксировано при сборе заявки)',1,0,1,0.67,'Нет ответа после 4 гудков, назначен повторный дозвон.',NULL,1,'2026-10-05 16:00:00','2026-10-05 16:00:23','2026-10-05 16:00:00'),
  ('acl_01d4','tnt_0016','aag_0179','acp_017e','con_00dv','org_00d3',NULL,'ord_00uw','outbound','+998 70 878 26 43','completed','interested',214,0.32,'consent.calls = true (зафиксировано при сборе заявки)',1,1,1,0.86,'Клиент заинтересовался тарифом Business, запросил коммерческое предложение.','[ИИ] Здравствуйте, это ИИ-помощник компании Acme Corp. Звонок записывается для контроля качества.
[Клиент] Да, слушаю.
[ИИ] Удобно обсудить внедрение CRM на 2 минуты?
[Клиент] Давайте, расскажите.
[ИИ] Отлично, задам несколько уточняющих вопросов...',1,'2026-09-07 15:00:00','2026-09-07 15:03:34','2026-09-07 15:00:00'),
  ('acl_01d6','tnt_0016','aag_0177','acp_017e','con_00b7','org_00dh','deal_00ly',NULL,'outbound','+998 82 656 68 72','completed','no_answer',6,0.01,'consent.calls = true (зафиксировано при сборе заявки)',0,0,1,0.54,'Нет ответа после 4 гудков, назначен повторный дозвон.',NULL,1,'2026-08-18 21:00:00','2026-08-18 21:00:06','2026-08-18 21:00:00'),
  ('acl_01d7','tnt_0016','aag_0177','acp_017f','con_00bf','org_00e2',NULL,NULL,'outbound','+998 94 791 96 50','completed','objection',227,0.34,'consent.calls = true (зафиксировано при сборе заявки)',1,0,1,0.63,'Возражение по цене. Агент привёл сравнение с текущими расходами, клиент попросил паузу.','[ИИ] Здравствуйте, это ИИ-помощник компании Acme Corp. Звонок записывается для контроля качества.
[Клиент] Да, слушаю.
[ИИ] Удобно обсудить внедрение CRM на 2 минуты?
[Клиент] Давайте, расскажите.
[ИИ] Отлично, задам несколько уточняющих вопросов...',1,'2026-09-17 15:00:00','2026-09-17 15:03:47','2026-09-17 15:00:00'),
  ('acl_01d9','tnt_0016','aag_0176','acp_017d','con_00bw','org_00ei','deal_00jl',NULL,'outbound','+998 71 316 22 81','completed','opted_out',384,0.58,'consent.calls = true (зафиксировано при сборе заявки)',1,0,0,0.56,'Клиент запросил исключение из обзвона — добавлен в suppression list.','[ИИ] Здравствуйте, это ИИ-помощник компании Acme Corp. Звонок записывается для контроля качества.
[Клиент] Да, слушаю.
[ИИ] Удобно обсудить внедрение CRM на 2 минуты?
[Клиент] Давайте, расскажите.
[ИИ] Отлично, задам несколько уточняющих вопросов...',1,'2026-09-26 05:00:00','2026-09-26 05:06:24','2026-09-26 05:00:00'),
  ('acl_01db','tnt_0016','aag_0179','acp_017f','con_00d9','org_00e2',NULL,NULL,'outbound','+998 92 271 88 24','completed','objection',261,0.39,'consent.calls = true (зафиксировано при сборе заявки)',1,0,1,0.5,'Возражение по цене. Агент привёл сравнение с текущими расходами, клиент попросил паузу.','[ИИ] Здравствуйте, это ИИ-помощник компании Acme Corp. Звонок записывается для контроля качества.
[Клиент] Да, слушаю.
[ИИ] Удобно обсудить внедрение CRM на 2 минуты?
[Клиент] Давайте, расскажите.
[ИИ] Отлично, задам несколько уточняющих вопросов...',1,'2026-08-13 23:00:00','2026-08-13 23:04:21','2026-08-13 23:00:00'),
  ('acl_01dd','tnt_0016','aag_0177','acp_017g','con_00d1','org_00ci','deal_00lh',NULL,'outbound','+998 78 718 12 73','completed','qualified',150,0.22,'consent.calls = true (зафиксировано при сборе заявки)',1,1,1,0.89,'Квалифицирован: есть бюджет, потребность в интеграции и ЛПР на линии.','[ИИ] Здравствуйте, это ИИ-помощник компании Acme Corp. Звонок записывается для контроля качества.
[Клиент] Да, слушаю.
[ИИ] Удобно обсудить внедрение CRM на 2 минуты?
[Клиент] Давайте, расскажите.
[ИИ] Отлично, задам несколько уточняющих вопросов...',1,'2026-08-22 09:00:00','2026-08-22 09:02:30','2026-08-22 09:00:00'),
  ('acl_01df','tnt_0016','aag_0176','acp_017g','con_00ay','org_00bx','deal_00j3',NULL,'outbound','+998 88 968 66 93','completed','voicemail',8,0.01,'consent.calls = true (зафиксировано при сборе заявки)',0,0,1,0.44,'Ответил автоответчик, оставлено голосовое сообщение.',NULL,1,'2026-10-06 02:00:00','2026-10-06 02:00:08','2026-10-06 02:00:00'),
  ('acl_01dg','tnt_0016','aag_0176','acp_017e','con_00cv','org_00bx',NULL,NULL,'outbound','+998 94 974 34 15','completed','human_handoff',273,0.41,'consent.calls = true (зафиксировано при сборе заявки)',1,0,0,0.41,'Запросил связь с человеком, переведён на менеджера.','[ИИ] Здравствуйте, это ИИ-помощник компании Acme Corp. Звонок записывается для контроля качества.
[Клиент] Да, слушаю.
[ИИ] Удобно обсудить внедрение CRM на 2 минуты?
[Клиент] Давайте, расскажите.
[ИИ] Отлично, задам несколько уточняющих вопросов...',1,'2026-09-21 15:00:00','2026-09-21 15:04:33','2026-09-21 15:00:00'),
  ('acl_01di','tnt_0016','aag_0176','acp_017f','con_00eh','org_00cq','deal_00hi',NULL,'outbound','+998 99 323 89 19','completed','opted_out',284,0.43,'consent.calls = true (зафиксировано при сборе заявки)',1,0,0,0.46,'Клиент запросил исключение из обзвона — добавлен в suppression list.','[ИИ] Здравствуйте, это ИИ-помощник компании Acme Corp. Звонок записывается для контроля качества.
[Клиент] Да, слушаю.
[ИИ] Удобно обсудить внедрение CRM на 2 минуты?
[Клиент] Давайте, расскажите.
[ИИ] Отлично, задам несколько уточняющих вопросов...',1,'2026-10-01 06:00:00','2026-10-01 06:04:44','2026-10-01 06:00:00'),
  ('acl_01dk','tnt_0016','aag_0175','acp_017e','con_00do','org_00b6','deal_00ki',NULL,'outbound','+998 89 741 29 66','completed','qualified',222,0.33,'consent.calls = true (зафиксировано при сборе заявки)',1,1,1,0.89,'Квалифицирован: есть бюджет, потребность в интеграции и ЛПР на линии.','[ИИ] Здравствуйте, это ИИ-помощник компании Acme Corp. Звонок записывается для контроля качества.
[Клиент] Да, слушаю.
[ИИ] Удобно обсудить внедрение CRM на 2 минуты?
[Клиент] Давайте, расскажите.
[ИИ] Отлично, задам несколько уточняющих вопросов...',1,'2026-08-25 07:00:00','2026-08-25 07:03:42','2026-08-25 07:00:00'),
  ('acl_01dm','tnt_0016','aag_0177','acp_017d','con_00dv','org_00d7',NULL,NULL,'outbound','+998 82 373 48 66','completed','opted_out',381,0.57,'consent.calls = true (зафиксировано при сборе заявки)',1,1,1,0.5,'Клиент запросил исключение из обзвона — добавлен в suppression list.','[ИИ] Здравствуйте, это ИИ-помощник компании Acme Corp. Звонок записывается для контроля качества.
[Клиент] Да, слушаю.
[ИИ] Удобно обсудить внедрение CRM на 2 минуты?
[Клиент] Давайте, расскажите.
[ИИ] Отлично, задам несколько уточняющих вопросов...',1,'2026-09-07 04:00:00','2026-09-07 04:06:21','2026-09-07 04:00:00'),
  ('acl_01do','tnt_0016','aag_0179','acp_017f','con_00bd','org_00bp',NULL,NULL,'outbound','+998 71 952 34 69','completed','no_answer',21,0.03,'consent.calls = true (зафиксировано при сборе заявки)',1,0,0,0.61,'Нет ответа после 4 гудков, назначен повторный дозвон.',NULL,1,'2026-09-26 06:00:00','2026-09-26 06:00:21','2026-09-26 06:00:00'),
  ('acl_01dp','tnt_0016','aag_0175','acp_017f','con_00bg','org_00ax',NULL,NULL,'outbound','+998 81 279 95 38','completed','qualified',248,0.37,'consent.calls = true (зафиксировано при сборе заявки)',1,1,1,0.86,'Квалифицирован: есть бюджет, потребность в интеграции и ЛПР на линии.','[ИИ] Здравствуйте, это ИИ-помощник компании Acme Corp. Звонок записывается для контроля качества.
[Клиент] Да, слушаю.
[ИИ] Удобно обсудить внедрение CRM на 2 минуты?
[Клиент] Давайте, расскажите.
[ИИ] Отлично, задам несколько уточняющих вопросов...',1,'2026-09-01 04:00:00','2026-09-01 04:04:08','2026-09-01 04:00:00'),
  ('acl_01dr','tnt_0016','aag_0175',NULL,'con_00c8','org_00e6','deal_00g6',NULL,'outbound','+998 87 590 37 65','completed','qualified',293,0.44,'consent.calls = true (зафиксировано при сборе заявки)',1,0,1,0.82,'Квалифицирован: есть бюджет, потребность в интеграции и ЛПР на линии.','[ИИ] Здравствуйте, это ИИ-помощник компании Acme Corp. Звонок записывается для контроля качества.
[Клиент] Да, слушаю.
[ИИ] Удобно обсудить внедрение CRM на 2 минуты?
[Клиент] Давайте, расскажите.
[ИИ] Отлично, задам несколько уточняющих вопросов...',1,'2026-08-27 09:00:00','2026-08-27 09:04:53','2026-08-27 09:00:00'),
  ('acl_01dt','tnt_0016','aag_0176','acp_017d','con_00b0','org_00c0',NULL,NULL,'outbound','+998 95 553 66 62','completed','qualified',172,0.26,'consent.calls = true (зафиксировано при сборе заявки)',1,1,1,0.89,'Квалифицирован: есть бюджет, потребность в интеграции и ЛПР на линии.','[ИИ] Здравствуйте, это ИИ-помощник компании Acme Corp. Звонок записывается для контроля качества.
[Клиент] Да, слушаю.
[ИИ] Удобно обсудить внедрение CRM на 2 минуты?
[Клиент] Давайте, расскажите.
[ИИ] Отлично, задам несколько уточняющих вопросов...',1,'2026-10-03 01:00:00','2026-10-03 01:02:52','2026-10-03 01:00:00'),
  ('acl_01dv','tnt_0016','aag_0175','acp_017g','con_00ct','org_00e6','deal_00fp',NULL,'outbound','+998 73 285 33 50','completed','qualified',330,0.5,'consent.calls = true (зафиксировано при сборе заявки)',1,0,0,0.77,'Квалифицирован: есть бюджет, потребность в интеграции и ЛПР на линии.','[ИИ] Здравствуйте, это ИИ-помощник компании Acme Corp. Звонок записывается для контроля качества.
[Клиент] Да, слушаю.
[ИИ] Удобно обсудить внедрение CRM на 2 минуты?
[Клиент] Давайте, расскажите.
[ИИ] Отлично, задам несколько уточняющих вопросов...',1,'2026-08-29 20:00:00','2026-08-29 20:05:30','2026-08-29 20:00:00'),
  ('acl_01dx','tnt_0016','aag_0175',NULL,'con_00db','org_00ed','deal_00hc',NULL,'outbound','+998 75 708 84 96','completed','objection',127,0.19,'consent.calls = true (зафиксировано при сборе заявки)',1,0,1,0.41,'Возражение по цене. Агент привёл сравнение с текущими расходами, клиент попросил паузу.','[ИИ] Здравствуйте, это ИИ-помощник компании Acme Corp. Звонок записывается для контроля качества.
[Клиент] Да, слушаю.
[ИИ] Удобно обсудить внедрение CRM на 2 минуты?
[Клиент] Давайте, расскажите.
[ИИ] Отлично, задам несколько уточняющих вопросов...',1,'2026-09-10 20:00:00','2026-09-10 20:02:07','2026-09-10 20:00:00'),
  ('acl_01dz','tnt_0016','aag_0178','acp_017e','con_00e7','org_00dh','deal_00i1',NULL,'outbound','+998 74 159 11 50','completed','objection',79,0.12,'consent.calls = true (зафиксировано при сборе заявки)',1,1,1,0.37,'Возражение по цене. Агент привёл сравнение с текущими расходами, клиент попросил паузу.','[ИИ] Здравствуйте, это ИИ-помощник компании Acme Corp. Звонок записывается для контроля качества.
[Клиент] Да, слушаю.
[ИИ] Удобно обсудить внедрение CRM на 2 минуты?
[Клиент] Давайте, расскажите.
[ИИ] Отлично, задам несколько уточняющих вопросов...',1,'2026-10-07 02:00:00','2026-10-07 02:01:19','2026-10-07 02:00:00'),
  ('acl_01e1','tnt_0016','aag_0176','acp_017g','con_00b8','org_00be',NULL,NULL,'outbound','+998 81 121 81 15','completed','no_answer',15,0.02,'consent.calls = true (зафиксировано при сборе заявки)',1,0,1,0.36,'Нет ответа после 4 гудков, назначен повторный дозвон.',NULL,1,'2026-09-25 22:00:00','2026-09-25 22:00:15','2026-09-25 22:00:00'),
  ('acl_01e2','tnt_0016','aag_0179','acp_017g','con_00ct','org_00c6',NULL,NULL,'outbound','+998 89 272 95 48','completed','voicemail',11,0.02,'consent.calls = true (зафиксировано при сборе заявки)',1,0,0,0.55,'Ответил автоответчик, оставлено голосовое сообщение.',NULL,1,'2026-09-19 16:00:00','2026-09-19 16:00:11','2026-09-19 16:00:00'),
  ('acl_01e3','tnt_0016','aag_0176',NULL,'con_00ch','org_00bu',NULL,NULL,'outbound','+998 85 744 37 83','completed','voicemail',20,0.03,'consent.calls = true (зафиксировано при сборе заявки)',1,0,1,0.53,'Ответил автоответчик, оставлено голосовое сообщение.',NULL,1,'2026-09-11 14:00:00','2026-09-11 14:00:20','2026-09-11 14:00:00'),
  ('acl_01e4','tnt_0016','aag_0179','acp_017d','con_00c4','org_00e9',NULL,NULL,'outbound','+998 94 186 74 78','completed','no_answer',2,0,'consent.calls = true (зафиксировано при сборе заявки)',0,0,1,0.32,'Нет ответа после 4 гудков, назначен повторный дозвон.',NULL,1,'2026-09-02 18:00:00','2026-09-02 18:00:02','2026-09-02 18:00:00'),
  ('acl_01e5','tnt_0016','aag_0179','acp_017d','con_00cl','org_00bp',NULL,NULL,'outbound','+998 74 127 12 45','completed','interested',222,0.33,'consent.calls = true (зафиксировано при сборе заявки)',1,0,1,0.93,'Клиент заинтересовался тарифом Business, запросил коммерческое предложение.','[ИИ] Здравствуйте, это ИИ-помощник компании Acme Corp. Звонок записывается для контроля качества.
[Клиент] Да, слушаю.
[ИИ] Удобно обсудить внедрение CRM на 2 минуты?
[Клиент] Давайте, расскажите.
[ИИ] Отлично, задам несколько уточняющих вопросов...',1,'2026-09-08 18:00:00','2026-09-08 18:03:42','2026-09-08 18:00:00'),
  ('acl_01e8','tnt_0016','aag_0176','acp_017e','con_00aw','org_00d3','deal_00hp',NULL,'outbound','+998 99 118 43 30','completed','human_handoff',260,0.39,'consent.calls = true (зафиксировано при сборе заявки)',1,1,1,0.53,'Запросил связь с человеком, переведён на менеджера.','[ИИ] Здравствуйте, это ИИ-помощник компании Acme Corp. Звонок записывается для контроля качества.
[Клиент] Да, слушаю.
[ИИ] Удобно обсудить внедрение CRM на 2 минуты?
[Клиент] Давайте, расскажите.
[ИИ] Отлично, задам несколько уточняющих вопросов...',1,'2026-08-12 20:00:00','2026-08-12 20:04:20','2026-08-12 20:00:00'),
  ('acl_01ea','tnt_0016','aag_0175',NULL,'con_00bv','org_00dp','deal_00lh',NULL,'outbound','+998 92 570 63 12','completed','no_answer',0,0,'consent.calls = true (зафиксировано при сборе заявки)',0,0,0,0.62,'Нет ответа после 4 гудков, назначен повторный дозвон.',NULL,1,'2026-09-18 23:00:00','2026-09-18 23:00:00','2026-09-18 23:00:00'),
  ('acl_01eb','tnt_0016','aag_0176','acp_017f','con_00d5','org_00be',NULL,NULL,'outbound','+998 76 506 29 57','failed','interested',340,0.51,'consent.calls = true (зафиксировано при сборе заявки)',1,1,1,0.92,'Клиент заинтересовался тарифом Business, запросил коммерческое предложение.','[ИИ] Здравствуйте, это ИИ-помощник компании Acme Corp. Звонок записывается для контроля качества.
[Клиент] Да, слушаю.
[ИИ] Удобно обсудить внедрение CRM на 2 минуты?
[Клиент] Давайте, расскажите.
[ИИ] Отлично, задам несколько уточняющих вопросов...',1,'2026-09-10 01:00:00','2026-09-10 01:05:40','2026-09-10 01:00:00'),
  ('acl_01ed','tnt_0016','aag_0175','acp_017e','con_00dn','org_00bx','deal_00g6',NULL,'outbound','+998 84 838 37 49','completed','voicemail',8,0.01,'consent.calls = true (зафиксировано при сборе заявки)',0,0,0,0.7,'Ответил автоответчик, оставлено голосовое сообщение.',NULL,1,'2026-08-21 15:00:00','2026-08-21 15:00:08','2026-08-21 15:00:00'),
  ('acl_01ee','tnt_0016','aag_0177','acp_017d','con_00b8','org_00be',NULL,'ord_00uz','outbound','+998 73 488 15 85','completed','interested',240,0.36,'consent.calls = true (зафиксировано при сборе заявки)',1,1,1,0.92,'Клиент заинтересовался тарифом Business, запросил коммерческое предложение.','[ИИ] Здравствуйте, это ИИ-помощник компании Acme Corp. Звонок записывается для контроля качества.
[Клиент] Да, слушаю.
[ИИ] Удобно обсудить внедрение CRM на 2 минуты?
[Клиент] Давайте, расскажите.
[ИИ] Отлично, задам несколько уточняющих вопросов...',1,'2026-10-06 22:00:00','2026-10-06 22:04:00','2026-10-06 22:00:00'),
  ('acl_01eg','tnt_0016','aag_0175','acp_017f','con_00bt','org_00dk',NULL,NULL,'outbound','+998 95 244 67 94','completed','not_interested',234,0.35,'consent.calls = true (зафиксировано при сборе заявки)',1,1,1,0.61,'Клиент сообщил об отсутствии потребности на текущий момент.','[ИИ] Здравствуйте, это ИИ-помощник компании Acme Corp. Звонок записывается для контроля качества.
[Клиент] Да, слушаю.
[ИИ] Удобно обсудить внедрение CRM на 2 минуты?
[Клиент] Нам это сейчас не нужно.
[ИИ] Отлично, задам несколько уточняющих вопросов...',1,'2026-08-18 22:00:00','2026-08-18 22:03:54','2026-08-18 22:00:00'),
  ('acl_01ei','tnt_0016','aag_0177','acp_017d','con_00b3','org_00cn',NULL,NULL,'outbound','+998 70 888 72 15','completed','voicemail',14,0.02,'consent.calls = true (зафиксировано при сборе заявки)',1,0,1,0.35,'Ответил автоответчик, оставлено голосовое сообщение.',NULL,1,'2026-08-20 16:00:00','2026-08-20 16:00:14','2026-08-20 16:00:00'),
  ('acl_01ej','tnt_0016','aag_0176','acp_017e','con_00bf','org_00dh',NULL,NULL,'outbound','+998 71 609 50 39','completed','no_answer',25,0.04,'consent.calls = true (зафиксировано при сборе заявки)',1,0,1,0.34,'Нет ответа после 4 гудков, назначен повторный дозвон.',NULL,1,'2026-10-06 05:00:00','2026-10-06 05:00:25','2026-10-06 05:00:00'),
  ('acl_01ek','tnt_0016','aag_0176','acp_017g','con_00dq','org_00du',NULL,NULL,'outbound','+998 85 250 99 39','completed','voicemail',22,0.03,'consent.calls = true (зафиксировано при сборе заявки)',1,0,0,0.5,'Ответил автоответчик, оставлено голосовое сообщение.',NULL,1,'2026-10-04 01:00:00','2026-10-04 01:00:22','2026-10-04 01:00:00'),
  ('acl_01el','tnt_0016','aag_0177','acp_017e','con_00ct','org_00c3',NULL,NULL,'outbound','+998 89 482 93 52','completed','objection',106,0.16,'consent.calls = true (зафиксировано при сборе заявки)',1,1,1,0.36,'Возражение по цене. Агент привёл сравнение с текущими расходами, клиент попросил паузу.','[ИИ] Здравствуйте, это ИИ-помощник компании Acme Corp. Звонок записывается для контроля качества.
[Клиент] Да, слушаю.
[ИИ] Удобно обсудить внедрение CRM на 2 минуты?
[Клиент] Давайте, расскажите.
[ИИ] Отлично, задам несколько уточняющих вопросов...',1,'2026-09-29 06:00:00','2026-09-29 06:01:46','2026-09-29 06:00:00'),
  ('acl_01en','tnt_0016','aag_0175','acp_017g','con_00bo','org_00d7','deal_00m6',NULL,'outbound','+998 98 484 44 85','completed','voicemail',17,0.03,'consent.calls = true (зафиксировано при сборе заявки)',1,0,0,0.41,'Ответил автоответчик, оставлено голосовое сообщение.',NULL,1,'2026-09-11 19:00:00','2026-09-11 19:00:17','2026-09-11 19:00:00'),
  ('acl_01eo','tnt_0016','aag_0175',NULL,'con_00ca','org_00dx',NULL,NULL,'outbound','+998 91 635 53 34','completed','voicemail',8,0.01,'consent.calls = true (зафиксировано при сборе заявки)',0,0,1,0.35,'Ответил автоответчик, оставлено голосовое сообщение.',NULL,1,'2026-08-17 07:00:00','2026-08-17 07:00:08','2026-08-17 07:00:00'),
  ('acl_01ep','tnt_0016','aag_0176','acp_017f','con_00cf','org_00be',NULL,NULL,'outbound','+998 80 394 59 38','completed','objection',405,0.61,'consent.calls = true (зафиксировано при сборе заявки)',1,1,1,0.3,'Возражение по цене. Агент привёл сравнение с текущими расходами, клиент попросил паузу.','[ИИ] Здравствуйте, это ИИ-помощник компании Acme Corp. Звонок записывается для контроля качества.
[Клиент] Да, слушаю.
[ИИ] Удобно обсудить внедрение CRM на 2 минуты?
[Клиент] Давайте, расскажите.
[ИИ] Отлично, задам несколько уточняющих вопросов...',1,'2026-09-16 03:00:00','2026-09-16 03:06:45','2026-09-16 03:00:00'),
  ('acl_01er','tnt_0016','aag_0175','acp_017e','con_00dt','org_00c6',NULL,NULL,'outbound','+998 88 681 75 78','completed','not_interested',141,0.21,'consent.calls = true (зафиксировано при сборе заявки)',1,0,0,0.54,'Клиент сообщил об отсутствии потребности на текущий момент.','[ИИ] Здравствуйте, это ИИ-помощник компании Acme Corp. Звонок записывается для контроля качества.
[Клиент] Да, слушаю.
[ИИ] Удобно обсудить внедрение CRM на 2 минуты?
[Клиент] Нам это сейчас не нужно.
[ИИ] Отлично, задам несколько уточняющих вопросов...',1,'2026-08-24 14:00:00','2026-08-24 14:02:21','2026-08-24 14:00:00'),
  ('acl_01et','tnt_0016','aag_0177',NULL,'con_00co','org_00dc',NULL,NULL,'outbound','+998 94 749 75 76','completed','interested',124,0.19,'consent.calls = true (зафиксировано при сборе заявки)',1,0,0,0.92,'Клиент заинтересовался тарифом Business, запросил коммерческое предложение.','[ИИ] Здравствуйте, это ИИ-помощник компании Acme Corp. Звонок записывается для контроля качества.
[Клиент] Да, слушаю.
[ИИ] Удобно обсудить внедрение CRM на 2 минуты?
[Клиент] Давайте, расскажите.
[ИИ] Отлично, задам несколько уточняющих вопросов...',1,'2026-09-19 02:00:00','2026-09-19 02:02:04','2026-09-19 02:00:00'),
  ('acl_01ev','tnt_0016','aag_0178',NULL,'con_00cv','org_00au','deal_00gt',NULL,'outbound','+998 74 116 11 62','completed','not_interested',47,0.07,'consent.calls = true (зафиксировано при сборе заявки)',1,1,1,0.37,'Клиент сообщил об отсутствии потребности на текущий момент.','[ИИ] Здравствуйте, это ИИ-помощник компании Acme Corp. Звонок записывается для контроля качества.
[Клиент] Да, слушаю.
[ИИ] Удобно обсудить внедрение CRM на 2 минуты?
[Клиент] Нам это сейчас не нужно.
[ИИ] Отлично, задам несколько уточняющих вопросов...',1,'2026-09-24 17:00:00','2026-09-24 17:00:47','2026-09-24 17:00:00');
INSERT INTO ai_calls VALUES
  ('acl_01ex','tnt_0016','aag_0175','acp_017g','con_00cx','org_00ax',NULL,NULL,'outbound','+998 81 370 36 28','completed','opted_out',249,0.37,'consent.calls = true (зафиксировано при сборе заявки)',1,1,1,0.36,'Клиент запросил исключение из обзвона — добавлен в suppression list.','[ИИ] Здравствуйте, это ИИ-помощник компании Acme Corp. Звонок записывается для контроля качества.
[Клиент] Да, слушаю.
[ИИ] Удобно обсудить внедрение CRM на 2 минуты?
[Клиент] Давайте, расскажите.
[ИИ] Отлично, задам несколько уточняющих вопросов...',1,'2026-10-06 23:00:00','2026-10-06 23:04:09','2026-10-06 23:00:00'),
  ('acl_01ez','tnt_0016','aag_0178',NULL,'con_00ef','org_00cy',NULL,NULL,'outbound','+998 74 778 40 97','completed','not_interested',363,0.54,'consent.calls = true (зафиксировано при сборе заявки)',1,0,0,0.4,'Клиент сообщил об отсутствии потребности на текущий момент.','[ИИ] Здравствуйте, это ИИ-помощник компании Acme Corp. Звонок записывается для контроля качества.
[Клиент] Да, слушаю.
[ИИ] Удобно обсудить внедрение CRM на 2 минуты?
[Клиент] Нам это сейчас не нужно.
[ИИ] Отлично, задам несколько уточняющих вопросов...',1,'2026-09-05 19:00:00','2026-09-05 19:06:03','2026-09-05 19:00:00'),
  ('acl_01f1','tnt_0016','aag_0176','acp_017g','con_00db','org_00be',NULL,NULL,'outbound','+998 91 187 93 36','completed','qualified',268,0.4,'consent.calls = true (зафиксировано при сборе заявки)',1,1,1,0.87,'Квалифицирован: есть бюджет, потребность в интеграции и ЛПР на линии.','[ИИ] Здравствуйте, это ИИ-помощник компании Acme Corp. Звонок записывается для контроля качества.
[Клиент] Да, слушаю.
[ИИ] Удобно обсудить внедрение CRM на 2 минуты?
[Клиент] Давайте, расскажите.
[ИИ] Отлично, задам несколько уточняющих вопросов...',1,'2026-09-19 15:00:00','2026-09-19 15:04:28','2026-09-19 15:00:00'),
  ('acl_01f3','tnt_0016','aag_0179','acp_017d','con_00dr','org_00au','deal_00i5',NULL,'outbound','+998 90 822 63 47','completed','no_answer',11,0.02,'consent.calls = true (зафиксировано при сборе заявки)',1,0,1,0.37,'Нет ответа после 4 гудков, назначен повторный дозвон.',NULL,1,'2026-09-15 14:00:00','2026-09-15 14:00:11','2026-09-15 14:00:00'),
  ('acl_01f4','tnt_0016','aag_0178','acp_017g','con_00ec','org_00cy',NULL,NULL,'outbound','+998 93 412 65 91','completed','objection',51,0.08,'consent.calls = true (зафиксировано при сборе заявки)',1,1,1,0.53,'Возражение по цене. Агент привёл сравнение с текущими расходами, клиент попросил паузу.','[ИИ] Здравствуйте, это ИИ-помощник компании Acme Corp. Звонок записывается для контроля качества.
[Клиент] Да, слушаю.
[ИИ] Удобно обсудить внедрение CRM на 2 минуты?
[Клиент] Давайте, расскажите.
[ИИ] Отлично, задам несколько уточняющих вопросов...',1,'2026-09-30 07:00:00','2026-09-30 07:00:51','2026-09-30 07:00:00'),
  ('acl_01f6','tnt_0016','aag_0176','acp_017e','con_00av','org_00au',NULL,NULL,'outbound','+998 77 639 33 82','failed','opted_out',220,0.33,'consent.calls = true (зафиксировано при сборе заявки)',1,1,1,0.68,'Клиент запросил исключение из обзвона — добавлен в suppression list.','[ИИ] Здравствуйте, это ИИ-помощник компании Acme Corp. Звонок записывается для контроля качества.
[Клиент] Да, слушаю.
[ИИ] Удобно обсудить внедрение CRM на 2 минуты?
[Клиент] Давайте, расскажите.
[ИИ] Отлично, задам несколько уточняющих вопросов...',1,'2026-08-27 17:00:00','2026-08-27 17:03:40','2026-08-27 17:00:00'),
  ('acl_01f8','tnt_0016','aag_0178','acp_017f','con_00e3','org_00ce',NULL,NULL,'outbound','+998 75 529 80 71','completed','interested',263,0.39,'consent.calls = true (зафиксировано при сборе заявки)',1,0,1,0.95,'Клиент заинтересовался тарифом Business, запросил коммерческое предложение.','[ИИ] Здравствуйте, это ИИ-помощник компании Acme Corp. Звонок записывается для контроля качества.
[Клиент] Да, слушаю.
[ИИ] Удобно обсудить внедрение CRM на 2 минуты?
[Клиент] Давайте, расскажите.
[ИИ] Отлично, задам несколько уточняющих вопросов...',1,'2026-09-07 09:00:00','2026-09-07 09:04:23','2026-09-07 09:00:00'),
  ('acl_01fa','tnt_0016','aag_0177','acp_017e','con_00eb','org_00e6',NULL,NULL,'outbound','+998 99 914 96 39','completed','not_interested',352,0.53,'consent.calls = true (зафиксировано при сборе заявки)',1,1,1,0.6,'Клиент сообщил об отсутствии потребности на текущий момент.','[ИИ] Здравствуйте, это ИИ-помощник компании Acme Corp. Звонок записывается для контроля качества.
[Клиент] Да, слушаю.
[ИИ] Удобно обсудить внедрение CRM на 2 минуты?
[Клиент] Нам это сейчас не нужно.
[ИИ] Отлично, задам несколько уточняющих вопросов...',1,'2026-09-04 08:00:00','2026-09-04 08:05:52','2026-09-04 08:00:00'),
  ('acl_01fc','tnt_0016','aag_0178','acp_017d','con_00dq','org_00e9','deal_00gn',NULL,'outbound','+998 91 538 24 60','completed','no_answer',10,0.02,'consent.calls = true (зафиксировано при сборе заявки)',0,0,0,0.64,'Нет ответа после 4 гудков, назначен повторный дозвон.',NULL,1,'2026-09-04 02:00:00','2026-09-04 02:00:10','2026-09-04 02:00:00'),
  ('acl_01fd','tnt_0016','aag_0176','acp_017e','con_00b8','org_00c3',NULL,NULL,'outbound','+998 89 985 91 39','completed','qualified',195,0.29,'consent.calls = true (зафиксировано при сборе заявки)',1,0,0,0.74,'Квалифицирован: есть бюджет, потребность в интеграции и ЛПР на линии.','[ИИ] Здравствуйте, это ИИ-помощник компании Acme Corp. Звонок записывается для контроля качества.
[Клиент] Да, слушаю.
[ИИ] Удобно обсудить внедрение CRM на 2 минуты?
[Клиент] Давайте, расскажите.
[ИИ] Отлично, задам несколько уточняющих вопросов...',1,'2026-10-07 15:00:00','2026-10-07 15:03:15','2026-10-07 15:00:00'),
  ('acl_01ff','tnt_0016','aag_0179','acp_017f','con_00c1','org_00e9','deal_00iq',NULL,'outbound','+998 82 910 76 37','completed','interested',310,0.47,'consent.calls = true (зафиксировано при сборе заявки)',1,1,1,0.92,'Клиент заинтересовался тарифом Business, запросил коммерческое предложение.','[ИИ] Здравствуйте, это ИИ-помощник компании Acme Corp. Звонок записывается для контроля качества.
[Клиент] Да, слушаю.
[ИИ] Удобно обсудить внедрение CRM на 2 минуты?
[Клиент] Давайте, расскажите.
[ИИ] Отлично, задам несколько уточняющих вопросов...',1,'2026-09-18 00:00:00','2026-09-18 00:05:10','2026-09-18 00:00:00'),
  ('acl_01fi','tnt_0016','aag_0179','acp_017d','con_00dt','org_00e2','deal_00hc',NULL,'outbound','+998 87 265 73 19','completed','opted_out',398,0.6,'consent.calls = true (зафиксировано при сборе заявки)',1,0,0,0.61,'Клиент запросил исключение из обзвона — добавлен в suppression list.','[ИИ] Здравствуйте, это ИИ-помощник компании Acme Corp. Звонок записывается для контроля качества.
[Клиент] Да, слушаю.
[ИИ] Удобно обсудить внедрение CRM на 2 минуты?
[Клиент] Давайте, расскажите.
[ИИ] Отлично, задам несколько уточняющих вопросов...',1,'2026-09-09 18:00:00','2026-09-09 18:06:38','2026-09-09 18:00:00'),
  ('acl_01fk','tnt_0016','aag_0179',NULL,'con_00d1','org_00ce',NULL,NULL,'outbound','+998 85 893 61 58','failed','human_handoff',79,0.12,'consent.calls = true (зафиксировано при сборе заявки)',1,1,1,0.51,'Запросил связь с человеком, переведён на менеджера.','[ИИ] Здравствуйте, это ИИ-помощник компании Acme Corp. Звонок записывается для контроля качества.
[Клиент] Да, слушаю.
[ИИ] Удобно обсудить внедрение CRM на 2 минуты?
[Клиент] Давайте, расскажите.
[ИИ] Отлично, задам несколько уточняющих вопросов...',1,'2026-09-19 05:00:00','2026-09-19 05:01:19','2026-09-19 05:00:00'),
  ('acl_01fm','tnt_0016','aag_0178','acp_017d','con_00bw','org_00b9',NULL,NULL,'outbound','+998 86 624 20 12','completed','human_handoff',344,0.52,'consent.calls = true (зафиксировано при сборе заявки)',1,0,1,0.53,'Запросил связь с человеком, переведён на менеджера.','[ИИ] Здравствуйте, это ИИ-помощник компании Acme Corp. Звонок записывается для контроля качества.
[Клиент] Да, слушаю.
[ИИ] Удобно обсудить внедрение CRM на 2 минуты?
[Клиент] Давайте, расскажите.
[ИИ] Отлично, задам несколько уточняющих вопросов...',1,'2026-08-27 15:00:00','2026-08-27 15:05:44','2026-08-27 15:00:00'),
  ('acl_01fo','tnt_0016','aag_0178','acp_017d','con_00c4','org_00bx',NULL,NULL,'outbound','+998 81 926 42 79','failed','qualified',373,0.56,'consent.calls = true (зафиксировано при сборе заявки)',1,0,0,0.84,'Квалифицирован: есть бюджет, потребность в интеграции и ЛПР на линии.','[ИИ] Здравствуйте, это ИИ-помощник компании Acme Corp. Звонок записывается для контроля качества.
[Клиент] Да, слушаю.
[ИИ] Удобно обсудить внедрение CRM на 2 минуты?
[Клиент] Давайте, расскажите.
[ИИ] Отлично, задам несколько уточняющих вопросов...',1,'2026-09-19 18:00:00','2026-09-19 18:06:13','2026-09-19 18:00:00'),
  ('acl_01fq','tnt_0016','aag_0177','acp_017d','con_00df','org_00c6',NULL,NULL,'outbound','+998 84 465 95 74','completed','qualified',361,0.54,'consent.calls = true (зафиксировано при сборе заявки)',1,0,0,0.78,'Квалифицирован: есть бюджет, потребность в интеграции и ЛПР на линии.','[ИИ] Здравствуйте, это ИИ-помощник компании Acme Corp. Звонок записывается для контроля качества.
[Клиент] Да, слушаю.
[ИИ] Удобно обсудить внедрение CRM на 2 минуты?
[Клиент] Давайте, расскажите.
[ИИ] Отлично, задам несколько уточняющих вопросов...',1,'2026-08-25 18:00:00','2026-08-25 18:06:01','2026-08-25 18:00:00'),
  ('acl_01fs','tnt_0016','aag_0176','acp_017g','con_00bn','org_00cn',NULL,NULL,'outbound','+998 72 416 59 49','completed','opted_out',158,0.24,'consent.calls = true (зафиксировано при сборе заявки)',1,1,1,0.42,'Клиент запросил исключение из обзвона — добавлен в suppression list.','[ИИ] Здравствуйте, это ИИ-помощник компании Acme Corp. Звонок записывается для контроля качества.
[Клиент] Да, слушаю.
[ИИ] Удобно обсудить внедрение CRM на 2 минуты?
[Клиент] Давайте, расскажите.
[ИИ] Отлично, задам несколько уточняющих вопросов...',1,'2026-08-22 09:00:00','2026-08-22 09:02:38','2026-08-22 09:00:00'),
  ('acl_01fu','tnt_0016','aag_0175','acp_017f','con_00bw','org_00bp','deal_00j3',NULL,'outbound','+998 89 195 88 38','completed','interested',123,0.18,'consent.calls = true (зафиксировано при сборе заявки)',1,1,1,0.94,'Клиент заинтересовался тарифом Business, запросил коммерческое предложение.','[ИИ] Здравствуйте, это ИИ-помощник компании Acme Corp. Звонок записывается для контроля качества.
[Клиент] Да, слушаю.
[ИИ] Удобно обсудить внедрение CRM на 2 минуты?
[Клиент] Давайте, расскажите.
[ИИ] Отлично, задам несколько уточняющих вопросов...',1,'2026-09-11 09:00:00','2026-09-11 09:02:03','2026-09-11 09:00:00'),
  ('acl_01fw','tnt_0016','aag_0176','acp_017d','con_00d4','org_00e2',NULL,NULL,'outbound','+998 71 202 15 77','completed','no_answer',15,0.02,'consent.calls = true (зафиксировано при сборе заявки)',1,0,1,0.57,'Нет ответа после 4 гудков, назначен повторный дозвон.',NULL,1,'2026-08-17 19:00:00','2026-08-17 19:00:15','2026-08-17 19:00:00'),
  ('acl_01fx','tnt_0016','aag_0179','acp_017f','con_00d8','org_00cb',NULL,NULL,'outbound','+998 74 430 67 45','completed','objection',283,0.42,'consent.calls = true (зафиксировано при сборе заявки)',1,1,1,0.33,'Возражение по цене. Агент привёл сравнение с текущими расходами, клиент попросил паузу.','[ИИ] Здравствуйте, это ИИ-помощник компании Acme Corp. Звонок записывается для контроля качества.
[Клиент] Да, слушаю.
[ИИ] Удобно обсудить внедрение CRM на 2 минуты?
[Клиент] Давайте, расскажите.
[ИИ] Отлично, задам несколько уточняющих вопросов...',1,'2026-09-12 01:00:00','2026-09-12 01:04:43','2026-09-12 01:00:00'),
  ('acl_01fz','tnt_0016','aag_0175','acp_017d','con_00c8','org_00dx',NULL,'ord_00tw','outbound','+998 98 290 93 24','completed','interested',217,0.33,'consent.calls = true (зафиксировано при сборе заявки)',1,0,1,0.83,'Клиент заинтересовался тарифом Business, запросил коммерческое предложение.','[ИИ] Здравствуйте, это ИИ-помощник компании Acme Corp. Звонок записывается для контроля качества.
[Клиент] Да, слушаю.
[ИИ] Удобно обсудить внедрение CRM на 2 минуты?
[Клиент] Давайте, расскажите.
[ИИ] Отлично, задам несколько уточняющих вопросов...',1,'2026-08-22 04:00:00','2026-08-22 04:03:37','2026-08-22 04:00:00'),
  ('acl_01g2','tnt_0016','aag_0178','acp_017e','con_00ca','org_00cb',NULL,NULL,'outbound','+998 94 766 38 69','completed','opted_out',104,0.16,'consent.calls = true (зафиксировано при сборе заявки)',1,1,1,0.43,'Клиент запросил исключение из обзвона — добавлен в suppression list.','[ИИ] Здравствуйте, это ИИ-помощник компании Acme Corp. Звонок записывается для контроля качества.
[Клиент] Да, слушаю.
[ИИ] Удобно обсудить внедрение CRM на 2 минуты?
[Клиент] Давайте, расскажите.
[ИИ] Отлично, задам несколько уточняющих вопросов...',1,'2026-09-05 17:00:00','2026-09-05 17:01:44','2026-09-05 17:00:00'),
  ('acl_01g4','tnt_0016','aag_0175','acp_017g','con_00ct','org_00b1',NULL,NULL,'outbound','+998 72 333 42 73','completed','human_handoff',302,0.45,'consent.calls = true (зафиксировано при сборе заявки)',1,0,0,0.39,'Запросил связь с человеком, переведён на менеджера.','[ИИ] Здравствуйте, это ИИ-помощник компании Acme Corp. Звонок записывается для контроля качества.
[Клиент] Да, слушаю.
[ИИ] Удобно обсудить внедрение CRM на 2 минуты?
[Клиент] Давайте, расскажите.
[ИИ] Отлично, задам несколько уточняющих вопросов...',1,'2026-10-09 07:00:00','2026-10-09 07:05:02','2026-10-09 07:00:00'),
  ('acl_01g6','tnt_0016','aag_0177','acp_017g','con_00dj','org_00bm','deal_00fj',NULL,'outbound','+998 90 838 78 18','completed','opted_out',192,0.29,'consent.calls = true (зафиксировано при сборе заявки)',1,1,1,0.67,'Клиент запросил исключение из обзвона — добавлен в suppression list.','[ИИ] Здравствуйте, это ИИ-помощник компании Acme Corp. Звонок записывается для контроля качества.
[Клиент] Да, слушаю.
[ИИ] Удобно обсудить внедрение CRM на 2 минуты?
[Клиент] Давайте, расскажите.
[ИИ] Отлично, задам несколько уточняющих вопросов...',1,'2026-08-28 00:00:00','2026-08-28 00:03:12','2026-08-28 00:00:00'),
  ('acl_01g8','tnt_0016','aag_0179','acp_017d','con_00d0','org_00d7',NULL,NULL,'outbound','+998 86 957 40 97','completed','qualified',289,0.43,'consent.calls = true (зафиксировано при сборе заявки)',1,1,1,0.74,'Квалифицирован: есть бюджет, потребность в интеграции и ЛПР на линии.','[ИИ] Здравствуйте, это ИИ-помощник компании Acme Corp. Звонок записывается для контроля качества.
[Клиент] Да, слушаю.
[ИИ] Удобно обсудить внедрение CRM на 2 минуты?
[Клиент] Давайте, расскажите.
[ИИ] Отлично, задам несколько уточняющих вопросов...',1,'2026-10-07 19:00:00','2026-10-07 19:04:49','2026-10-07 19:00:00'),
  ('acl_01ga','tnt_0016','aag_0175','acp_017g','con_00ec','org_00d7','deal_00i5',NULL,'outbound','+998 70 849 49 44','completed','no_answer',9,0.01,'consent.calls = true (зафиксировано при сборе заявки)',0,0,1,0.49,'Нет ответа после 4 гудков, назначен повторный дозвон.',NULL,1,'2026-10-04 04:00:00','2026-10-04 04:00:09','2026-10-04 04:00:00'),
  ('acl_01gb','tnt_0016','aag_0176','acp_017g','con_00bd','org_00c6',NULL,NULL,'outbound','+998 72 357 43 52','completed','qualified',359,0.54,'consent.calls = true (зафиксировано при сборе заявки)',1,0,0,0.71,'Квалифицирован: есть бюджет, потребность в интеграции и ЛПР на линии.','[ИИ] Здравствуйте, это ИИ-помощник компании Acme Corp. Звонок записывается для контроля качества.
[Клиент] Да, слушаю.
[ИИ] Удобно обсудить внедрение CRM на 2 минуты?
[Клиент] Давайте, расскажите.
[ИИ] Отлично, задам несколько уточняющих вопросов...',1,'2026-08-14 08:00:00','2026-08-14 08:05:59','2026-08-14 08:00:00'),
  ('acl_01gd','tnt_0016','aag_0176','acp_017e','con_00bc','org_00au',NULL,NULL,'outbound','+998 77 538 45 14','completed','voicemail',19,0.03,'consent.calls = true (зафиксировано при сборе заявки)',1,0,1,0.53,'Ответил автоответчик, оставлено голосовое сообщение.',NULL,1,'2026-09-19 13:00:00','2026-09-19 13:00:19','2026-09-19 13:00:00'),
  ('acl_01ge','tnt_0016','aag_0175','acp_017e','con_00cc','org_00c6',NULL,NULL,'outbound','+998 88 189 74 69','completed','not_interested',146,0.22,'consent.calls = true (зафиксировано при сборе заявки)',1,1,1,0.52,'Клиент сообщил об отсутствии потребности на текущий момент.','[ИИ] Здравствуйте, это ИИ-помощник компании Acme Corp. Звонок записывается для контроля качества.
[Клиент] Да, слушаю.
[ИИ] Удобно обсудить внедрение CRM на 2 минуты?
[Клиент] Нам это сейчас не нужно.
[ИИ] Отлично, задам несколько уточняющих вопросов...',1,'2026-08-26 22:00:00','2026-08-26 22:02:26','2026-08-26 22:00:00'),
  ('acl_01gg','tnt_0016','aag_0178','acp_017d','con_00ek','org_00cy',NULL,NULL,'outbound','+998 76 837 49 89','completed','human_handoff',339,0.51,'consent.calls = true (зафиксировано при сборе заявки)',1,0,0,0.61,'Запросил связь с человеком, переведён на менеджера.','[ИИ] Здравствуйте, это ИИ-помощник компании Acme Corp. Звонок записывается для контроля качества.
[Клиент] Да, слушаю.
[ИИ] Удобно обсудить внедрение CRM на 2 минуты?
[Клиент] Давайте, расскажите.
[ИИ] Отлично, задам несколько уточняющих вопросов...',1,'2026-09-19 19:00:00','2026-09-19 19:05:39','2026-09-19 19:00:00'),
  ('acl_01gi','tnt_0016','aag_0176','acp_017e','con_00bn','org_00b9',NULL,NULL,'outbound','+998 70 268 54 48','completed','qualified',92,0.14,'consent.calls = true (зафиксировано при сборе заявки)',1,0,0,0.89,'Квалифицирован: есть бюджет, потребность в интеграции и ЛПР на линии.','[ИИ] Здравствуйте, это ИИ-помощник компании Acme Corp. Звонок записывается для контроля качества.
[Клиент] Да, слушаю.
[ИИ] Удобно обсудить внедрение CRM на 2 минуты?
[Клиент] Давайте, расскажите.
[ИИ] Отлично, задам несколько уточняющих вопросов...',1,'2026-09-24 13:00:00','2026-09-24 13:01:32','2026-09-24 13:00:00'),
  ('acl_01gk','tnt_0016','aag_0178',NULL,'con_00cd','org_00b1',NULL,NULL,'outbound','+998 79 968 22 36','completed','opted_out',342,0.51,'consent.calls = true (зафиксировано при сборе заявки)',1,0,0,0.66,'Клиент запросил исключение из обзвона — добавлен в suppression list.','[ИИ] Здравствуйте, это ИИ-помощник компании Acme Corp. Звонок записывается для контроля качества.
[Клиент] Да, слушаю.
[ИИ] Удобно обсудить внедрение CRM на 2 минуты?
[Клиент] Давайте, расскажите.
[ИИ] Отлично, задам несколько уточняющих вопросов...',1,'2026-10-01 08:00:00','2026-10-01 08:05:42','2026-10-01 08:00:00'),
  ('acl_01gm','tnt_0016','aag_0176','acp_017d','con_00c9','org_00ci',NULL,NULL,'outbound','+998 87 933 27 75','failed','not_interested',158,0.24,'consent.calls = true (зафиксировано при сборе заявки)',1,1,1,0.42,'Клиент сообщил об отсутствии потребности на текущий момент.','[ИИ] Здравствуйте, это ИИ-помощник компании Acme Corp. Звонок записывается для контроля качества.
[Клиент] Да, слушаю.
[ИИ] Удобно обсудить внедрение CRM на 2 минуты?
[Клиент] Нам это сейчас не нужно.
[ИИ] Отлично, задам несколько уточняющих вопросов...',1,'2026-08-12 17:00:00','2026-08-12 17:02:38','2026-08-12 17:00:00'),
  ('acl_01go','tnt_0016','aag_0177','acp_017f','con_00eg','org_00cu',NULL,NULL,'outbound','+998 86 561 16 30','completed','objection',222,0.33,'consent.calls = true (зафиксировано при сборе заявки)',1,0,1,0.48,'Возражение по цене. Агент привёл сравнение с текущими расходами, клиент попросил паузу.','[ИИ] Здравствуйте, это ИИ-помощник компании Acme Corp. Звонок записывается для контроля качества.
[Клиент] Да, слушаю.
[ИИ] Удобно обсудить внедрение CRM на 2 минуты?
[Клиент] Давайте, расскажите.
[ИИ] Отлично, задам несколько уточняющих вопросов...',1,'2026-10-05 04:00:00','2026-10-05 04:03:42','2026-10-05 04:00:00'),
  ('acl_01gq','tnt_0016','aag_0179',NULL,'con_00e7','org_00dp',NULL,NULL,'outbound','+998 77 855 75 82','completed','qualified',240,0.36,'consent.calls = true (зафиксировано при сборе заявки)',1,1,1,0.79,'Квалифицирован: есть бюджет, потребность в интеграции и ЛПР на линии.','[ИИ] Здравствуйте, это ИИ-помощник компании Acme Corp. Звонок записывается для контроля качества.
[Клиент] Да, слушаю.
[ИИ] Удобно обсудить внедрение CRM на 2 минуты?
[Клиент] Давайте, расскажите.
[ИИ] Отлично, задам несколько уточняющих вопросов...',1,'2026-10-04 14:00:00','2026-10-04 14:04:00','2026-10-04 14:00:00'),
  ('acl_01gs','tnt_0016','aag_0175','acp_017e','con_00eh','org_00cy','deal_00hm',NULL,'outbound','+998 76 136 69 18','completed','human_handoff',385,0.58,'consent.calls = true (зафиксировано при сборе заявки)',1,0,0,0.36,'Запросил связь с человеком, переведён на менеджера.','[ИИ] Здравствуйте, это ИИ-помощник компании Acme Corp. Звонок записывается для контроля качества.
[Клиент] Да, слушаю.
[ИИ] Удобно обсудить внедрение CRM на 2 минуты?
[Клиент] Давайте, расскажите.
[ИИ] Отлично, задам несколько уточняющих вопросов...',1,'2026-09-01 05:00:00','2026-09-01 05:06:25','2026-09-01 05:00:00'),
  ('acl_01gu','tnt_0016','aag_0178','acp_017e','con_00cx','org_00ax','deal_00kv',NULL,'outbound','+998 80 517 55 45','completed','human_handoff',102,0.15,'consent.calls = true (зафиксировано при сборе заявки)',1,0,0,0.37,'Запросил связь с человеком, переведён на менеджера.','[ИИ] Здравствуйте, это ИИ-помощник компании Acme Corp. Звонок записывается для контроля качества.
[Клиент] Да, слушаю.
[ИИ] Удобно обсудить внедрение CRM на 2 минуты?
[Клиент] Давайте, расскажите.
[ИИ] Отлично, задам несколько уточняющих вопросов...',1,'2026-09-24 16:00:00','2026-09-24 16:01:42','2026-09-24 16:00:00'),
  ('acl_01gw','tnt_0016','aag_0175','acp_017d','con_00do','org_00ei',NULL,NULL,'outbound','+998 96 846 42 20','completed','qualified',390,0.59,'consent.calls = true (зафиксировано при сборе заявки)',1,1,1,0.83,'Квалифицирован: есть бюджет, потребность в интеграции и ЛПР на линии.','[ИИ] Здравствуйте, это ИИ-помощник компании Acme Corp. Звонок записывается для контроля качества.
[Клиент] Да, слушаю.
[ИИ] Удобно обсудить внедрение CRM на 2 минуты?
[Клиент] Давайте, расскажите.
[ИИ] Отлично, задам несколько уточняющих вопросов...',1,'2026-08-23 09:00:00','2026-08-23 09:06:30','2026-08-23 09:00:00'),
  ('acl_01gy','tnt_0016','aag_0176','acp_017e','con_00dl','org_00b6',NULL,NULL,'outbound','+998 98 232 40 77','completed','interested',265,0.4,'consent.calls = true (зафиксировано при сборе заявки)',1,0,0,0.91,'Клиент заинтересовался тарифом Business, запросил коммерческое предложение.','[ИИ] Здравствуйте, это ИИ-помощник компании Acme Corp. Звонок записывается для контроля качества.
[Клиент] Да, слушаю.
[ИИ] Удобно обсудить внедрение CRM на 2 минуты?
[Клиент] Давайте, расскажите.
[ИИ] Отлично, задам несколько уточняющих вопросов...',1,'2026-09-24 22:00:00','2026-09-24 22:04:25','2026-09-24 22:00:00'),
  ('acl_01h0','tnt_0016','aag_0176','acp_017g','con_00ba','org_00au',NULL,NULL,'outbound','+998 74 195 76 80','completed','no_answer',11,0.02,'consent.calls = true (зафиксировано при сборе заявки)',1,0,1,0.64,'Нет ответа после 4 гудков, назначен повторный дозвон.',NULL,1,'2026-09-13 14:00:00','2026-09-13 14:00:11','2026-09-13 14:00:00'),
  ('acl_01h1','tnt_0016','aag_0177','acp_017g','con_00ca','org_00b9',NULL,NULL,'outbound','+998 71 580 84 71','completed','no_answer',6,0.01,'consent.calls = true (зафиксировано при сборе заявки)',0,0,1,0.51,'Нет ответа после 4 гудков, назначен повторный дозвон.',NULL,1,'2026-09-13 00:00:00','2026-09-13 00:00:06','2026-09-13 00:00:00'),
  ('acl_01h2','tnt_0016','aag_0177','acp_017f','con_00bt','org_00bm',NULL,NULL,'outbound','+998 78 678 81 13','completed','human_handoff',264,0.4,'consent.calls = true (зафиксировано при сборе заявки)',1,0,0,0.63,'Запросил связь с человеком, переведён на менеджера.','[ИИ] Здравствуйте, это ИИ-помощник компании Acme Corp. Звонок записывается для контроля качества.
[Клиент] Да, слушаю.
[ИИ] Удобно обсудить внедрение CRM на 2 минуты?
[Клиент] Давайте, расскажите.
[ИИ] Отлично, задам несколько уточняющих вопросов...',1,'2026-08-16 20:00:00','2026-08-16 20:04:24','2026-08-16 20:00:00'),
  ('acl_01h4','tnt_0016','aag_0175','acp_017d','con_00bb','org_00bx',NULL,NULL,'outbound','+998 70 241 81 99','completed','human_handoff',73,0.11,'consent.calls = true (зафиксировано при сборе заявки)',1,1,1,0.52,'Запросил связь с человеком, переведён на менеджера.','[ИИ] Здравствуйте, это ИИ-помощник компании Acme Corp. Звонок записывается для контроля качества.
[Клиент] Да, слушаю.
[ИИ] Удобно обсудить внедрение CRM на 2 минуты?
[Клиент] Давайте, расскажите.
[ИИ] Отлично, задам несколько уточняющих вопросов...',1,'2026-10-08 15:00:00','2026-10-08 15:01:13','2026-10-08 15:00:00'),
  ('acl_01h6','tnt_0016','aag_0179','acp_017d','con_00b7','org_00c6',NULL,NULL,'outbound','+998 75 792 55 69','completed','not_interested',292,0.44,'consent.calls = true (зафиксировано при сборе заявки)',1,1,1,0.38,'Клиент сообщил об отсутствии потребности на текущий момент.','[ИИ] Здравствуйте, это ИИ-помощник компании Acme Corp. Звонок записывается для контроля качества.
[Клиент] Да, слушаю.
[ИИ] Удобно обсудить внедрение CRM на 2 минуты?
[Клиент] Нам это сейчас не нужно.
[ИИ] Отлично, задам несколько уточняющих вопросов...',1,'2026-08-23 13:00:00','2026-08-23 13:04:52','2026-08-23 13:00:00'),
  ('acl_01h8','tnt_0016','aag_0178','acp_017g','con_00de','org_00b9','deal_00g6',NULL,'outbound','+998 84 974 32 43','completed','not_interested',88,0.13,'consent.calls = true (зафиксировано при сборе заявки)',1,1,1,0.37,'Клиент сообщил об отсутствии потребности на текущий момент.','[ИИ] Здравствуйте, это ИИ-помощник компании Acme Corp. Звонок записывается для контроля качества.
[Клиент] Да, слушаю.
[ИИ] Удобно обсудить внедрение CRM на 2 минуты?
[Клиент] Нам это сейчас не нужно.
[ИИ] Отлично, задам несколько уточняющих вопросов...',1,'2026-08-13 03:00:00','2026-08-13 03:01:28','2026-08-13 03:00:00'),
  ('acl_01ha','tnt_0016','aag_0176','acp_017f','con_00dy','org_00dp','deal_00ja','ord_00rt','outbound','+998 91 405 79 33','completed','interested',368,0.55,'consent.calls = true (зафиксировано при сборе заявки)',1,1,1,0.88,'Клиент заинтересовался тарифом Business, запросил коммерческое предложение.','[ИИ] Здравствуйте, это ИИ-помощник компании Acme Corp. Звонок записывается для контроля качества.
[Клиент] Да, слушаю.
[ИИ] Удобно обсудить внедрение CRM на 2 минуты?
[Клиент] Давайте, расскажите.
[ИИ] Отлично, задам несколько уточняющих вопросов...',1,'2026-09-22 20:00:00','2026-09-22 20:06:08','2026-09-22 20:00:00'),
  ('acl_01hc','tnt_0016','aag_0178','acp_017g','con_00bi','org_00ce',NULL,'ord_00sj','outbound','+998 86 599 57 61','completed','interested',412,0.62,'consent.calls = true (зафиксировано при сборе заявки)',1,1,1,0.94,'Клиент заинтересовался тарифом Business, запросил коммерческое предложение.','[ИИ] Здравствуйте, это ИИ-помощник компании Acme Corp. Звонок записывается для контроля качества.
[Клиент] Да, слушаю.
[ИИ] Удобно обсудить внедрение CRM на 2 минуты?
[Клиент] Давайте, расскажите.
[ИИ] Отлично, задам несколько уточняющих вопросов...',1,'2026-08-18 07:00:00','2026-08-18 07:06:52','2026-08-18 07:00:00'),
  ('acl_01he','tnt_0016','aag_0178','acp_017g','con_00b7','org_00cb','deal_00gg',NULL,'outbound','+998 76 302 96 90','completed','no_answer',5,0.01,'consent.calls = true (зафиксировано при сборе заявки)',0,0,1,0.69,'Нет ответа после 4 гудков, назначен повторный дозвон.',NULL,1,'2026-09-03 14:00:00','2026-09-03 14:00:05','2026-09-03 14:00:00'),
  ('acl_01hf','tnt_0016','aag_0179','acp_017f','con_00cl','org_00b6',NULL,NULL,'outbound','+998 77 404 90 12','completed','objection',226,0.34,'consent.calls = true (зафиксировано при сборе заявки)',1,1,1,0.63,'Возражение по цене. Агент привёл сравнение с текущими расходами, клиент попросил паузу.','[ИИ] Здравствуйте, это ИИ-помощник компании Acme Corp. Звонок записывается для контроля качества.
[Клиент] Да, слушаю.
[ИИ] Удобно обсудить внедрение CRM на 2 минуты?
[Клиент] Давайте, расскажите.
[ИИ] Отлично, задам несколько уточняющих вопросов...',1,'2026-09-29 02:00:00','2026-09-29 02:03:46','2026-09-29 02:00:00'),
  ('acl_01hh','tnt_0016','aag_0176','acp_017g','con_00d5','org_00cy','deal_00k6','ord_00s8','outbound','+998 85 372 59 37','completed','interested',290,0.43,'consent.calls = true (зафиксировано при сборе заявки)',1,1,1,0.87,'Клиент заинтересовался тарифом Business, запросил коммерческое предложение.','[ИИ] Здравствуйте, это ИИ-помощник компании Acme Corp. Звонок записывается для контроля качества.
[Клиент] Да, слушаю.
[ИИ] Удобно обсудить внедрение CRM на 2 минуты?
[Клиент] Давайте, расскажите.
[ИИ] Отлично, задам несколько уточняющих вопросов...',1,'2026-08-22 18:00:00','2026-08-22 18:04:50','2026-08-22 18:00:00');
INSERT INTO ai_calls VALUES
  ('acl_01hj','tnt_0016','aag_0177','acp_017g','con_00bo','org_00bx',NULL,NULL,'outbound','+998 90 588 65 44','completed','interested',234,0.35,'consent.calls = true (зафиксировано при сборе заявки)',1,0,0,0.84,'Клиент заинтересовался тарифом Business, запросил коммерческое предложение.','[ИИ] Здравствуйте, это ИИ-помощник компании Acme Corp. Звонок записывается для контроля качества.
[Клиент] Да, слушаю.
[ИИ] Удобно обсудить внедрение CRM на 2 минуты?
[Клиент] Давайте, расскажите.
[ИИ] Отлично, задам несколько уточняющих вопросов...',1,'2026-10-01 23:00:00','2026-10-01 23:03:54','2026-10-01 23:00:00'),
  ('acl_01hm','tnt_0016','aag_0175','acp_017d','con_00df','org_00dk',NULL,'ord_00sm','outbound','+998 89 490 43 59','completed','interested',151,0.23,'consent.calls = true (зафиксировано при сборе заявки)',1,0,0,0.9,'Клиент заинтересовался тарифом Business, запросил коммерческое предложение.','[ИИ] Здравствуйте, это ИИ-помощник компании Acme Corp. Звонок записывается для контроля качества.
[Клиент] Да, слушаю.
[ИИ] Удобно обсудить внедрение CRM на 2 минуты?
[Клиент] Давайте, расскажите.
[ИИ] Отлично, задам несколько уточняющих вопросов...',1,'2026-09-05 07:00:00','2026-09-05 07:02:31','2026-09-05 07:00:00'),
  ('acl_01hp','tnt_0016','aag_0179','acp_017e','con_00b3','org_00dk','deal_00h5','ord_00tt','outbound','+998 95 551 12 75','completed','interested',394,0.59,'consent.calls = true (зафиксировано при сборе заявки)',1,1,1,0.91,'Клиент заинтересовался тарифом Business, запросил коммерческое предложение.','[ИИ] Здравствуйте, это ИИ-помощник компании Acme Corp. Звонок записывается для контроля качества.
[Клиент] Да, слушаю.
[ИИ] Удобно обсудить внедрение CRM на 2 минуты?
[Клиент] Давайте, расскажите.
[ИИ] Отлично, задам несколько уточняющих вопросов...',1,'2026-09-02 13:00:00','2026-09-02 13:06:34','2026-09-02 13:00:00'),
  ('acl_01hr','tnt_0016','aag_0175','acp_017d','con_00e1','org_00ei',NULL,NULL,'outbound','+998 93 108 45 67','completed','voicemail',15,0.02,'consent.calls = true (зафиксировано при сборе заявки)',1,0,0,0.49,'Ответил автоответчик, оставлено голосовое сообщение.',NULL,1,'2026-09-10 08:00:00','2026-09-10 08:00:15','2026-09-10 08:00:00'),
  ('acl_01hs','tnt_0016','aag_0178','acp_017d','con_00c8','org_00cb',NULL,NULL,'outbound','+998 90 513 24 42','completed','objection',299,0.45,'consent.calls = true (зафиксировано при сборе заявки)',1,0,0,0.62,'Возражение по цене. Агент привёл сравнение с текущими расходами, клиент попросил паузу.','[ИИ] Здравствуйте, это ИИ-помощник компании Acme Corp. Звонок записывается для контроля качества.
[Клиент] Да, слушаю.
[ИИ] Удобно обсудить внедрение CRM на 2 минуты?
[Клиент] Давайте, расскажите.
[ИИ] Отлично, задам несколько уточняющих вопросов...',1,'2026-10-05 18:00:00','2026-10-05 18:04:59','2026-10-05 18:00:00'),
  ('acl_01hu','tnt_0016','aag_0177','acp_017d','con_00do','org_00ci',NULL,NULL,'outbound','+998 94 624 35 59','completed','qualified',133,0.2,'consent.calls = true (зафиксировано при сборе заявки)',1,1,1,0.8,'Квалифицирован: есть бюджет, потребность в интеграции и ЛПР на линии.','[ИИ] Здравствуйте, это ИИ-помощник компании Acme Corp. Звонок записывается для контроля качества.
[Клиент] Да, слушаю.
[ИИ] Удобно обсудить внедрение CRM на 2 минуты?
[Клиент] Давайте, расскажите.
[ИИ] Отлично, задам несколько уточняющих вопросов...',1,'2026-09-16 22:00:00','2026-09-16 22:02:13','2026-09-16 22:00:00'),
  ('acl_01hw','tnt_0016','aag_0176','acp_017d','con_00bo','org_00ci',NULL,NULL,'outbound','+998 72 708 71 22','completed','qualified',353,0.53,'consent.calls = true (зафиксировано при сборе заявки)',1,0,1,0.84,'Квалифицирован: есть бюджет, потребность в интеграции и ЛПР на линии.','[ИИ] Здравствуйте, это ИИ-помощник компании Acme Corp. Звонок записывается для контроля качества.
[Клиент] Да, слушаю.
[ИИ] Удобно обсудить внедрение CRM на 2 минуты?
[Клиент] Давайте, расскажите.
[ИИ] Отлично, задам несколько уточняющих вопросов...',1,'2026-08-18 22:00:00','2026-08-18 22:05:53','2026-08-18 22:00:00'),
  ('acl_01hy','tnt_0016','aag_0177','acp_017e','con_00ef','org_00bp',NULL,NULL,'outbound','+998 93 418 42 12','completed','qualified',63,0.09,'consent.calls = true (зафиксировано при сборе заявки)',1,0,1,0.86,'Квалифицирован: есть бюджет, потребность в интеграции и ЛПР на линии.','[ИИ] Здравствуйте, это ИИ-помощник компании Acme Corp. Звонок записывается для контроля качества.
[Клиент] Да, слушаю.
[ИИ] Удобно обсудить внедрение CRM на 2 минуты?
[Клиент] Давайте, расскажите.
[ИИ] Отлично, задам несколько уточняющих вопросов...',1,'2026-09-03 14:00:00','2026-09-03 14:01:03','2026-09-03 14:00:00'),
  ('acl_01i0','tnt_0016','aag_0179','acp_017e','con_00e4','org_00dx',NULL,NULL,'outbound','+998 73 989 48 17','completed','not_interested',225,0.34,'consent.calls = true (зафиксировано при сборе заявки)',1,1,1,0.31,'Клиент сообщил об отсутствии потребности на текущий момент.','[ИИ] Здравствуйте, это ИИ-помощник компании Acme Corp. Звонок записывается для контроля качества.
[Клиент] Да, слушаю.
[ИИ] Удобно обсудить внедрение CRM на 2 минуты?
[Клиент] Нам это сейчас не нужно.
[ИИ] Отлично, задам несколько уточняющих вопросов...',1,'2026-09-16 15:00:00','2026-09-16 15:03:45','2026-09-16 15:00:00'),
  ('acl_01i2','tnt_0016','aag_0178','acp_017e','con_00df','org_00cb','deal_00iu',NULL,'outbound','+998 85 314 97 51','completed','qualified',87,0.13,'consent.calls = true (зафиксировано при сборе заявки)',1,0,0,0.72,'Квалифицирован: есть бюджет, потребность в интеграции и ЛПР на линии.','[ИИ] Здравствуйте, это ИИ-помощник компании Acme Corp. Звонок записывается для контроля качества.
[Клиент] Да, слушаю.
[ИИ] Удобно обсудить внедрение CRM на 2 минуты?
[Клиент] Давайте, расскажите.
[ИИ] Отлично, задам несколько уточняющих вопросов...',1,'2026-08-14 21:00:00','2026-08-14 21:01:27','2026-08-14 21:00:00'),
  ('acl_01i4','tnt_0016','aag_0176',NULL,'con_00cw','org_00ed','deal_00i5',NULL,'outbound','+998 95 376 93 36','completed','qualified',216,0.32,'consent.calls = true (зафиксировано при сборе заявки)',1,1,1,0.85,'Квалифицирован: есть бюджет, потребность в интеграции и ЛПР на линии.','[ИИ] Здравствуйте, это ИИ-помощник компании Acme Corp. Звонок записывается для контроля качества.
[Клиент] Да, слушаю.
[ИИ] Удобно обсудить внедрение CRM на 2 минуты?
[Клиент] Давайте, расскажите.
[ИИ] Отлично, задам несколько уточняющих вопросов...',1,'2026-09-27 21:00:00','2026-09-27 21:03:36','2026-09-27 21:00:00'),
  ('acl_01i6','tnt_0016','aag_0175','acp_017f','con_00cf','org_00ax',NULL,NULL,'outbound','+998 95 444 65 88','completed','interested',331,0.5,'consent.calls = true (зафиксировано при сборе заявки)',1,1,1,0.88,'Клиент заинтересовался тарифом Business, запросил коммерческое предложение.','[ИИ] Здравствуйте, это ИИ-помощник компании Acme Corp. Звонок записывается для контроля качества.
[Клиент] Да, слушаю.
[ИИ] Удобно обсудить внедрение CRM на 2 минуты?
[Клиент] Давайте, расскажите.
[ИИ] Отлично, задам несколько уточняющих вопросов...',1,'2026-09-11 14:00:00','2026-09-11 14:05:31','2026-09-11 14:00:00'),
  ('acl_01i9','tnt_0016','aag_0175','acp_017g','con_00b5','org_00cb','deal_00gc',NULL,'outbound','+998 85 839 36 95','completed','no_answer',2,0,'consent.calls = true (зафиксировано при сборе заявки)',0,0,1,0.46,'Нет ответа после 4 гудков, назначен повторный дозвон.',NULL,1,'2026-09-30 08:00:00','2026-09-30 08:00:02','2026-09-30 08:00:00'),
  ('acl_01ia','tnt_0016','aag_0177','acp_017e','con_00d4','org_00cy','deal_00gc',NULL,'outbound','+998 97 660 38 63','completed','objection',63,0.09,'consent.calls = true (зафиксировано при сборе заявки)',1,1,1,0.55,'Возражение по цене. Агент привёл сравнение с текущими расходами, клиент попросил паузу.','[ИИ] Здравствуйте, это ИИ-помощник компании Acme Corp. Звонок записывается для контроля качества.
[Клиент] Да, слушаю.
[ИИ] Удобно обсудить внедрение CRM на 2 минуты?
[Клиент] Давайте, расскажите.
[ИИ] Отлично, задам несколько уточняющих вопросов...',1,'2026-08-24 16:00:00','2026-08-24 16:01:03','2026-08-24 16:00:00'),
  ('acl_01ic','tnt_0016','aag_0176','acp_017g','con_00ej','org_00c3','deal_00hy',NULL,'outbound','+998 79 174 36 32','completed','no_answer',22,0.03,'consent.calls = true (зафиксировано при сборе заявки)',1,0,0,0.7,'Нет ответа после 4 гудков, назначен повторный дозвон.',NULL,1,'2026-09-23 13:00:00','2026-09-23 13:00:22','2026-09-23 13:00:00'),
  ('acl_01id','tnt_0016','aag_0177','acp_017d','con_00cs','org_00cu',NULL,NULL,'outbound','+998 98 967 91 11','completed','no_answer',12,0.02,'consent.calls = true (зафиксировано при сборе заявки)',1,0,1,0.65,'Нет ответа после 4 гудков, назначен повторный дозвон.',NULL,1,'2026-10-12 07:00:00','2026-10-12 07:00:12','2026-10-12 07:00:00'),
  ('acl_01ie','tnt_0016','aag_0178','acp_017d','con_00cc','org_00bu','deal_00jz',NULL,'outbound','+998 92 732 84 83','completed','interested',195,0.29,'consent.calls = true (зафиксировано при сборе заявки)',1,0,1,0.88,'Клиент заинтересовался тарифом Business, запросил коммерческое предложение.','[ИИ] Здравствуйте, это ИИ-помощник компании Acme Corp. Звонок записывается для контроля качества.
[Клиент] Да, слушаю.
[ИИ] Удобно обсудить внедрение CRM на 2 минуты?
[Клиент] Давайте, расскажите.
[ИИ] Отлично, задам несколько уточняющих вопросов...',1,'2026-09-19 21:00:00','2026-09-19 21:03:15','2026-09-19 21:00:00'),
  ('acl_01ih','tnt_0016','aag_0175',NULL,'con_00b4','org_00dk',NULL,NULL,'outbound','+998 93 860 80 90','completed','opted_out',51,0.08,'consent.calls = true (зафиксировано при сборе заявки)',1,1,1,0.48,'Клиент запросил исключение из обзвона — добавлен в suppression list.','[ИИ] Здравствуйте, это ИИ-помощник компании Acme Corp. Звонок записывается для контроля качества.
[Клиент] Да, слушаю.
[ИИ] Удобно обсудить внедрение CRM на 2 минуты?
[Клиент] Давайте, расскажите.
[ИИ] Отлично, задам несколько уточняющих вопросов...',1,'2026-08-24 22:00:00','2026-08-24 22:00:51','2026-08-24 22:00:00'),
  ('acl_01ij','tnt_0016','aag_0177','acp_017e','con_00ch','org_00dk',NULL,NULL,'outbound','+998 86 652 17 88','completed','interested',159,0.24,'consent.calls = true (зафиксировано при сборе заявки)',1,1,1,0.88,'Клиент заинтересовался тарифом Business, запросил коммерческое предложение.','[ИИ] Здравствуйте, это ИИ-помощник компании Acme Corp. Звонок записывается для контроля качества.
[Клиент] Да, слушаю.
[ИИ] Удобно обсудить внедрение CRM на 2 минуты?
[Клиент] Давайте, расскажите.
[ИИ] Отлично, задам несколько уточняющих вопросов...',1,'2026-08-19 17:00:00','2026-08-19 17:02:39','2026-08-19 17:00:00'),
  ('acl_01il','tnt_0016','aag_0179','acp_017e','con_00el','org_00ci',NULL,'ord_00uo','outbound','+998 93 383 98 19','completed','interested',106,0.16,'consent.calls = true (зафиксировано при сборе заявки)',1,0,0,0.9,'Клиент заинтересовался тарифом Business, запросил коммерческое предложение.','[ИИ] Здравствуйте, это ИИ-помощник компании Acme Corp. Звонок записывается для контроля качества.
[Клиент] Да, слушаю.
[ИИ] Удобно обсудить внедрение CRM на 2 минуты?
[Клиент] Давайте, расскажите.
[ИИ] Отлично, задам несколько уточняющих вопросов...',1,'2026-09-28 18:00:00','2026-09-28 18:01:46','2026-09-28 18:00:00'),
  ('acl_01in','tnt_0016','aag_0177','acp_017f','con_00cz','org_00bh',NULL,NULL,'outbound','+998 87 937 61 69','completed','human_handoff',258,0.39,'consent.calls = true (зафиксировано при сборе заявки)',1,0,1,0.56,'Запросил связь с человеком, переведён на менеджера.','[ИИ] Здравствуйте, это ИИ-помощник компании Acme Corp. Звонок записывается для контроля качества.
[Клиент] Да, слушаю.
[ИИ] Удобно обсудить внедрение CRM на 2 минуты?
[Клиент] Давайте, расскажите.
[ИИ] Отлично, задам несколько уточняющих вопросов...',1,'2026-10-07 05:00:00','2026-10-07 05:04:18','2026-10-07 05:00:00'),
  ('acl_01ip','tnt_0016','aag_0175','acp_017f','con_00aw','org_00dx',NULL,NULL,'outbound','+998 74 476 84 57','completed','no_answer',19,0.03,'consent.calls = true (зафиксировано при сборе заявки)',1,0,1,0.45,'Нет ответа после 4 гудков, назначен повторный дозвон.',NULL,1,'2026-08-31 23:00:00','2026-08-31 23:00:19','2026-08-31 23:00:00'),
  ('acl_01iq','tnt_0016','aag_0178','acp_017d','con_00d8','org_00bx',NULL,NULL,'outbound','+998 75 757 30 18','completed','human_handoff',121,0.18,'consent.calls = true (зафиксировано при сборе заявки)',1,1,1,0.43,'Запросил связь с человеком, переведён на менеджера.','[ИИ] Здравствуйте, это ИИ-помощник компании Acme Corp. Звонок записывается для контроля качества.
[Клиент] Да, слушаю.
[ИИ] Удобно обсудить внедрение CRM на 2 минуты?
[Клиент] Давайте, расскажите.
[ИИ] Отлично, задам несколько уточняющих вопросов...',1,'2026-08-31 09:00:00','2026-08-31 09:02:01','2026-08-31 09:00:00'),
  ('acl_01is','tnt_0016','aag_0179','acp_017e','con_00aw','org_00bp',NULL,NULL,'outbound','+998 72 780 60 80','completed','interested',229,0.34,'consent.calls = true (зафиксировано при сборе заявки)',1,1,1,0.95,'Клиент заинтересовался тарифом Business, запросил коммерческое предложение.','[ИИ] Здравствуйте, это ИИ-помощник компании Acme Corp. Звонок записывается для контроля качества.
[Клиент] Да, слушаю.
[ИИ] Удобно обсудить внедрение CRM на 2 минуты?
[Клиент] Давайте, расскажите.
[ИИ] Отлично, задам несколько уточняющих вопросов...',1,'2026-09-23 05:00:00','2026-09-23 05:03:49','2026-09-23 05:00:00'),
  ('acl_01iu','tnt_0016','aag_0175','acp_017g','con_00e7','org_00d3',NULL,NULL,'outbound','+998 94 190 90 64','completed','qualified',309,0.46,'consent.calls = true (зафиксировано при сборе заявки)',1,0,0,0.87,'Квалифицирован: есть бюджет, потребность в интеграции и ЛПР на линии.','[ИИ] Здравствуйте, это ИИ-помощник компании Acme Corp. Звонок записывается для контроля качества.
[Клиент] Да, слушаю.
[ИИ] Удобно обсудить внедрение CRM на 2 минуты?
[Клиент] Давайте, расскажите.
[ИИ] Отлично, задам несколько уточняющих вопросов...',1,'2026-09-06 04:00:00','2026-09-06 04:05:09','2026-09-06 04:00:00'),
  ('acl_01iw','tnt_0016','aag_0179',NULL,'con_00dg','org_00bm',NULL,NULL,'outbound','+998 74 321 21 72','completed','opted_out',151,0.23,'consent.calls = true (зафиксировано при сборе заявки)',1,0,0,0.36,'Клиент запросил исключение из обзвона — добавлен в suppression list.','[ИИ] Здравствуйте, это ИИ-помощник компании Acme Corp. Звонок записывается для контроля качества.
[Клиент] Да, слушаю.
[ИИ] Удобно обсудить внедрение CRM на 2 минуты?
[Клиент] Давайте, расскажите.
[ИИ] Отлично, задам несколько уточняющих вопросов...',1,'2026-09-14 08:00:00','2026-09-14 08:02:31','2026-09-14 08:00:00'),
  ('acl_01iy','tnt_0016','aag_0178','acp_017f','con_00bd','org_00du',NULL,NULL,'outbound','+998 84 151 40 93','completed','no_answer',17,0.03,'consent.calls = true (зафиксировано при сборе заявки)',1,0,0,0.48,'Нет ответа после 4 гудков, назначен повторный дозвон.',NULL,1,'2026-10-03 19:00:00','2026-10-03 19:00:17','2026-10-03 19:00:00'),
  ('acl_01iz','tnt_0016','aag_0179','acp_017f','con_00de','org_00dc',NULL,NULL,'outbound','+998 83 427 89 49','completed','objection',68,0.1,'consent.calls = true (зафиксировано при сборе заявки)',1,1,1,0.46,'Возражение по цене. Агент привёл сравнение с текущими расходами, клиент попросил паузу.','[ИИ] Здравствуйте, это ИИ-помощник компании Acme Corp. Звонок записывается для контроля качества.
[Клиент] Да, слушаю.
[ИИ] Удобно обсудить внедрение CRM на 2 минуты?
[Клиент] Давайте, расскажите.
[ИИ] Отлично, задам несколько уточняющих вопросов...',1,'2026-09-29 08:00:00','2026-09-29 08:01:08','2026-09-29 08:00:00'),
  ('acl_01j1','tnt_0016','aag_0176','acp_017d','con_00dt','org_00ci','deal_00iq',NULL,'outbound','+998 96 600 15 33','completed','opted_out',260,0.39,'consent.calls = true (зафиксировано при сборе заявки)',1,1,1,0.53,'Клиент запросил исключение из обзвона — добавлен в suppression list.','[ИИ] Здравствуйте, это ИИ-помощник компании Acme Corp. Звонок записывается для контроля качества.
[Клиент] Да, слушаю.
[ИИ] Удобно обсудить внедрение CRM на 2 минуты?
[Клиент] Давайте, расскажите.
[ИИ] Отлично, задам несколько уточняющих вопросов...',1,'2026-09-04 04:00:00','2026-09-04 04:04:20','2026-09-04 04:00:00'),
  ('acl_01j3','tnt_0016','aag_0179','acp_017f','con_00e3','org_00d7','deal_00jr',NULL,'outbound','+998 80 675 78 69','completed','objection',284,0.43,'consent.calls = true (зафиксировано при сборе заявки)',1,1,1,0.41,'Возражение по цене. Агент привёл сравнение с текущими расходами, клиент попросил паузу.','[ИИ] Здравствуйте, это ИИ-помощник компании Acme Corp. Звонок записывается для контроля качества.
[Клиент] Да, слушаю.
[ИИ] Удобно обсудить внедрение CRM на 2 минуты?
[Клиент] Давайте, расскажите.
[ИИ] Отлично, задам несколько уточняющих вопросов...',1,'2026-08-25 16:00:00','2026-08-25 16:04:44','2026-08-25 16:00:00'),
  ('acl_01j5','tnt_0016','aag_0179','acp_017e','con_00c7','org_00b6','deal_00kc',NULL,'outbound','+998 77 615 53 58','completed','opted_out',357,0.54,'consent.calls = true (зафиксировано при сборе заявки)',1,0,0,0.61,'Клиент запросил исключение из обзвона — добавлен в suppression list.','[ИИ] Здравствуйте, это ИИ-помощник компании Acme Corp. Звонок записывается для контроля качества.
[Клиент] Да, слушаю.
[ИИ] Удобно обсудить внедрение CRM на 2 минуты?
[Клиент] Давайте, расскажите.
[ИИ] Отлично, задам несколько уточняющих вопросов...',1,'2026-09-11 20:00:00','2026-09-11 20:05:57','2026-09-11 20:00:00'),
  ('acl_01j7','tnt_0016','aag_0175','acp_017f','con_00c1','org_00du',NULL,NULL,'outbound','+998 82 364 51 57','completed','qualified',298,0.45,'consent.calls = true (зафиксировано при сборе заявки)',1,0,0,0.82,'Квалифицирован: есть бюджет, потребность в интеграции и ЛПР на линии.','[ИИ] Здравствуйте, это ИИ-помощник компании Acme Corp. Звонок записывается для контроля качества.
[Клиент] Да, слушаю.
[ИИ] Удобно обсудить внедрение CRM на 2 минуты?
[Клиент] Давайте, расскажите.
[ИИ] Отлично, задам несколько уточняющих вопросов...',1,'2026-08-27 07:00:00','2026-08-27 07:04:58','2026-08-27 07:00:00'),
  ('acl_01j9','tnt_0016','aag_0175','acp_017e','con_00dy','org_00cq',NULL,'ord_00v6','outbound','+998 71 540 29 84','failed','interested',222,0.33,'consent.calls = true (зафиксировано при сборе заявки)',1,1,1,0.88,'Клиент заинтересовался тарифом Business, запросил коммерческое предложение.','[ИИ] Здравствуйте, это ИИ-помощник компании Acme Corp. Звонок записывается для контроля качества.
[Клиент] Да, слушаю.
[ИИ] Удобно обсудить внедрение CRM на 2 минуты?
[Клиент] Давайте, расскажите.
[ИИ] Отлично, задам несколько уточняющих вопросов...',1,'2026-09-25 05:00:00','2026-09-25 05:03:42','2026-09-25 05:00:00'),
  ('acl_01jb','tnt_0016','aag_0176','acp_017d','con_00ec','org_00ce',NULL,NULL,'outbound','+998 98 149 55 68','completed','objection',181,0.27,'consent.calls = true (зафиксировано при сборе заявки)',1,1,1,0.57,'Возражение по цене. Агент привёл сравнение с текущими расходами, клиент попросил паузу.','[ИИ] Здравствуйте, это ИИ-помощник компании Acme Corp. Звонок записывается для контроля качества.
[Клиент] Да, слушаю.
[ИИ] Удобно обсудить внедрение CRM на 2 минуты?
[Клиент] Давайте, расскажите.
[ИИ] Отлично, задам несколько уточняющих вопросов...',1,'2026-09-05 21:00:00','2026-09-05 21:03:01','2026-09-05 21:00:00'),
  ('acl_01jd','tnt_0016','aag_0175','acp_017e','con_00ds','org_00ei',NULL,NULL,'outbound','+998 86 943 64 89','completed','voicemail',6,0.01,'consent.calls = true (зафиксировано при сборе заявки)',0,0,0,0.59,'Ответил автоответчик, оставлено голосовое сообщение.',NULL,1,'2026-10-01 17:00:00','2026-10-01 17:00:06','2026-10-01 17:00:00'),
  ('acl_01je','tnt_0016','aag_0176',NULL,'con_00e4','org_00bh',NULL,NULL,'outbound','+998 97 539 25 52','completed','voicemail',17,0.03,'consent.calls = true (зафиксировано при сборе заявки)',1,0,0,0.34,'Ответил автоответчик, оставлено голосовое сообщение.',NULL,1,'2026-09-24 08:00:00','2026-09-24 08:00:17','2026-09-24 08:00:00'),
  ('acl_01jf','tnt_0016','aag_0176','acp_017d','con_00di','org_00cb',NULL,NULL,'outbound','+998 73 492 73 62','completed','no_answer',10,0.02,'consent.calls = true (зафиксировано при сборе заявки)',0,0,0,0.5,'Нет ответа после 4 гудков, назначен повторный дозвон.',NULL,1,'2026-09-15 08:00:00','2026-09-15 08:00:10','2026-09-15 08:00:00'),
  ('acl_01jg','tnt_0016','aag_0178','acp_017g','con_00dw','org_00b9',NULL,NULL,'outbound','+998 76 312 56 29','completed','interested',47,0.07,'consent.calls = true (зафиксировано при сборе заявки)',1,0,0,0.96,'Клиент заинтересовался тарифом Business, запросил коммерческое предложение.','[ИИ] Здравствуйте, это ИИ-помощник компании Acme Corp. Звонок записывается для контроля качества.
[Клиент] Да, слушаю.
[ИИ] Удобно обсудить внедрение CRM на 2 минуты?
[Клиент] Давайте, расскажите.
[ИИ] Отлично, задам несколько уточняющих вопросов...',1,'2026-08-17 20:00:00','2026-08-17 20:00:47','2026-08-17 20:00:00'),
  ('acl_01ji','tnt_0016','aag_0177','acp_017g','con_00dn','org_00d7',NULL,NULL,'outbound','+998 80 206 35 56','completed','opted_out',243,0.36,'consent.calls = true (зафиксировано при сборе заявки)',1,1,1,0.43,'Клиент запросил исключение из обзвона — добавлен в suppression list.','[ИИ] Здравствуйте, это ИИ-помощник компании Acme Corp. Звонок записывается для контроля качества.
[Клиент] Да, слушаю.
[ИИ] Удобно обсудить внедрение CRM на 2 минуты?
[Клиент] Давайте, расскажите.
[ИИ] Отлично, задам несколько уточняющих вопросов...',1,'2026-10-11 22:00:00','2026-10-11 22:04:03','2026-10-11 22:00:00'),
  ('acl_01jk','tnt_0016','aag_0176','acp_017g','con_00c2','org_00cy','deal_00h5',NULL,'outbound','+998 81 639 93 30','completed','voicemail',2,0,'consent.calls = true (зафиксировано при сборе заявки)',0,0,0,0.55,'Ответил автоответчик, оставлено голосовое сообщение.',NULL,1,'2026-08-28 23:00:00','2026-08-28 23:00:02','2026-08-28 23:00:00'),
  ('acl_01nd','tnt_0017','aag_017a','acp_017i','con_00fg','org_00ey',NULL,NULL,'outbound','+998 84 261 36 47','completed','qualified',406,0.61,'consent.calls = true (зафиксировано при сборе заявки)',1,1,1,0.78,'Квалифицирован: есть бюджет, потребность в интеграции и ЛПР на линии.','[ИИ] Здравствуйте, это ИИ-помощник компании Nexio Lab. Звонок записывается для контроля качества.
[Клиент] Да, слушаю.
[ИИ] Удобно обсудить внедрение CRM на 2 минуты?
[Клиент] Давайте, расскажите.
[ИИ] Отлично, задам несколько уточняющих вопросов...',1,'2026-09-27 05:00:00','2026-09-27 05:06:46','2026-09-27 05:00:00'),
  ('acl_01nf','tnt_0017','aag_017c','acp_017i','con_00f1','org_00ff',NULL,NULL,'outbound','+998 99 127 21 56','completed','interested',280,0.42,'consent.calls = true (зафиксировано при сборе заявки)',1,1,1,0.85,'Клиент заинтересовался тарифом Business, запросил коммерческое предложение.','[ИИ] Здравствуйте, это ИИ-помощник компании Nexio Lab. Звонок записывается для контроля качества.
[Клиент] Да, слушаю.
[ИИ] Удобно обсудить внедрение CRM на 2 минуты?
[Клиент] Давайте, расскажите.
[ИИ] Отлично, задам несколько уточняющих вопросов...',1,'2026-09-29 18:00:00','2026-09-29 18:04:40','2026-09-29 18:00:00'),
  ('acl_01nh','tnt_0017','aag_017a','acp_017h','con_00f3','org_00fc',NULL,NULL,'outbound','+998 97 265 41 55','completed','not_interested',379,0.57,'consent.calls = true (зафиксировано при сборе заявки)',1,0,0,0.64,'Клиент сообщил об отсутствии потребности на текущий момент.','[ИИ] Здравствуйте, это ИИ-помощник компании Nexio Lab. Звонок записывается для контроля качества.
[Клиент] Да, слушаю.
[ИИ] Удобно обсудить внедрение CRM на 2 минуты?
[Клиент] Нам это сейчас не нужно.
[ИИ] Отлично, задам несколько уточняющих вопросов...',1,'2026-09-23 23:00:00','2026-09-23 23:06:19','2026-09-23 23:00:00'),
  ('acl_01nj','tnt_0017','aag_017a','acp_017h','con_00ew','org_00eq',NULL,NULL,'outbound','+998 95 461 51 30','completed','qualified',315,0.47,'consent.calls = true (зафиксировано при сборе заявки)',1,1,1,0.9,'Квалифицирован: есть бюджет, потребность в интеграции и ЛПР на линии.','[ИИ] Здравствуйте, это ИИ-помощник компании Nexio Lab. Звонок записывается для контроля качества.
[Клиент] Да, слушаю.
[ИИ] Удобно обсудить внедрение CRM на 2 минуты?
[Клиент] Давайте, расскажите.
[ИИ] Отлично, задам несколько уточняющих вопросов...',1,'2026-09-30 09:00:00','2026-09-30 09:05:15','2026-09-30 09:00:00'),
  ('acl_01nl','tnt_0017','aag_017b','acp_017h','con_00f6','org_00et',NULL,'ord_00vs','outbound','+998 96 881 82 48','completed','interested',218,0.33,'consent.calls = true (зафиксировано при сборе заявки)',1,0,0,0.87,'Клиент заинтересовался тарифом Business, запросил коммерческое предложение.','[ИИ] Здравствуйте, это ИИ-помощник компании Nexio Lab. Звонок записывается для контроля качества.
[Клиент] Да, слушаю.
[ИИ] Удобно обсудить внедрение CRM на 2 минуты?
[Клиент] Давайте, расскажите.
[ИИ] Отлично, задам несколько уточняющих вопросов...',1,'2026-10-02 23:00:00','2026-10-02 23:03:38','2026-10-02 23:00:00'),
  ('acl_01nn','tnt_0017','aag_017b','acp_017h','con_00f3','org_00f0',NULL,NULL,'outbound','+998 97 206 80 57','completed','interested',221,0.33,'consent.calls = true (зафиксировано при сборе заявки)',1,1,1,0.94,'Клиент заинтересовался тарифом Business, запросил коммерческое предложение.','[ИИ] Здравствуйте, это ИИ-помощник компании Nexio Lab. Звонок записывается для контроля качества.
[Клиент] Да, слушаю.
[ИИ] Удобно обсудить внедрение CRM на 2 минуты?
[Клиент] Давайте, расскажите.
[ИИ] Отлично, задам несколько уточняющих вопросов...',1,'2026-09-08 03:00:00','2026-09-08 03:03:41','2026-09-08 03:00:00'),
  ('acl_01np','tnt_0017','aag_017b','acp_017i','con_00er','org_00ff',NULL,NULL,'outbound','+998 94 885 43 60','completed','voicemail',9,0.01,'consent.calls = true (зафиксировано при сборе заявки)',0,0,0,0.37,'Ответил автоответчик, оставлено голосовое сообщение.',NULL,1,'2026-09-07 21:00:00','2026-09-07 21:00:09','2026-09-07 21:00:00'),
  ('acl_01nq','tnt_0017','aag_017c','acp_017i','con_00ep','org_00eq','deal_00nh',NULL,'outbound','+998 96 899 75 23','completed','qualified',352,0.53,'consent.calls = true (зафиксировано при сборе заявки)',1,0,0,0.78,'Квалифицирован: есть бюджет, потребность в интеграции и ЛПР на линии.','[ИИ] Здравствуйте, это ИИ-помощник компании Nexio Lab. Звонок записывается для контроля качества.
[Клиент] Да, слушаю.
[ИИ] Удобно обсудить внедрение CRM на 2 минуты?
[Клиент] Давайте, расскажите.
[ИИ] Отлично, задам несколько уточняющих вопросов...',1,'2026-10-07 16:00:00','2026-10-07 16:05:52','2026-10-07 16:00:00'),
  ('acl_01ns','tnt_0017','aag_017a','acp_017h','con_00fd','org_00ff','deal_00nj','ord_00w1','outbound','+998 83 721 13 72','completed','interested',201,0.3,'consent.calls = true (зафиксировано при сборе заявки)',1,1,1,0.9,'Клиент заинтересовался тарифом Business, запросил коммерческое предложение.','[ИИ] Здравствуйте, это ИИ-помощник компании Nexio Lab. Звонок записывается для контроля качества.
[Клиент] Да, слушаю.
[ИИ] Удобно обсудить внедрение CRM на 2 минуты?
[Клиент] Давайте, расскажите.
[ИИ] Отлично, задам несколько уточняющих вопросов...',1,'2026-10-01 04:00:00','2026-10-01 04:03:21','2026-10-01 04:00:00'),
  ('acl_01nu','tnt_0017','aag_017c','acp_017h','con_00fe','org_00ff','deal_00og',NULL,'outbound','+998 91 868 57 81','completed','not_interested',204,0.31,'consent.calls = true (зафиксировано при сборе заявки)',1,1,1,0.49,'Клиент сообщил об отсутствии потребности на текущий момент.','[ИИ] Здравствуйте, это ИИ-помощник компании Nexio Lab. Звонок записывается для контроля качества.
[Клиент] Да, слушаю.
[ИИ] Удобно обсудить внедрение CRM на 2 минуты?
[Клиент] Нам это сейчас не нужно.
[ИИ] Отлично, задам несколько уточняющих вопросов...',1,'2026-09-05 15:00:00','2026-09-05 15:03:24','2026-09-05 15:00:00');
INSERT INTO ai_calls VALUES
  ('acl_01nw','tnt_0017','aag_017a',NULL,'con_00f4','org_00f9',NULL,NULL,'outbound','+998 81 621 11 69','completed','not_interested',170,0.26,'consent.calls = true (зафиксировано при сборе заявки)',1,1,1,0.58,'Клиент сообщил об отсутствии потребности на текущий момент.','[ИИ] Здравствуйте, это ИИ-помощник компании Nexio Lab. Звонок записывается для контроля качества.
[Клиент] Да, слушаю.
[ИИ] Удобно обсудить внедрение CRM на 2 минуты?
[Клиент] Нам это сейчас не нужно.
[ИИ] Отлично, задам несколько уточняющих вопросов...',1,'2026-08-28 02:00:00','2026-08-28 02:02:50','2026-08-28 02:00:00'),
  ('acl_01ny','tnt_0017','aag_017c','acp_017h','con_00fg','org_00ey','deal_00mx',NULL,'outbound','+998 82 369 60 70','completed','qualified',91,0.14,'consent.calls = true (зафиксировано при сборе заявки)',1,0,0,0.85,'Квалифицирован: есть бюджет, потребность в интеграции и ЛПР на линии.','[ИИ] Здравствуйте, это ИИ-помощник компании Nexio Lab. Звонок записывается для контроля качества.
[Клиент] Да, слушаю.
[ИИ] Удобно обсудить внедрение CRM на 2 минуты?
[Клиент] Давайте, расскажите.
[ИИ] Отлично, задам несколько уточняющих вопросов...',1,'2026-10-08 22:00:00','2026-10-08 22:01:31','2026-10-08 22:00:00'),
  ('acl_01o0','tnt_0017','aag_017c','acp_017h','con_00fd','org_00f9',NULL,NULL,'outbound','+998 83 693 56 64','failed','voicemail',15,0.02,'consent.calls = true (зафиксировано при сборе заявки)',1,0,1,0.31,'Ответил автоответчик, оставлено голосовое сообщение.',NULL,1,'2026-08-27 18:00:00','2026-08-27 18:00:15','2026-08-27 18:00:00'),
  ('acl_01o1','tnt_0017','aag_017a','acp_017h','con_00eo','org_00fc',NULL,NULL,'outbound','+998 74 545 89 30','completed','not_interested',380,0.57,'consent.calls = true (зафиксировано при сборе заявки)',1,0,0,0.63,'Клиент сообщил об отсутствии потребности на текущий момент.','[ИИ] Здравствуйте, это ИИ-помощник компании Nexio Lab. Звонок записывается для контроля качества.
[Клиент] Да, слушаю.
[ИИ] Удобно обсудить внедрение CRM на 2 минуты?
[Клиент] Нам это сейчас не нужно.
[ИИ] Отлично, задам несколько уточняющих вопросов...',1,'2026-09-22 03:00:00','2026-09-22 03:06:20','2026-09-22 03:00:00'),
  ('acl_01o3','tnt_0017','aag_017c',NULL,'con_00ew','org_00f7',NULL,NULL,'outbound','+998 72 637 72 86','completed','opted_out',177,0.27,'consent.calls = true (зафиксировано при сборе заявки)',1,0,0,0.53,'Клиент запросил исключение из обзвона — добавлен в suppression list.','[ИИ] Здравствуйте, это ИИ-помощник компании Nexio Lab. Звонок записывается для контроля качества.
[Клиент] Да, слушаю.
[ИИ] Удобно обсудить внедрение CRM на 2 минуты?
[Клиент] Давайте, расскажите.
[ИИ] Отлично, задам несколько уточняющих вопросов...',1,'2026-09-08 02:00:00','2026-09-08 02:02:57','2026-09-08 02:00:00'),
  ('acl_01o5','tnt_0017','aag_017c','acp_017i','con_00eu','org_00f2',NULL,NULL,'outbound','+998 94 849 15 11','completed','interested',132,0.2,'consent.calls = true (зафиксировано при сборе заявки)',1,0,0,0.89,'Клиент заинтересовался тарифом Business, запросил коммерческое предложение.','[ИИ] Здравствуйте, это ИИ-помощник компании Nexio Lab. Звонок записывается для контроля качества.
[Клиент] Да, слушаю.
[ИИ] Удобно обсудить внедрение CRM на 2 минуты?
[Клиент] Давайте, расскажите.
[ИИ] Отлично, задам несколько уточняющих вопросов...',1,'2026-09-04 00:00:00','2026-09-04 00:02:12','2026-09-04 00:00:00'),
  ('acl_01o7','tnt_0017','aag_017a','acp_017i','con_00ez','org_00f9','deal_00nx','ord_00w4','outbound','+998 83 145 82 25','completed','interested',406,0.61,'consent.calls = true (зафиксировано при сборе заявки)',1,0,0,0.85,'Клиент заинтересовался тарифом Business, запросил коммерческое предложение.','[ИИ] Здравствуйте, это ИИ-помощник компании Nexio Lab. Звонок записывается для контроля качества.
[Клиент] Да, слушаю.
[ИИ] Удобно обсудить внедрение CRM на 2 минуты?
[Клиент] Давайте, расскажите.
[ИИ] Отлично, задам несколько уточняющих вопросов...',1,'2026-09-15 09:00:00','2026-09-15 09:06:46','2026-09-15 09:00:00'),
  ('acl_01o9','tnt_0017','aag_017b','acp_017h','con_00f8','org_00ev',NULL,NULL,'outbound','+998 83 650 16 84','completed','interested',68,0.1,'consent.calls = true (зафиксировано при сборе заявки)',1,1,1,0.89,'Клиент заинтересовался тарифом Business, запросил коммерческое предложение.','[ИИ] Здравствуйте, это ИИ-помощник компании Nexio Lab. Звонок записывается для контроля качества.
[Клиент] Да, слушаю.
[ИИ] Удобно обсудить внедрение CRM на 2 минуты?
[Клиент] Давайте, расскажите.
[ИИ] Отлично, задам несколько уточняющих вопросов...',1,'2026-08-21 00:00:00','2026-08-21 00:01:08','2026-08-21 00:00:00'),
  ('acl_01ob','tnt_0017','aag_017c',NULL,'con_00f8','org_00ey',NULL,NULL,'outbound','+998 77 490 33 36','completed','interested',123,0.18,'consent.calls = true (зафиксировано при сборе заявки)',1,0,0,0.97,'Клиент заинтересовался тарифом Business, запросил коммерческое предложение.','[ИИ] Здравствуйте, это ИИ-помощник компании Nexio Lab. Звонок записывается для контроля качества.
[Клиент] Да, слушаю.
[ИИ] Удобно обсудить внедрение CRM на 2 минуты?
[Клиент] Давайте, расскажите.
[ИИ] Отлично, задам несколько уточняющих вопросов...',1,'2026-09-22 18:00:00','2026-09-22 18:02:03','2026-09-22 18:00:00'),
  ('acl_01od','tnt_0017','aag_017a','acp_017h','con_00fa','org_00ff',NULL,NULL,'outbound','+998 78 472 23 23','completed','voicemail',11,0.02,'consent.calls = true (зафиксировано при сборе заявки)',1,0,1,0.63,'Ответил автоответчик, оставлено голосовое сообщение.',NULL,1,'2026-09-13 20:00:00','2026-09-13 20:00:11','2026-09-13 20:00:00'),
  ('acl_01oe','tnt_0017','aag_017a','acp_017h','con_00ew','org_00f7',NULL,NULL,'outbound','+998 76 665 55 57','completed','human_handoff',131,0.2,'consent.calls = true (зафиксировано при сборе заявки)',1,1,1,0.68,'Запросил связь с человеком, переведён на менеджера.','[ИИ] Здравствуйте, это ИИ-помощник компании Nexio Lab. Звонок записывается для контроля качества.
[Клиент] Да, слушаю.
[ИИ] Удобно обсудить внедрение CRM на 2 минуты?
[Клиент] Давайте, расскажите.
[ИИ] Отлично, задам несколько уточняющих вопросов...',1,'2026-09-26 14:00:00','2026-09-26 14:02:11','2026-09-26 14:00:00'),
  ('acl_01og','tnt_0017','aag_017a','acp_017h','con_00er','org_00ff','deal_00nl',NULL,'outbound','+998 71 397 88 37','completed','objection',68,0.1,'consent.calls = true (зафиксировано при сборе заявки)',1,0,0,0.4,'Возражение по цене. Агент привёл сравнение с текущими расходами, клиент попросил паузу.','[ИИ] Здравствуйте, это ИИ-помощник компании Nexio Lab. Звонок записывается для контроля качества.
[Клиент] Да, слушаю.
[ИИ] Удобно обсудить внедрение CRM на 2 минуты?
[Клиент] Давайте, расскажите.
[ИИ] Отлично, задам несколько уточняющих вопросов...',1,'2026-08-27 02:00:00','2026-08-27 02:01:08','2026-08-27 02:00:00'),
  ('acl_01oi','tnt_0017','aag_017b','acp_017i','con_00es','org_00ev',NULL,NULL,'outbound','+998 99 811 94 37','completed','opted_out',132,0.2,'consent.calls = true (зафиксировано при сборе заявки)',1,1,1,0.31,'Клиент запросил исключение из обзвона — добавлен в suppression list.','[ИИ] Здравствуйте, это ИИ-помощник компании Nexio Lab. Звонок записывается для контроля качества.
[Клиент] Да, слушаю.
[ИИ] Удобно обсудить внедрение CRM на 2 минуты?
[Клиент] Давайте, расскажите.
[ИИ] Отлично, задам несколько уточняющих вопросов...',1,'2026-09-03 08:00:00','2026-09-03 08:02:12','2026-09-03 08:00:00'),
  ('acl_01ok','tnt_0017','aag_017b','acp_017h','con_00ez','org_00f9',NULL,NULL,'outbound','+998 93 531 14 91','completed','interested',87,0.13,'consent.calls = true (зафиксировано при сборе заявки)',1,1,1,0.93,'Клиент заинтересовался тарифом Business, запросил коммерческое предложение.','[ИИ] Здравствуйте, это ИИ-помощник компании Nexio Lab. Звонок записывается для контроля качества.
[Клиент] Да, слушаю.
[ИИ] Удобно обсудить внедрение CRM на 2 минуты?
[Клиент] Давайте, расскажите.
[ИИ] Отлично, задам несколько уточняющих вопросов...',1,'2026-08-24 19:00:00','2026-08-24 19:01:27','2026-08-24 19:00:00'),
  ('acl_01om','tnt_0017','aag_017c','acp_017h','con_00f1','org_00f9',NULL,NULL,'outbound','+998 93 610 53 18','completed','voicemail',13,0.02,'consent.calls = true (зафиксировано при сборе заявки)',1,0,1,0.32,'Ответил автоответчик, оставлено голосовое сообщение.',NULL,1,'2026-10-09 19:00:00','2026-10-09 19:00:13','2026-10-09 19:00:00'),
  ('acl_01on','tnt_0017','aag_017c','acp_017i','con_00f3','org_00et','deal_00nl',NULL,'outbound','+998 93 523 50 56','completed','voicemail',0,0,'consent.calls = true (зафиксировано при сборе заявки)',0,0,1,0.62,'Ответил автоответчик, оставлено голосовое сообщение.',NULL,1,'2026-10-06 21:00:00','2026-10-06 21:00:00','2026-10-06 21:00:00'),
  ('acl_01oo','tnt_0017','aag_017c','acp_017h','con_00fe','org_00f0',NULL,NULL,'outbound','+998 80 526 24 21','completed','interested',104,0.16,'consent.calls = true (зафиксировано при сборе заявки)',1,0,1,0.88,'Клиент заинтересовался тарифом Business, запросил коммерческое предложение.','[ИИ] Здравствуйте, это ИИ-помощник компании Nexio Lab. Звонок записывается для контроля качества.
[Клиент] Да, слушаю.
[ИИ] Удобно обсудить внедрение CRM на 2 минуты?
[Клиент] Давайте, расскажите.
[ИИ] Отлично, задам несколько уточняющих вопросов...',1,'2026-09-18 13:00:00','2026-09-18 13:01:44','2026-09-18 13:00:00'),
  ('acl_01or','tnt_0017','aag_017b','acp_017i','con_00fg','org_00en','deal_00nv',NULL,'outbound','+998 89 964 82 81','completed','interested',67,0.1,'consent.calls = true (зафиксировано при сборе заявки)',1,1,1,0.86,'Клиент заинтересовался тарифом Business, запросил коммерческое предложение.','[ИИ] Здравствуйте, это ИИ-помощник компании Nexio Lab. Звонок записывается для контроля качества.
[Клиент] Да, слушаю.
[ИИ] Удобно обсудить внедрение CRM на 2 минуты?
[Клиент] Давайте, расскажите.
[ИИ] Отлично, задам несколько уточняющих вопросов...',1,'2026-08-23 23:00:00','2026-08-23 23:01:07','2026-08-23 23:00:00'),
  ('acl_01ot','tnt_0017','aag_017a','acp_017h','con_00fb','org_00f5',NULL,NULL,'outbound','+998 78 283 77 71','completed','voicemail',10,0.02,'consent.calls = true (зафиксировано при сборе заявки)',0,0,1,0.56,'Ответил автоответчик, оставлено голосовое сообщение.',NULL,1,'2026-08-20 00:00:00','2026-08-20 00:00:10','2026-08-20 00:00:00'),
  ('acl_01ou','tnt_0017','aag_017b','acp_017i','con_00es','org_00en','deal_00oi',NULL,'outbound','+998 80 674 61 58','completed','opted_out',174,0.26,'consent.calls = true (зафиксировано при сборе заявки)',1,1,1,0.6,'Клиент запросил исключение из обзвона — добавлен в suppression list.','[ИИ] Здравствуйте, это ИИ-помощник компании Nexio Lab. Звонок записывается для контроля качества.
[Клиент] Да, слушаю.
[ИИ] Удобно обсудить внедрение CRM на 2 минуты?
[Клиент] Давайте, расскажите.
[ИИ] Отлично, задам несколько уточняющих вопросов...',1,'2026-10-12 02:00:00','2026-10-12 02:02:54','2026-10-12 02:00:00'),
  ('acl_01ow','tnt_0017','aag_017a','acp_017h','con_00fa','org_00f5',NULL,NULL,'outbound','+998 84 612 95 73','completed','not_interested',159,0.24,'consent.calls = true (зафиксировано при сборе заявки)',1,1,1,0.63,'Клиент сообщил об отсутствии потребности на текущий момент.','[ИИ] Здравствуйте, это ИИ-помощник компании Nexio Lab. Звонок записывается для контроля качества.
[Клиент] Да, слушаю.
[ИИ] Удобно обсудить внедрение CRM на 2 минуты?
[Клиент] Нам это сейчас не нужно.
[ИИ] Отлично, задам несколько уточняющих вопросов...',1,'2026-08-21 04:00:00','2026-08-21 04:02:39','2026-08-21 04:00:00'),
  ('acl_01oy','tnt_0017','aag_017a','acp_017i','con_00es','org_00fc',NULL,NULL,'outbound','+998 84 249 17 49','completed','voicemail',2,0,'consent.calls = true (зафиксировано при сборе заявки)',0,0,1,0.68,'Ответил автоответчик, оставлено голосовое сообщение.',NULL,1,'2026-10-02 00:00:00','2026-10-02 00:00:02','2026-10-02 00:00:00'),
  ('acl_01oz','tnt_0017','aag_017b','acp_017h','con_00es','org_00fc',NULL,'ord_00ve','outbound','+998 81 217 17 95','failed','interested',217,0.33,'consent.calls = true (зафиксировано при сборе заявки)',1,1,1,0.96,'Клиент заинтересовался тарифом Business, запросил коммерческое предложение.','[ИИ] Здравствуйте, это ИИ-помощник компании Nexio Lab. Звонок записывается для контроля качества.
[Клиент] Да, слушаю.
[ИИ] Удобно обсудить внедрение CRM на 2 минуты?
[Клиент] Давайте, расскажите.
[ИИ] Отлично, задам несколько уточняющих вопросов...',1,'2026-09-29 00:00:00','2026-09-29 00:03:37','2026-09-29 00:00:00'),
  ('acl_01p1','tnt_0017','aag_017a',NULL,'con_00fg','org_00f2','deal_00oa',NULL,'outbound','+998 90 684 20 66','completed','not_interested',409,0.61,'consent.calls = true (зафиксировано при сборе заявки)',1,0,1,0.43,'Клиент сообщил об отсутствии потребности на текущий момент.','[ИИ] Здравствуйте, это ИИ-помощник компании Nexio Lab. Звонок записывается для контроля качества.
[Клиент] Да, слушаю.
[ИИ] Удобно обсудить внедрение CRM на 2 минуты?
[Клиент] Нам это сейчас не нужно.
[ИИ] Отлично, задам несколько уточняющих вопросов...',1,'2026-09-10 09:00:00','2026-09-10 09:06:49','2026-09-10 09:00:00'),
  ('acl_01p3','tnt_0017','aag_017b','acp_017h','con_00ez','org_00f0',NULL,NULL,'outbound','+998 92 481 15 59','completed','interested',317,0.48,'consent.calls = true (зафиксировано при сборе заявки)',1,0,0,0.88,'Клиент заинтересовался тарифом Business, запросил коммерческое предложение.','[ИИ] Здравствуйте, это ИИ-помощник компании Nexio Lab. Звонок записывается для контроля качества.
[Клиент] Да, слушаю.
[ИИ] Удобно обсудить внедрение CRM на 2 минуты?
[Клиент] Давайте, расскажите.
[ИИ] Отлично, задам несколько уточняющих вопросов...',1,'2026-09-14 13:00:00','2026-09-14 13:05:17','2026-09-14 13:00:00'),
  ('acl_01p5','tnt_0017','aag_017a','acp_017i','con_00ez','org_00en',NULL,NULL,'outbound','+998 72 275 80 19','completed','no_answer',19,0.03,'consent.calls = true (зафиксировано при сборе заявки)',1,0,1,0.7,'Нет ответа после 4 гудков, назначен повторный дозвон.',NULL,1,'2026-08-16 09:00:00','2026-08-16 09:00:19','2026-08-16 09:00:00'),
  ('acl_01p6','tnt_0017','aag_017c','acp_017h','con_00f4','org_00f2',NULL,NULL,'outbound','+998 83 401 79 65','completed','qualified',386,0.58,'consent.calls = true (зафиксировано при сборе заявки)',1,0,0,0.79,'Квалифицирован: есть бюджет, потребность в интеграции и ЛПР на линии.','[ИИ] Здравствуйте, это ИИ-помощник компании Nexio Lab. Звонок записывается для контроля качества.
[Клиент] Да, слушаю.
[ИИ] Удобно обсудить внедрение CRM на 2 минуты?
[Клиент] Давайте, расскажите.
[ИИ] Отлично, задам несколько уточняющих вопросов...',1,'2026-09-30 04:00:00','2026-09-30 04:06:26','2026-09-30 04:00:00'),
  ('acl_01p8','tnt_0017','aag_017c','acp_017h','con_00f4','org_00f0',NULL,NULL,'outbound','+998 80 137 60 90','completed','opted_out',323,0.48,'consent.calls = true (зафиксировано при сборе заявки)',1,1,1,0.61,'Клиент запросил исключение из обзвона — добавлен в suppression list.','[ИИ] Здравствуйте, это ИИ-помощник компании Nexio Lab. Звонок записывается для контроля качества.
[Клиент] Да, слушаю.
[ИИ] Удобно обсудить внедрение CRM на 2 минуты?
[Клиент] Давайте, расскажите.
[ИИ] Отлично, задам несколько уточняющих вопросов...',1,'2026-09-20 22:00:00','2026-09-20 22:05:23','2026-09-20 22:00:00'),
  ('acl_01pa','tnt_0017','aag_017c','acp_017h','con_00f6','org_00ev',NULL,NULL,'outbound','+998 98 525 67 40','completed','opted_out',122,0.18,'consent.calls = true (зафиксировано при сборе заявки)',1,1,1,0.31,'Клиент запросил исключение из обзвона — добавлен в suppression list.','[ИИ] Здравствуйте, это ИИ-помощник компании Nexio Lab. Звонок записывается для контроля качества.
[Клиент] Да, слушаю.
[ИИ] Удобно обсудить внедрение CRM на 2 минуты?
[Клиент] Давайте, расскажите.
[ИИ] Отлично, задам несколько уточняющих вопросов...',1,'2026-09-02 16:00:00','2026-09-02 16:02:02','2026-09-02 16:00:00'),
  ('acl_01pc','tnt_0017','aag_017c','acp_017i','con_00fg','org_00f0',NULL,NULL,'outbound','+998 95 880 93 95','completed','interested',215,0.32,'consent.calls = true (зафиксировано при сборе заявки)',1,1,1,0.88,'Клиент заинтересовался тарифом Business, запросил коммерческое предложение.','[ИИ] Здравствуйте, это ИИ-помощник компании Nexio Lab. Звонок записывается для контроля качества.
[Клиент] Да, слушаю.
[ИИ] Удобно обсудить внедрение CRM на 2 минуты?
[Клиент] Давайте, расскажите.
[ИИ] Отлично, задам несколько уточняющих вопросов...',1,'2026-09-15 16:00:00','2026-09-15 16:03:35','2026-09-15 16:00:00'),
  ('acl_01pe','tnt_0017','aag_017c','acp_017i','con_00fb','org_00f9',NULL,NULL,'outbound','+998 78 405 81 78','completed','interested',67,0.1,'consent.calls = true (зафиксировано при сборе заявки)',1,0,0,0.88,'Клиент заинтересовался тарифом Business, запросил коммерческое предложение.','[ИИ] Здравствуйте, это ИИ-помощник компании Nexio Lab. Звонок записывается для контроля качества.
[Клиент] Да, слушаю.
[ИИ] Удобно обсудить внедрение CRM на 2 минуты?
[Клиент] Давайте, расскажите.
[ИИ] Отлично, задам несколько уточняющих вопросов...',1,'2026-10-09 18:00:00','2026-10-09 18:01:07','2026-10-09 18:00:00'),
  ('acl_01pg','tnt_0017','aag_017c','acp_017h','con_00ex','org_00ey',NULL,NULL,'outbound','+998 98 749 79 47','completed','opted_out',129,0.19,'consent.calls = true (зафиксировано при сборе заявки)',1,1,1,0.54,'Клиент запросил исключение из обзвона — добавлен в suppression list.','[ИИ] Здравствуйте, это ИИ-помощник компании Nexio Lab. Звонок записывается для контроля качества.
[Клиент] Да, слушаю.
[ИИ] Удобно обсудить внедрение CRM на 2 минуты?
[Клиент] Давайте, расскажите.
[ИИ] Отлично, задам несколько уточняющих вопросов...',1,'2026-09-22 22:00:00','2026-09-22 22:02:09','2026-09-22 22:00:00'),
  ('acl_01pi','tnt_0017','aag_017b','acp_017i','con_00f6','org_00ey','deal_00n3',NULL,'outbound','+998 92 476 13 76','completed','no_answer',22,0.03,'consent.calls = true (зафиксировано при сборе заявки)',1,0,0,0.65,'Нет ответа после 4 гудков, назначен повторный дозвон.',NULL,1,'2026-09-21 19:00:00','2026-09-21 19:00:22','2026-09-21 19:00:00'),
  ('acl_01pj','tnt_0017','aag_017b','acp_017h','con_00ex','org_00f7',NULL,NULL,'outbound','+998 73 594 41 17','completed','objection',105,0.16,'consent.calls = true (зафиксировано при сборе заявки)',1,0,1,0.43,'Возражение по цене. Агент привёл сравнение с текущими расходами, клиент попросил паузу.','[ИИ] Здравствуйте, это ИИ-помощник компании Nexio Lab. Звонок записывается для контроля качества.
[Клиент] Да, слушаю.
[ИИ] Удобно обсудить внедрение CRM на 2 минуты?
[Клиент] Давайте, расскажите.
[ИИ] Отлично, задам несколько уточняющих вопросов...',1,'2026-10-11 23:00:00','2026-10-11 23:01:45','2026-10-11 23:00:00'),
  ('acl_01pl','tnt_0017','aag_017b','acp_017i','con_00fa','org_00f0','deal_00nc',NULL,'outbound','+998 89 108 11 24','completed','objection',122,0.18,'consent.calls = true (зафиксировано при сборе заявки)',1,1,1,0.65,'Возражение по цене. Агент привёл сравнение с текущими расходами, клиент попросил паузу.','[ИИ] Здравствуйте, это ИИ-помощник компании Nexio Lab. Звонок записывается для контроля качества.
[Клиент] Да, слушаю.
[ИИ] Удобно обсудить внедрение CRM на 2 минуты?
[Клиент] Давайте, расскажите.
[ИИ] Отлично, задам несколько уточняющих вопросов...',1,'2026-08-28 04:00:00','2026-08-28 04:02:02','2026-08-28 04:00:00'),
  ('acl_01pn','tnt_0017','aag_017c','acp_017h','con_00fg','org_00et',NULL,NULL,'outbound','+998 76 209 79 58','completed','qualified',135,0.2,'consent.calls = true (зафиксировано при сборе заявки)',1,1,1,0.83,'Квалифицирован: есть бюджет, потребность в интеграции и ЛПР на линии.','[ИИ] Здравствуйте, это ИИ-помощник компании Nexio Lab. Звонок записывается для контроля качества.
[Клиент] Да, слушаю.
[ИИ] Удобно обсудить внедрение CRM на 2 минуты?
[Клиент] Давайте, расскажите.
[ИИ] Отлично, задам несколько уточняющих вопросов...',1,'2026-10-03 04:00:00','2026-10-03 04:02:15','2026-10-03 04:00:00'),
  ('acl_01pp','tnt_0017','aag_017a',NULL,'con_00fa','org_00f5',NULL,NULL,'outbound','+998 79 402 76 53','completed','qualified',100,0.15,'consent.calls = true (зафиксировано при сборе заявки)',1,0,1,0.74,'Квалифицирован: есть бюджет, потребность в интеграции и ЛПР на линии.','[ИИ] Здравствуйте, это ИИ-помощник компании Nexio Lab. Звонок записывается для контроля качества.
[Клиент] Да, слушаю.
[ИИ] Удобно обсудить внедрение CRM на 2 минуты?
[Клиент] Давайте, расскажите.
[ИИ] Отлично, задам несколько уточняющих вопросов...',1,'2026-10-02 15:00:00','2026-10-02 15:01:40','2026-10-02 15:00:00'),
  ('acl_01pr','tnt_0017','aag_017a','acp_017i','con_00f3','org_00f5',NULL,'ord_00w4','outbound','+998 93 791 20 83','completed','interested',386,0.58,'consent.calls = true (зафиксировано при сборе заявки)',1,1,1,0.87,'Клиент заинтересовался тарифом Business, запросил коммерческое предложение.','[ИИ] Здравствуйте, это ИИ-помощник компании Nexio Lab. Звонок записывается для контроля качества.
[Клиент] Да, слушаю.
[ИИ] Удобно обсудить внедрение CRM на 2 минуты?
[Клиент] Давайте, расскажите.
[ИИ] Отлично, задам несколько уточняющих вопросов...',1,'2026-09-11 03:00:00','2026-09-11 03:06:26','2026-09-11 03:00:00'),
  ('acl_01pt','tnt_0017','aag_017b','acp_017i','con_00ez','org_00en',NULL,NULL,'outbound','+998 93 258 46 78','completed','no_answer',15,0.02,'consent.calls = true (зафиксировано при сборе заявки)',1,0,1,0.43,'Нет ответа после 4 гудков, назначен повторный дозвон.',NULL,1,'2026-09-18 00:00:00','2026-09-18 00:00:15','2026-09-18 00:00:00'),
  ('acl_01pu','tnt_0017','aag_017c','acp_017i','con_00es','org_00et',NULL,NULL,'outbound','+998 79 998 52 54','completed','not_interested',124,0.19,'consent.calls = true (зафиксировано при сборе заявки)',1,1,1,0.43,'Клиент сообщил об отсутствии потребности на текущий момент.','[ИИ] Здравствуйте, это ИИ-помощник компании Nexio Lab. Звонок записывается для контроля качества.
[Клиент] Да, слушаю.
[ИИ] Удобно обсудить внедрение CRM на 2 минуты?
[Клиент] Нам это сейчас не нужно.
[ИИ] Отлично, задам несколько уточняющих вопросов...',1,'2026-09-05 15:00:00','2026-09-05 15:02:04','2026-09-05 15:00:00'),
  ('acl_01pw','tnt_0017','aag_017c','acp_017h','con_00ep','org_00fc',NULL,NULL,'outbound','+998 74 105 43 67','failed','voicemail',17,0.03,'consent.calls = true (зафиксировано при сборе заявки)',1,0,1,0.57,'Ответил автоответчик, оставлено голосовое сообщение.',NULL,1,'2026-09-06 15:00:00','2026-09-06 15:00:17','2026-09-06 15:00:00'),
  ('acl_01px','tnt_0017','aag_017a','acp_017h','con_00eu','org_00ey',NULL,NULL,'outbound','+998 87 409 78 91','failed','no_answer',15,0.02,'consent.calls = true (зафиксировано при сборе заявки)',1,0,0,0.52,'Нет ответа после 4 гудков, назначен повторный дозвон.',NULL,1,'2026-08-22 16:00:00','2026-08-22 16:00:15','2026-08-22 16:00:00'),
  ('acl_01py','tnt_0017','aag_017b','acp_017i','con_00fd','org_00f5',NULL,NULL,'outbound','+998 99 625 75 32','completed','human_handoff',199,0.3,'consent.calls = true (зафиксировано при сборе заявки)',1,0,0,0.62,'Запросил связь с человеком, переведён на менеджера.','[ИИ] Здравствуйте, это ИИ-помощник компании Nexio Lab. Звонок записывается для контроля качества.
[Клиент] Да, слушаю.
[ИИ] Удобно обсудить внедрение CRM на 2 минуты?
[Клиент] Давайте, расскажите.
[ИИ] Отлично, задам несколько уточняющих вопросов...',1,'2026-09-23 03:00:00','2026-09-23 03:03:19','2026-09-23 03:00:00'),
  ('acl_01q0','tnt_0017','aag_017c',NULL,'con_00ew','org_00ey',NULL,NULL,'outbound','+998 94 402 81 26','completed','opted_out',108,0.16,'consent.calls = true (зафиксировано при сборе заявки)',1,1,1,0.48,'Клиент запросил исключение из обзвона — добавлен в suppression list.','[ИИ] Здравствуйте, это ИИ-помощник компании Nexio Lab. Звонок записывается для контроля качества.
[Клиент] Да, слушаю.
[ИИ] Удобно обсудить внедрение CRM на 2 минуты?
[Клиент] Давайте, расскажите.
[ИИ] Отлично, задам несколько уточняющих вопросов...',1,'2026-09-20 02:00:00','2026-09-20 02:01:48','2026-09-20 02:00:00'),
  ('acl_01q2','tnt_0017','aag_017b','acp_017i','con_00fd','org_00ev',NULL,NULL,'outbound','+998 85 957 87 31','completed','qualified',376,0.56,'consent.calls = true (зафиксировано при сборе заявки)',1,0,0,0.81,'Квалифицирован: есть бюджет, потребность в интеграции и ЛПР на линии.','[ИИ] Здравствуйте, это ИИ-помощник компании Nexio Lab. Звонок записывается для контроля качества.
[Клиент] Да, слушаю.
[ИИ] Удобно обсудить внедрение CRM на 2 минуты?
[Клиент] Давайте, расскажите.
[ИИ] Отлично, задам несколько уточняющих вопросов...',1,'2026-09-25 17:00:00','2026-09-25 17:06:16','2026-09-25 17:00:00'),
  ('acl_01q4','tnt_0017','aag_017b','acp_017i','con_00fe','org_00ev',NULL,NULL,'outbound','+998 85 783 53 54','completed','interested',274,0.41,'consent.calls = true (зафиксировано при сборе заявки)',1,1,1,0.87,'Клиент заинтересовался тарифом Business, запросил коммерческое предложение.','[ИИ] Здравствуйте, это ИИ-помощник компании Nexio Lab. Звонок записывается для контроля качества.
[Клиент] Да, слушаю.
[ИИ] Удобно обсудить внедрение CRM на 2 минуты?
[Клиент] Давайте, расскажите.
[ИИ] Отлично, задам несколько уточняющих вопросов...',1,'2026-09-10 23:00:00','2026-09-10 23:04:34','2026-09-10 23:00:00'),
  ('acl_01q7','tnt_0017','aag_017a',NULL,'con_00f4','org_00en',NULL,NULL,'outbound','+998 92 322 62 80','completed','not_interested',202,0.3,'consent.calls = true (зафиксировано при сборе заявки)',1,1,1,0.63,'Клиент сообщил об отсутствии потребности на текущий момент.','[ИИ] Здравствуйте, это ИИ-помощник компании Nexio Lab. Звонок записывается для контроля качества.
[Клиент] Да, слушаю.
[ИИ] Удобно обсудить внедрение CRM на 2 минуты?
[Клиент] Нам это сейчас не нужно.
[ИИ] Отлично, задам несколько уточняющих вопросов...',1,'2026-08-16 15:00:00','2026-08-16 15:03:22','2026-08-16 15:00:00'),
  ('acl_01q9','tnt_0017','aag_017a',NULL,'con_00ez','org_00eq','deal_00oq',NULL,'outbound','+998 70 778 71 17','completed','voicemail',21,0.03,'consent.calls = true (зафиксировано при сборе заявки)',1,0,1,0.47,'Ответил автоответчик, оставлено голосовое сообщение.',NULL,1,'2026-09-17 22:00:00','2026-09-17 22:00:21','2026-09-17 22:00:00'),
  ('acl_01qa','tnt_0017','aag_017b','acp_017h','con_00f3','org_00ev','deal_00o0',NULL,'outbound','+998 71 847 91 88','completed','opted_out',351,0.53,'consent.calls = true (зафиксировано при сборе заявки)',1,1,1,0.39,'Клиент запросил исключение из обзвона — добавлен в suppression list.','[ИИ] Здравствуйте, это ИИ-помощник компании Nexio Lab. Звонок записывается для контроля качества.
[Клиент] Да, слушаю.
[ИИ] Удобно обсудить внедрение CRM на 2 минуты?
[Клиент] Давайте, расскажите.
[ИИ] Отлично, задам несколько уточняющих вопросов...',1,'2026-09-05 00:00:00','2026-09-05 00:05:51','2026-09-05 00:00:00'),
  ('acl_01qc','tnt_0017','aag_017b','acp_017h','con_00f1','org_00f0','deal_00ol',NULL,'outbound','+998 76 723 42 84','completed','qualified',79,0.12,'consent.calls = true (зафиксировано при сборе заявки)',1,0,0,0.84,'Квалифицирован: есть бюджет, потребность в интеграции и ЛПР на линии.','[ИИ] Здравствуйте, это ИИ-помощник компании Nexio Lab. Звонок записывается для контроля качества.
[Клиент] Да, слушаю.
[ИИ] Удобно обсудить внедрение CRM на 2 минуты?
[Клиент] Давайте, расскажите.
[ИИ] Отлично, задам несколько уточняющих вопросов...',1,'2026-09-04 05:00:00','2026-09-04 05:01:19','2026-09-04 05:00:00');
INSERT INTO ai_calls VALUES
  ('acl_01qe','tnt_0017','aag_017b','acp_017i','con_00er','org_00ey',NULL,NULL,'outbound','+998 73 802 57 96','completed','opted_out',102,0.15,'consent.calls = true (зафиксировано при сборе заявки)',1,0,0,0.41,'Клиент запросил исключение из обзвона — добавлен в suppression list.','[ИИ] Здравствуйте, это ИИ-помощник компании Nexio Lab. Звонок записывается для контроля качества.
[Клиент] Да, слушаю.
[ИИ] Удобно обсудить внедрение CRM на 2 минуты?
[Клиент] Давайте, расскажите.
[ИИ] Отлично, задам несколько уточняющих вопросов...',1,'2026-08-31 05:00:00','2026-08-31 05:01:42','2026-08-31 05:00:00'),
  ('acl_01qg','tnt_0017','aag_017c',NULL,'con_00fd','org_00et','deal_00nr',NULL,'outbound','+998 92 869 64 46','completed','qualified',326,0.49,'consent.calls = true (зафиксировано при сборе заявки)',1,1,1,0.9,'Квалифицирован: есть бюджет, потребность в интеграции и ЛПР на линии.','[ИИ] Здравствуйте, это ИИ-помощник компании Nexio Lab. Звонок записывается для контроля качества.
[Клиент] Да, слушаю.
[ИИ] Удобно обсудить внедрение CRM на 2 минуты?
[Клиент] Давайте, расскажите.
[ИИ] Отлично, задам несколько уточняющих вопросов...',1,'2026-10-03 06:00:00','2026-10-03 06:05:26','2026-10-03 06:00:00'),
  ('acl_01qi','tnt_0017','aag_017b','acp_017i','con_00f8','org_00ff',NULL,NULL,'outbound','+998 96 116 27 76','completed','voicemail',4,0.01,'consent.calls = true (зафиксировано при сборе заявки)',0,0,1,0.4,'Ответил автоответчик, оставлено голосовое сообщение.',NULL,1,'2026-08-31 20:00:00','2026-08-31 20:00:04','2026-08-31 20:00:00'),
  ('acl_01qj','tnt_0017','aag_017a','acp_017h','con_00eu','org_00f2',NULL,'ord_00vb','outbound','+998 91 461 67 93','completed','interested',230,0.35,'consent.calls = true (зафиксировано при сборе заявки)',1,0,0,0.82,'Клиент заинтересовался тарифом Business, запросил коммерческое предложение.','[ИИ] Здравствуйте, это ИИ-помощник компании Nexio Lab. Звонок записывается для контроля качества.
[Клиент] Да, слушаю.
[ИИ] Удобно обсудить внедрение CRM на 2 минуты?
[Клиент] Давайте, расскажите.
[ИИ] Отлично, задам несколько уточняющих вопросов...',1,'2026-09-03 21:00:00','2026-09-03 21:03:50','2026-09-03 21:00:00'),
  ('acl_01ql','tnt_0017','aag_017b','acp_017h','con_00er','org_00en',NULL,NULL,'outbound','+998 80 366 86 61','completed','interested',158,0.24,'consent.calls = true (зафиксировано при сборе заявки)',1,1,1,0.93,'Клиент заинтересовался тарифом Business, запросил коммерческое предложение.','[ИИ] Здравствуйте, это ИИ-помощник компании Nexio Lab. Звонок записывается для контроля качества.
[Клиент] Да, слушаю.
[ИИ] Удобно обсудить внедрение CRM на 2 минуты?
[Клиент] Давайте, расскажите.
[ИИ] Отлично, задам несколько уточняющих вопросов...',1,'2026-09-13 00:00:00','2026-09-13 00:02:38','2026-09-13 00:00:00'),
  ('acl_01qo','tnt_0017','aag_017a','acp_017h','con_00fb','org_00f2',NULL,NULL,'outbound','+998 70 885 44 17','completed','opted_out',132,0.2,'consent.calls = true (зафиксировано при сборе заявки)',1,0,1,0.33,'Клиент запросил исключение из обзвона — добавлен в suppression list.','[ИИ] Здравствуйте, это ИИ-помощник компании Nexio Lab. Звонок записывается для контроля качества.
[Клиент] Да, слушаю.
[ИИ] Удобно обсудить внедрение CRM на 2 минуты?
[Клиент] Давайте, расскажите.
[ИИ] Отлично, задам несколько уточняющих вопросов...',1,'2026-09-02 23:00:00','2026-09-02 23:02:12','2026-09-02 23:00:00'),
  ('acl_01qq','tnt_0017','aag_017b',NULL,'con_00fb','org_00f7',NULL,NULL,'outbound','+998 89 269 66 81','completed','not_interested',137,0.21,'consent.calls = true (зафиксировано при сборе заявки)',1,1,1,0.38,'Клиент сообщил об отсутствии потребности на текущий момент.','[ИИ] Здравствуйте, это ИИ-помощник компании Nexio Lab. Звонок записывается для контроля качества.
[Клиент] Да, слушаю.
[ИИ] Удобно обсудить внедрение CRM на 2 минуты?
[Клиент] Нам это сейчас не нужно.
[ИИ] Отлично, задам несколько уточняющих вопросов...',1,'2026-10-03 21:00:00','2026-10-03 21:02:17','2026-10-03 21:00:00'),
  ('acl_01qs','tnt_0017','aag_017c','acp_017i','con_00fd','org_00f5',NULL,'ord_00vs','outbound','+998 76 845 52 24','failed','interested',414,0.62,'consent.calls = true (зафиксировано при сборе заявки)',1,1,1,0.95,'Клиент заинтересовался тарифом Business, запросил коммерческое предложение.','[ИИ] Здравствуйте, это ИИ-помощник компании Nexio Lab. Звонок записывается для контроля качества.
[Клиент] Да, слушаю.
[ИИ] Удобно обсудить внедрение CRM на 2 минуты?
[Клиент] Давайте, расскажите.
[ИИ] Отлично, задам несколько уточняющих вопросов...',1,'2026-10-07 13:00:00','2026-10-07 13:06:54','2026-10-07 13:00:00'),
  ('acl_01qu','tnt_0017','aag_017b','acp_017i','con_00fa','org_00fc','deal_00n3',NULL,'outbound','+998 93 977 17 34','completed','opted_out',365,0.55,'consent.calls = true (зафиксировано при сборе заявки)',1,1,1,0.66,'Клиент запросил исключение из обзвона — добавлен в suppression list.','[ИИ] Здравствуйте, это ИИ-помощник компании Nexio Lab. Звонок записывается для контроля качества.
[Клиент] Да, слушаю.
[ИИ] Удобно обсудить внедрение CRM на 2 минуты?
[Клиент] Давайте, расскажите.
[ИИ] Отлично, задам несколько уточняющих вопросов...',1,'2026-10-10 07:00:00','2026-10-10 07:06:05','2026-10-10 07:00:00'),
  ('acl_01qw','tnt_0017','aag_017a','acp_017i','con_00f1','org_00eq',NULL,NULL,'outbound','+998 94 203 28 66','completed','not_interested',89,0.13,'consent.calls = true (зафиксировано при сборе заявки)',1,1,1,0.7,'Клиент сообщил об отсутствии потребности на текущий момент.','[ИИ] Здравствуйте, это ИИ-помощник компании Nexio Lab. Звонок записывается для контроля качества.
[Клиент] Да, слушаю.
[ИИ] Удобно обсудить внедрение CRM на 2 минуты?
[Клиент] Нам это сейчас не нужно.
[ИИ] Отлично, задам несколько уточняющих вопросов...',1,'2026-10-12 02:00:00','2026-10-12 02:01:29','2026-10-12 02:00:00');

-- ai_tool_calls: 238 rows
INSERT INTO ai_tool_calls VALUES
  ('atc_017k','tnt_0016','acl_017j','check_availability','{"slot":"tomorrow 14:00"}','available',0,'2026-09-25 22:00:00'),
  ('atc_017m','tnt_0016','acl_017l','update_deal_stage','{"stage":"negotiation"}','updated',1,'2026-09-09 04:00:00'),
  ('atc_017o','tnt_0016','acl_017n','lookup_product','{"query":"CRM"}','found',0,'2026-10-04 06:00:00'),
  ('atc_017q','tnt_0016','acl_017p','update_deal_stage','{"stage":"negotiation"}','updated',1,'2026-09-25 20:00:00'),
  ('atc_017s','tnt_0016','acl_017r','send_proposal','{"channel":"email"}','queued',0,'2026-09-07 03:00:00'),
  ('atc_017u','tnt_0016','acl_017t','check_availability','{"slot":"tomorrow 14:00"}','available',0,'2026-10-04 06:00:00'),
  ('atc_017x','tnt_0016','acl_017w','send_proposal','{"channel":"email"}','queued',0,'2026-10-02 21:00:00'),
  ('atc_017z','tnt_0016','acl_017y','lookup_product','{"query":"CRM"}','found',1,'2026-10-02 19:00:00'),
  ('atc_0181','tnt_0016','acl_0180','create_task','{"title":"Перезвонить"}','created',0,'2026-09-26 20:00:00'),
  ('atc_0183','tnt_0016','acl_0182','lookup_product','{"query":"CRM"}','found',0,'2026-08-15 05:00:00'),
  ('atc_0185','tnt_0016','acl_0184','create_task','{"title":"Перезвонить"}','created',0,'2026-09-19 23:00:00'),
  ('atc_0188','tnt_0016','acl_0187','update_deal_stage','{"stage":"negotiation"}','updated',0,'2026-09-22 13:00:00'),
  ('atc_018a','tnt_0016','acl_0189','send_proposal','{"channel":"email"}','queued',1,'2026-09-06 05:00:00'),
  ('atc_018c','tnt_0016','acl_018b','update_deal_stage','{"stage":"negotiation"}','updated',0,'2026-09-24 06:00:00'),
  ('atc_018g','tnt_0016','acl_018f','lookup_product','{"query":"CRM"}','found',0,'2026-08-31 03:00:00'),
  ('atc_018i','tnt_0016','acl_018h','update_deal_stage','{"stage":"negotiation"}','updated',0,'2026-09-19 09:00:00'),
  ('atc_018k','tnt_0016','acl_018j','check_availability','{"slot":"tomorrow 14:00"}','available',0,'2026-08-29 16:00:00'),
  ('atc_018n','tnt_0016','acl_018m','send_proposal','{"channel":"email"}','queued',0,'2026-08-14 23:00:00'),
  ('atc_018p','tnt_0016','acl_018o','create_task','{"title":"Перезвонить"}','created',1,'2026-10-01 19:00:00'),
  ('atc_018r','tnt_0016','acl_018q','lookup_product','{"query":"CRM"}','found',0,'2026-09-03 16:00:00'),
  ('atc_018u','tnt_0016','acl_018t','check_availability','{"slot":"tomorrow 14:00"}','available',0,'2026-09-29 19:00:00'),
  ('atc_018w','tnt_0016','acl_018v','send_proposal','{"channel":"email"}','queued',0,'2026-08-15 09:00:00'),
  ('atc_018y','tnt_0016','acl_018x','update_deal_stage','{"stage":"negotiation"}','updated',0,'2026-09-02 23:00:00'),
  ('atc_0190','tnt_0016','acl_018z','send_proposal','{"channel":"email"}','queued',1,'2026-10-07 17:00:00'),
  ('atc_0192','tnt_0016','acl_0191','lookup_product','{"query":"CRM"}','found',0,'2026-09-22 09:00:00'),
  ('atc_0196','tnt_0016','acl_0195','check_availability','{"slot":"tomorrow 14:00"}','available',1,'2026-10-09 06:00:00'),
  ('atc_0198','tnt_0016','acl_0197','check_availability','{"slot":"tomorrow 14:00"}','available',1,'2026-09-20 13:00:00'),
  ('atc_019a','tnt_0016','acl_0199','send_proposal','{"channel":"email"}','queued',0,'2026-10-09 09:00:00'),
  ('atc_019g','tnt_0016','acl_019f','create_task','{"title":"Перезвонить"}','created',1,'2026-08-30 14:00:00'),
  ('atc_019j','tnt_0016','acl_019i','check_availability','{"slot":"tomorrow 14:00"}','available',0,'2026-09-07 16:00:00'),
  ('atc_019l','tnt_0016','acl_019k','send_proposal','{"channel":"email"}','queued',0,'2026-09-25 14:00:00'),
  ('atc_019p','tnt_0016','acl_019o','update_deal_stage','{"stage":"negotiation"}','updated',0,'2026-10-07 20:00:00'),
  ('atc_019r','tnt_0016','acl_019q','update_deal_stage','{"stage":"negotiation"}','updated',0,'2026-08-12 13:00:00'),
  ('atc_019t','tnt_0016','acl_019s','lookup_product','{"query":"CRM"}','found',0,'2026-09-14 23:00:00'),
  ('atc_019w','tnt_0016','acl_019v','send_proposal','{"channel":"email"}','queued',0,'2026-09-27 22:00:00'),
  ('atc_019y','tnt_0016','acl_019x','create_task','{"title":"Перезвонить"}','created',0,'2026-08-23 03:00:00'),
  ('atc_01a0','tnt_0016','acl_019z','create_task','{"title":"Перезвонить"}','created',1,'2026-10-05 16:00:00'),
  ('atc_01a2','tnt_0016','acl_01a1','update_deal_stage','{"stage":"negotiation"}','updated',0,'2026-10-05 16:00:00'),
  ('atc_01a4','tnt_0016','acl_01a3','create_task','{"title":"Перезвонить"}','created',1,'2026-08-28 18:00:00'),
  ('atc_01a7','tnt_0016','acl_01a6','lookup_product','{"query":"CRM"}','found',1,'2026-09-17 05:00:00'),
  ('atc_01a9','tnt_0016','acl_01a8','create_task','{"title":"Перезвонить"}','created',0,'2026-10-11 18:00:00'),
  ('atc_01ab','tnt_0016','acl_01aa','send_proposal','{"channel":"email"}','queued',0,'2026-10-09 15:00:00'),
  ('atc_01ad','tnt_0016','acl_01ac','update_deal_stage','{"stage":"negotiation"}','updated',0,'2026-09-04 18:00:00'),
  ('atc_01af','tnt_0016','acl_01ae','check_availability','{"slot":"tomorrow 14:00"}','available',1,'2026-08-27 08:00:00'),
  ('atc_01ai','tnt_0016','acl_01ah','send_proposal','{"channel":"email"}','queued',0,'2026-10-09 19:00:00'),
  ('atc_01ak','tnt_0016','acl_01aj','send_proposal','{"channel":"email"}','queued',1,'2026-10-02 09:00:00'),
  ('atc_01am','tnt_0016','acl_01al','update_deal_stage','{"stage":"negotiation"}','updated',1,'2026-09-24 02:00:00'),
  ('atc_01ao','tnt_0016','acl_01an','send_proposal','{"channel":"email"}','queued',0,'2026-09-25 04:00:00'),
  ('atc_01aq','tnt_0016','acl_01ap','check_availability','{"slot":"tomorrow 14:00"}','available',0,'2026-09-13 00:00:00'),
  ('atc_01as','tnt_0016','acl_01ar','create_task','{"title":"Перезвонить"}','created',0,'2026-09-29 14:00:00');
INSERT INTO ai_tool_calls VALUES
  ('atc_01au','tnt_0016','acl_01at','create_task','{"title":"Перезвонить"}','created',0,'2026-08-27 20:00:00'),
  ('atc_01aw','tnt_0016','acl_01av','lookup_product','{"query":"CRM"}','found',0,'2026-09-21 08:00:00'),
  ('atc_01ay','tnt_0016','acl_01ax','check_availability','{"slot":"tomorrow 14:00"}','available',1,'2026-08-21 06:00:00'),
  ('atc_01b1','tnt_0016','acl_01b0','create_task','{"title":"Перезвонить"}','created',1,'2026-09-21 15:00:00'),
  ('atc_01b3','tnt_0016','acl_01b2','send_proposal','{"channel":"email"}','queued',0,'2026-09-07 22:00:00'),
  ('atc_01b5','tnt_0016','acl_01b4','check_availability','{"slot":"tomorrow 14:00"}','available',1,'2026-08-18 19:00:00'),
  ('atc_01b8','tnt_0016','acl_01b7','check_availability','{"slot":"tomorrow 14:00"}','available',0,'2026-09-20 23:00:00'),
  ('atc_01ba','tnt_0016','acl_01b9','lookup_product','{"query":"CRM"}','found',1,'2026-08-14 03:00:00'),
  ('atc_01bd','tnt_0016','acl_01bc','send_proposal','{"channel":"email"}','queued',0,'2026-09-11 18:00:00'),
  ('atc_01bf','tnt_0016','acl_01be','lookup_product','{"query":"CRM"}','found',0,'2026-09-21 01:00:00'),
  ('atc_01bh','tnt_0016','acl_01bg','send_proposal','{"channel":"email"}','queued',0,'2026-10-01 14:00:00'),
  ('atc_01bk','tnt_0016','acl_01bj','check_availability','{"slot":"tomorrow 14:00"}','available',0,'2026-09-22 15:00:00'),
  ('atc_01bn','tnt_0016','acl_01bm','update_deal_stage','{"stage":"negotiation"}','updated',0,'2026-08-25 06:00:00'),
  ('atc_01bs','tnt_0016','acl_01br','send_proposal','{"channel":"email"}','queued',0,'2026-09-11 14:00:00'),
  ('atc_01bu','tnt_0016','acl_01bt','lookup_product','{"query":"CRM"}','found',0,'2026-08-22 19:00:00'),
  ('atc_01bw','tnt_0016','acl_01bv','lookup_product','{"query":"CRM"}','found',0,'2026-09-08 00:00:00'),
  ('atc_01by','tnt_0016','acl_01bx','create_task','{"title":"Перезвонить"}','created',1,'2026-08-20 20:00:00'),
  ('atc_01c0','tnt_0016','acl_01bz','create_task','{"title":"Перезвонить"}','created',0,'2026-08-13 00:00:00'),
  ('atc_01c2','tnt_0016','acl_01c1','create_task','{"title":"Перезвонить"}','created',0,'2026-09-19 21:00:00'),
  ('atc_01c5','tnt_0016','acl_01c4','create_task','{"title":"Перезвонить"}','created',0,'2026-10-04 01:00:00'),
  ('atc_01c7','tnt_0016','acl_01c6','check_availability','{"slot":"tomorrow 14:00"}','available',1,'2026-09-30 06:00:00'),
  ('atc_01c9','tnt_0016','acl_01c8','create_task','{"title":"Перезвонить"}','created',0,'2026-09-09 22:00:00'),
  ('atc_01cb','tnt_0016','acl_01ca','lookup_product','{"query":"CRM"}','found',1,'2026-08-18 15:00:00'),
  ('atc_01cd','tnt_0016','acl_01cc','lookup_product','{"query":"CRM"}','found',1,'2026-09-09 23:00:00'),
  ('atc_01cf','tnt_0016','acl_01ce','lookup_product','{"query":"CRM"}','found',0,'2026-10-02 08:00:00'),
  ('atc_01ch','tnt_0016','acl_01cg','lookup_product','{"query":"CRM"}','found',0,'2026-08-23 04:00:00'),
  ('atc_01cj','tnt_0016','acl_01ci','update_deal_stage','{"stage":"negotiation"}','updated',1,'2026-08-24 16:00:00'),
  ('atc_01cl','tnt_0016','acl_01ck','check_availability','{"slot":"tomorrow 14:00"}','available',0,'2026-08-17 08:00:00'),
  ('atc_01cn','tnt_0016','acl_01cm','lookup_product','{"query":"CRM"}','found',0,'2026-10-10 07:00:00'),
  ('atc_01cq','tnt_0016','acl_01cp','check_availability','{"slot":"tomorrow 14:00"}','available',0,'2026-09-05 08:00:00'),
  ('atc_01ct','tnt_0016','acl_01cs','check_availability','{"slot":"tomorrow 14:00"}','available',0,'2026-08-14 18:00:00'),
  ('atc_01cv','tnt_0016','acl_01cu','check_availability','{"slot":"tomorrow 14:00"}','available',0,'2026-09-02 07:00:00'),
  ('atc_01cx','tnt_0016','acl_01cw','check_availability','{"slot":"tomorrow 14:00"}','available',1,'2026-08-26 06:00:00'),
  ('atc_01d0','tnt_0016','acl_01cz','create_task','{"title":"Перезвонить"}','created',0,'2026-09-25 14:00:00'),
  ('atc_01d2','tnt_0016','acl_01d1','create_task','{"title":"Перезвонить"}','created',1,'2026-08-30 03:00:00'),
  ('atc_01d5','tnt_0016','acl_01d4','send_proposal','{"channel":"email"}','queued',1,'2026-09-07 15:00:00'),
  ('atc_01d8','tnt_0016','acl_01d7','check_availability','{"slot":"tomorrow 14:00"}','available',0,'2026-09-17 15:00:00'),
  ('atc_01da','tnt_0016','acl_01d9','create_task','{"title":"Перезвонить"}','created',0,'2026-09-26 05:00:00'),
  ('atc_01dc','tnt_0016','acl_01db','update_deal_stage','{"stage":"negotiation"}','updated',0,'2026-08-13 23:00:00'),
  ('atc_01de','tnt_0016','acl_01dd','send_proposal','{"channel":"email"}','queued',0,'2026-08-22 09:00:00'),
  ('atc_01dh','tnt_0016','acl_01dg','update_deal_stage','{"stage":"negotiation"}','updated',0,'2026-09-21 15:00:00'),
  ('atc_01dj','tnt_0016','acl_01di','lookup_product','{"query":"CRM"}','found',0,'2026-10-01 06:00:00'),
  ('atc_01dl','tnt_0016','acl_01dk','check_availability','{"slot":"tomorrow 14:00"}','available',0,'2026-08-25 07:00:00'),
  ('atc_01dn','tnt_0016','acl_01dm','update_deal_stage','{"stage":"negotiation"}','updated',0,'2026-09-07 04:00:00'),
  ('atc_01dq','tnt_0016','acl_01dp','check_availability','{"slot":"tomorrow 14:00"}','available',0,'2026-09-01 04:00:00'),
  ('atc_01ds','tnt_0016','acl_01dr','create_task','{"title":"Перезвонить"}','created',0,'2026-08-27 09:00:00'),
  ('atc_01du','tnt_0016','acl_01dt','create_task','{"title":"Перезвонить"}','created',0,'2026-10-03 01:00:00'),
  ('atc_01dw','tnt_0016','acl_01dv','send_proposal','{"channel":"email"}','queued',0,'2026-08-29 20:00:00'),
  ('atc_01dy','tnt_0016','acl_01dx','create_task','{"title":"Перезвонить"}','created',0,'2026-09-10 20:00:00'),
  ('atc_01e0','tnt_0016','acl_01dz','lookup_product','{"query":"CRM"}','found',0,'2026-10-07 02:00:00');
INSERT INTO ai_tool_calls VALUES
  ('atc_01e6','tnt_0016','acl_01e5','update_deal_stage','{"stage":"negotiation"}','updated',1,'2026-09-08 18:00:00'),
  ('atc_01e9','tnt_0016','acl_01e8','update_deal_stage','{"stage":"negotiation"}','updated',0,'2026-08-12 20:00:00'),
  ('atc_01ec','tnt_0016','acl_01eb','check_availability','{"slot":"tomorrow 14:00"}','available',1,'2026-09-10 01:00:00'),
  ('atc_01ef','tnt_0016','acl_01ee','update_deal_stage','{"stage":"negotiation"}','updated',1,'2026-10-06 22:00:00'),
  ('atc_01eh','tnt_0016','acl_01eg','send_proposal','{"channel":"email"}','queued',0,'2026-08-18 22:00:00'),
  ('atc_01em','tnt_0016','acl_01el','update_deal_stage','{"stage":"negotiation"}','updated',0,'2026-09-29 06:00:00'),
  ('atc_01eq','tnt_0016','acl_01ep','create_task','{"title":"Перезвонить"}','created',0,'2026-09-16 03:00:00'),
  ('atc_01es','tnt_0016','acl_01er','lookup_product','{"query":"CRM"}','found',0,'2026-08-24 14:00:00'),
  ('atc_01eu','tnt_0016','acl_01et','check_availability','{"slot":"tomorrow 14:00"}','available',1,'2026-09-19 02:00:00'),
  ('atc_01ew','tnt_0016','acl_01ev','create_task','{"title":"Перезвонить"}','created',0,'2026-09-24 17:00:00'),
  ('atc_01ey','tnt_0016','acl_01ex','lookup_product','{"query":"CRM"}','found',0,'2026-10-06 23:00:00'),
  ('atc_01f0','tnt_0016','acl_01ez','lookup_product','{"query":"CRM"}','found',0,'2026-09-05 19:00:00'),
  ('atc_01f2','tnt_0016','acl_01f1','check_availability','{"slot":"tomorrow 14:00"}','available',0,'2026-09-19 15:00:00'),
  ('atc_01f5','tnt_0016','acl_01f4','lookup_product','{"query":"CRM"}','found',0,'2026-09-30 07:00:00'),
  ('atc_01f7','tnt_0016','acl_01f6','update_deal_stage','{"stage":"negotiation"}','updated',0,'2026-08-27 17:00:00'),
  ('atc_01f9','tnt_0016','acl_01f8','update_deal_stage','{"stage":"negotiation"}','updated',1,'2026-09-07 09:00:00'),
  ('atc_01fb','tnt_0016','acl_01fa','send_proposal','{"channel":"email"}','queued',0,'2026-09-04 08:00:00'),
  ('atc_01fe','tnt_0016','acl_01fd','send_proposal','{"channel":"email"}','queued',0,'2026-10-07 15:00:00'),
  ('atc_01fg','tnt_0016','acl_01ff','lookup_product','{"query":"CRM"}','found',1,'2026-09-18 00:00:00'),
  ('atc_01fj','tnt_0016','acl_01fi','send_proposal','{"channel":"email"}','queued',0,'2026-09-09 18:00:00'),
  ('atc_01fl','tnt_0016','acl_01fk','lookup_product','{"query":"CRM"}','found',0,'2026-09-19 05:00:00'),
  ('atc_01fn','tnt_0016','acl_01fm','check_availability','{"slot":"tomorrow 14:00"}','available',0,'2026-08-27 15:00:00'),
  ('atc_01fp','tnt_0016','acl_01fo','lookup_product','{"query":"CRM"}','found',0,'2026-09-19 18:00:00'),
  ('atc_01fr','tnt_0016','acl_01fq','check_availability','{"slot":"tomorrow 14:00"}','available',0,'2026-08-25 18:00:00'),
  ('atc_01ft','tnt_0016','acl_01fs','create_task','{"title":"Перезвонить"}','created',0,'2026-08-22 09:00:00'),
  ('atc_01fv','tnt_0016','acl_01fu','update_deal_stage','{"stage":"negotiation"}','updated',1,'2026-09-11 09:00:00'),
  ('atc_01fy','tnt_0016','acl_01fx','update_deal_stage','{"stage":"negotiation"}','updated',0,'2026-09-12 01:00:00'),
  ('atc_01g0','tnt_0016','acl_01fz','create_task','{"title":"Перезвонить"}','created',1,'2026-08-22 04:00:00'),
  ('atc_01g3','tnt_0016','acl_01g2','send_proposal','{"channel":"email"}','queued',0,'2026-09-05 17:00:00'),
  ('atc_01g5','tnt_0016','acl_01g4','send_proposal','{"channel":"email"}','queued',0,'2026-10-09 07:00:00'),
  ('atc_01g7','tnt_0016','acl_01g6','send_proposal','{"channel":"email"}','queued',0,'2026-08-28 00:00:00'),
  ('atc_01g9','tnt_0016','acl_01g8','lookup_product','{"query":"CRM"}','found',0,'2026-10-07 19:00:00'),
  ('atc_01gc','tnt_0016','acl_01gb','check_availability','{"slot":"tomorrow 14:00"}','available',0,'2026-08-14 08:00:00'),
  ('atc_01gf','tnt_0016','acl_01ge','send_proposal','{"channel":"email"}','queued',0,'2026-08-26 22:00:00'),
  ('atc_01gh','tnt_0016','acl_01gg','check_availability','{"slot":"tomorrow 14:00"}','available',0,'2026-09-19 19:00:00'),
  ('atc_01gj','tnt_0016','acl_01gi','check_availability','{"slot":"tomorrow 14:00"}','available',0,'2026-09-24 13:00:00'),
  ('atc_01gl','tnt_0016','acl_01gk','create_task','{"title":"Перезвонить"}','created',0,'2026-10-01 08:00:00'),
  ('atc_01gn','tnt_0016','acl_01gm','update_deal_stage','{"stage":"negotiation"}','updated',0,'2026-08-12 17:00:00'),
  ('atc_01gp','tnt_0016','acl_01go','lookup_product','{"query":"CRM"}','found',0,'2026-10-05 04:00:00'),
  ('atc_01gr','tnt_0016','acl_01gq','check_availability','{"slot":"tomorrow 14:00"}','available',0,'2026-10-04 14:00:00'),
  ('atc_01gt','tnt_0016','acl_01gs','lookup_product','{"query":"CRM"}','found',0,'2026-09-01 05:00:00'),
  ('atc_01gv','tnt_0016','acl_01gu','send_proposal','{"channel":"email"}','queued',0,'2026-09-24 16:00:00'),
  ('atc_01gx','tnt_0016','acl_01gw','lookup_product','{"query":"CRM"}','found',0,'2026-08-23 09:00:00'),
  ('atc_01gz','tnt_0016','acl_01gy','update_deal_stage','{"stage":"negotiation"}','updated',1,'2026-09-24 22:00:00'),
  ('atc_01h3','tnt_0016','acl_01h2','send_proposal','{"channel":"email"}','queued',0,'2026-08-16 20:00:00'),
  ('atc_01h5','tnt_0016','acl_01h4','send_proposal','{"channel":"email"}','queued',0,'2026-10-08 15:00:00'),
  ('atc_01h7','tnt_0016','acl_01h6','lookup_product','{"query":"CRM"}','found',0,'2026-08-23 13:00:00'),
  ('atc_01h9','tnt_0016','acl_01h8','update_deal_stage','{"stage":"negotiation"}','updated',0,'2026-08-13 03:00:00'),
  ('atc_01hb','tnt_0016','acl_01ha','create_task','{"title":"Перезвонить"}','created',1,'2026-09-22 20:00:00'),
  ('atc_01hd','tnt_0016','acl_01hc','send_proposal','{"channel":"email"}','queued',1,'2026-08-18 07:00:00');
INSERT INTO ai_tool_calls VALUES
  ('atc_01hg','tnt_0016','acl_01hf','update_deal_stage','{"stage":"negotiation"}','updated',0,'2026-09-29 02:00:00'),
  ('atc_01hi','tnt_0016','acl_01hh','send_proposal','{"channel":"email"}','queued',1,'2026-08-22 18:00:00'),
  ('atc_01hk','tnt_0016','acl_01hj','send_proposal','{"channel":"email"}','queued',1,'2026-10-01 23:00:00'),
  ('atc_01hn','tnt_0016','acl_01hm','lookup_product','{"query":"CRM"}','found',1,'2026-09-05 07:00:00'),
  ('atc_01hq','tnt_0016','acl_01hp','check_availability','{"slot":"tomorrow 14:00"}','available',1,'2026-09-02 13:00:00'),
  ('atc_01ht','tnt_0016','acl_01hs','create_task','{"title":"Перезвонить"}','created',0,'2026-10-05 18:00:00'),
  ('atc_01hv','tnt_0016','acl_01hu','send_proposal','{"channel":"email"}','queued',0,'2026-09-16 22:00:00'),
  ('atc_01hx','tnt_0016','acl_01hw','create_task','{"title":"Перезвонить"}','created',0,'2026-08-18 22:00:00'),
  ('atc_01hz','tnt_0016','acl_01hy','create_task','{"title":"Перезвонить"}','created',0,'2026-09-03 14:00:00'),
  ('atc_01i1','tnt_0016','acl_01i0','update_deal_stage','{"stage":"negotiation"}','updated',0,'2026-09-16 15:00:00'),
  ('atc_01i3','tnt_0016','acl_01i2','send_proposal','{"channel":"email"}','queued',0,'2026-08-14 21:00:00'),
  ('atc_01i5','tnt_0016','acl_01i4','check_availability','{"slot":"tomorrow 14:00"}','available',0,'2026-09-27 21:00:00'),
  ('atc_01i7','tnt_0016','acl_01i6','create_task','{"title":"Перезвонить"}','created',1,'2026-09-11 14:00:00'),
  ('atc_01ib','tnt_0016','acl_01ia','check_availability','{"slot":"tomorrow 14:00"}','available',0,'2026-08-24 16:00:00'),
  ('atc_01if','tnt_0016','acl_01ie','create_task','{"title":"Перезвонить"}','created',1,'2026-09-19 21:00:00'),
  ('atc_01ii','tnt_0016','acl_01ih','check_availability','{"slot":"tomorrow 14:00"}','available',0,'2026-08-24 22:00:00'),
  ('atc_01ik','tnt_0016','acl_01ij','lookup_product','{"query":"CRM"}','found',1,'2026-08-19 17:00:00'),
  ('atc_01im','tnt_0016','acl_01il','send_proposal','{"channel":"email"}','queued',1,'2026-09-28 18:00:00'),
  ('atc_01io','tnt_0016','acl_01in','create_task','{"title":"Перезвонить"}','created',0,'2026-10-07 05:00:00'),
  ('atc_01ir','tnt_0016','acl_01iq','check_availability','{"slot":"tomorrow 14:00"}','available',0,'2026-08-31 09:00:00'),
  ('atc_01it','tnt_0016','acl_01is','send_proposal','{"channel":"email"}','queued',1,'2026-09-23 05:00:00'),
  ('atc_01iv','tnt_0016','acl_01iu','lookup_product','{"query":"CRM"}','found',0,'2026-09-06 04:00:00'),
  ('atc_01ix','tnt_0016','acl_01iw','update_deal_stage','{"stage":"negotiation"}','updated',0,'2026-09-14 08:00:00'),
  ('atc_01j0','tnt_0016','acl_01iz','send_proposal','{"channel":"email"}','queued',0,'2026-09-29 08:00:00'),
  ('atc_01j2','tnt_0016','acl_01j1','check_availability','{"slot":"tomorrow 14:00"}','available',0,'2026-09-04 04:00:00'),
  ('atc_01j4','tnt_0016','acl_01j3','create_task','{"title":"Перезвонить"}','created',0,'2026-08-25 16:00:00'),
  ('atc_01j6','tnt_0016','acl_01j5','create_task','{"title":"Перезвонить"}','created',0,'2026-09-11 20:00:00'),
  ('atc_01j8','tnt_0016','acl_01j7','update_deal_stage','{"stage":"negotiation"}','updated',0,'2026-08-27 07:00:00'),
  ('atc_01ja','tnt_0016','acl_01j9','update_deal_stage','{"stage":"negotiation"}','updated',1,'2026-09-25 05:00:00'),
  ('atc_01jc','tnt_0016','acl_01jb','send_proposal','{"channel":"email"}','queued',0,'2026-09-05 21:00:00'),
  ('atc_01jh','tnt_0016','acl_01jg','create_task','{"title":"Перезвонить"}','created',1,'2026-08-17 20:00:00'),
  ('atc_01jj','tnt_0016','acl_01ji','send_proposal','{"channel":"email"}','queued',0,'2026-10-11 22:00:00'),
  ('atc_01ne','tnt_0017','acl_01nd','update_deal_stage','{"stage":"negotiation"}','updated',0,'2026-09-27 05:00:00'),
  ('atc_01ng','tnt_0017','acl_01nf','create_task','{"title":"Перезвонить"}','created',1,'2026-09-29 18:00:00'),
  ('atc_01ni','tnt_0017','acl_01nh','create_task','{"title":"Перезвонить"}','created',0,'2026-09-23 23:00:00'),
  ('atc_01nk','tnt_0017','acl_01nj','create_task','{"title":"Перезвонить"}','created',0,'2026-09-30 09:00:00'),
  ('atc_01nm','tnt_0017','acl_01nl','create_task','{"title":"Перезвонить"}','created',1,'2026-10-02 23:00:00'),
  ('atc_01no','tnt_0017','acl_01nn','lookup_product','{"query":"CRM"}','found',1,'2026-09-08 03:00:00'),
  ('atc_01nr','tnt_0017','acl_01nq','create_task','{"title":"Перезвонить"}','created',0,'2026-10-07 16:00:00'),
  ('atc_01nt','tnt_0017','acl_01ns','send_proposal','{"channel":"email"}','queued',1,'2026-10-01 04:00:00'),
  ('atc_01nv','tnt_0017','acl_01nu','send_proposal','{"channel":"email"}','queued',0,'2026-09-05 15:00:00'),
  ('atc_01nx','tnt_0017','acl_01nw','lookup_product','{"query":"CRM"}','found',0,'2026-08-28 02:00:00'),
  ('atc_01nz','tnt_0017','acl_01ny','check_availability','{"slot":"tomorrow 14:00"}','available',0,'2026-10-08 22:00:00'),
  ('atc_01o2','tnt_0017','acl_01o1','update_deal_stage','{"stage":"negotiation"}','updated',0,'2026-09-22 03:00:00'),
  ('atc_01o4','tnt_0017','acl_01o3','check_availability','{"slot":"tomorrow 14:00"}','available',0,'2026-09-08 02:00:00'),
  ('atc_01o6','tnt_0017','acl_01o5','check_availability','{"slot":"tomorrow 14:00"}','available',1,'2026-09-04 00:00:00'),
  ('atc_01o8','tnt_0017','acl_01o7','send_proposal','{"channel":"email"}','queued',1,'2026-09-15 09:00:00'),
  ('atc_01oa','tnt_0017','acl_01o9','check_availability','{"slot":"tomorrow 14:00"}','available',1,'2026-08-21 00:00:00'),
  ('atc_01oc','tnt_0017','acl_01ob','update_deal_stage','{"stage":"negotiation"}','updated',1,'2026-09-22 18:00:00'),
  ('atc_01of','tnt_0017','acl_01oe','check_availability','{"slot":"tomorrow 14:00"}','available',0,'2026-09-26 14:00:00');
INSERT INTO ai_tool_calls VALUES
  ('atc_01oh','tnt_0017','acl_01og','check_availability','{"slot":"tomorrow 14:00"}','available',0,'2026-08-27 02:00:00'),
  ('atc_01oj','tnt_0017','acl_01oi','update_deal_stage','{"stage":"negotiation"}','updated',0,'2026-09-03 08:00:00'),
  ('atc_01ol','tnt_0017','acl_01ok','send_proposal','{"channel":"email"}','queued',1,'2026-08-24 19:00:00'),
  ('atc_01op','tnt_0017','acl_01oo','check_availability','{"slot":"tomorrow 14:00"}','available',1,'2026-09-18 13:00:00'),
  ('atc_01os','tnt_0017','acl_01or','check_availability','{"slot":"tomorrow 14:00"}','available',1,'2026-08-23 23:00:00'),
  ('atc_01ov','tnt_0017','acl_01ou','lookup_product','{"query":"CRM"}','found',0,'2026-10-12 02:00:00'),
  ('atc_01ox','tnt_0017','acl_01ow','send_proposal','{"channel":"email"}','queued',0,'2026-08-21 04:00:00'),
  ('atc_01p0','tnt_0017','acl_01oz','send_proposal','{"channel":"email"}','queued',1,'2026-09-29 00:00:00'),
  ('atc_01p2','tnt_0017','acl_01p1','create_task','{"title":"Перезвонить"}','created',0,'2026-09-10 09:00:00'),
  ('atc_01p4','tnt_0017','acl_01p3','check_availability','{"slot":"tomorrow 14:00"}','available',1,'2026-09-14 13:00:00'),
  ('atc_01p7','tnt_0017','acl_01p6','create_task','{"title":"Перезвонить"}','created',0,'2026-09-30 04:00:00'),
  ('atc_01p9','tnt_0017','acl_01p8','create_task','{"title":"Перезвонить"}','created',0,'2026-09-20 22:00:00'),
  ('atc_01pb','tnt_0017','acl_01pa','lookup_product','{"query":"CRM"}','found',0,'2026-09-02 16:00:00'),
  ('atc_01pd','tnt_0017','acl_01pc','update_deal_stage','{"stage":"negotiation"}','updated',1,'2026-09-15 16:00:00'),
  ('atc_01pf','tnt_0017','acl_01pe','send_proposal','{"channel":"email"}','queued',1,'2026-10-09 18:00:00'),
  ('atc_01ph','tnt_0017','acl_01pg','check_availability','{"slot":"tomorrow 14:00"}','available',0,'2026-09-22 22:00:00'),
  ('atc_01pk','tnt_0017','acl_01pj','lookup_product','{"query":"CRM"}','found',0,'2026-10-11 23:00:00'),
  ('atc_01pm','tnt_0017','acl_01pl','lookup_product','{"query":"CRM"}','found',0,'2026-08-28 04:00:00'),
  ('atc_01po','tnt_0017','acl_01pn','check_availability','{"slot":"tomorrow 14:00"}','available',0,'2026-10-03 04:00:00'),
  ('atc_01pq','tnt_0017','acl_01pp','check_availability','{"slot":"tomorrow 14:00"}','available',0,'2026-10-02 15:00:00'),
  ('atc_01ps','tnt_0017','acl_01pr','lookup_product','{"query":"CRM"}','found',1,'2026-09-11 03:00:00'),
  ('atc_01pv','tnt_0017','acl_01pu','create_task','{"title":"Перезвонить"}','created',0,'2026-09-05 15:00:00'),
  ('atc_01pz','tnt_0017','acl_01py','send_proposal','{"channel":"email"}','queued',0,'2026-09-23 03:00:00'),
  ('atc_01q1','tnt_0017','acl_01q0','lookup_product','{"query":"CRM"}','found',0,'2026-09-20 02:00:00'),
  ('atc_01q3','tnt_0017','acl_01q2','send_proposal','{"channel":"email"}','queued',0,'2026-09-25 17:00:00'),
  ('atc_01q5','tnt_0017','acl_01q4','send_proposal','{"channel":"email"}','queued',1,'2026-09-10 23:00:00'),
  ('atc_01q8','tnt_0017','acl_01q7','update_deal_stage','{"stage":"negotiation"}','updated',0,'2026-08-16 15:00:00'),
  ('atc_01qb','tnt_0017','acl_01qa','update_deal_stage','{"stage":"negotiation"}','updated',0,'2026-09-05 00:00:00'),
  ('atc_01qd','tnt_0017','acl_01qc','check_availability','{"slot":"tomorrow 14:00"}','available',0,'2026-09-04 05:00:00'),
  ('atc_01qf','tnt_0017','acl_01qe','lookup_product','{"query":"CRM"}','found',0,'2026-08-31 05:00:00'),
  ('atc_01qh','tnt_0017','acl_01qg','check_availability','{"slot":"tomorrow 14:00"}','available',0,'2026-10-03 06:00:00'),
  ('atc_01qk','tnt_0017','acl_01qj','lookup_product','{"query":"CRM"}','found',1,'2026-09-03 21:00:00'),
  ('atc_01qm','tnt_0017','acl_01ql','lookup_product','{"query":"CRM"}','found',1,'2026-09-13 00:00:00'),
  ('atc_01qp','tnt_0017','acl_01qo','lookup_product','{"query":"CRM"}','found',0,'2026-09-02 23:00:00'),
  ('atc_01qr','tnt_0017','acl_01qq','send_proposal','{"channel":"email"}','queued',0,'2026-10-03 21:00:00'),
  ('atc_01qt','tnt_0017','acl_01qs','lookup_product','{"query":"CRM"}','found',1,'2026-10-07 13:00:00'),
  ('atc_01qv','tnt_0017','acl_01qu','create_task','{"title":"Перезвонить"}','created',0,'2026-10-10 07:00:00'),
  ('atc_01qx','tnt_0017','acl_01qw','lookup_product','{"query":"CRM"}','found',0,'2026-10-12 02:00:00');

-- ai_approvals: 15 rows
INSERT INTO ai_approvals VALUES
  ('aap_019h','tnt_0016','acl_019f','aag_0175','custom_terms','Агент запросил скидку 12% при сумме заказа выше лимита.','pending',NULL,'2026-08-30 14:00:00'),
  ('aap_01a5','tnt_0016','acl_01a3','aag_0178','custom_terms','Агент запросил скидку 12% при сумме заказа выше лимита.','pending',NULL,'2026-08-28 18:00:00'),
  ('aap_01az','tnt_0016','acl_01ax','aag_0177','discount','Агент запросил скидку 12% при сумме заказа выше лимита.','pending',NULL,'2026-08-21 06:00:00'),
  ('aap_01b6','tnt_0016','acl_01b4','aag_0176','large_order','Агент запросил скидку 12% при сумме заказа выше лимита.','pending',NULL,'2026-08-18 19:00:00'),
  ('aap_01bb','tnt_0016','acl_01b9','aag_0175','discount','Агент запросил скидку 12% при сумме заказа выше лимита.','pending',NULL,'2026-08-14 03:00:00'),
  ('aap_01e7','tnt_0016','acl_01e5','aag_0177','custom_terms','Агент запросил скидку 12% при сумме заказа выше лимита.','pending',NULL,'2026-09-08 18:00:00'),
  ('aap_01fh','tnt_0016','acl_01ff','aag_0177','custom_terms','Агент запросил скидку 12% при сумме заказа выше лимита.','pending',NULL,'2026-09-18 00:00:00'),
  ('aap_01g1','tnt_0016','acl_01fz','aag_0179','custom_terms','Агент запросил скидку 12% при сумме заказа выше лимита.','pending',NULL,'2026-08-22 04:00:00'),
  ('aap_01hl','tnt_0016','acl_01hj','aag_0179','custom_terms','Агент запросил скидку 12% при сумме заказа выше лимита.','pending',NULL,'2026-10-01 23:00:00'),
  ('aap_01ho','tnt_0016','acl_01hm','aag_0177','large_order','Агент запросил скидку 12% при сумме заказа выше лимита.','pending',NULL,'2026-09-05 07:00:00'),
  ('aap_01i8','tnt_0016','acl_01i6','aag_0178','custom_terms','Агент запросил скидку 12% при сумме заказа выше лимита.','pending',NULL,'2026-09-11 14:00:00'),
  ('aap_01ig','tnt_0016','acl_01ie','aag_0178','large_order','Агент запросил скидку 12% при сумме заказа выше лимита.','pending',NULL,'2026-09-19 21:00:00'),
  ('aap_01oq','tnt_0017','acl_01oo','aag_017b','discount','Агент запросил скидку 12% при сумме заказа выше лимита.','pending',NULL,'2026-09-18 13:00:00'),
  ('aap_01q6','tnt_0017','acl_01q4','aag_017b','custom_terms','Агент запросил скидку 12% при сумме заказа выше лимита.','pending',NULL,'2026-09-10 23:00:00'),
  ('aap_01qn','tnt_0017','acl_01ql','aag_017c','custom_terms','Агент запросил скидку 12% при сумме заказа выше лимита.','pending',NULL,'2026-09-13 00:00:00');

-- ai_evaluations: 52 rows
INSERT INTO ai_evaluations VALUES
  ('aev_01jl','tnt_0016','aag_0176','outcome_accuracy',0.72,115,'2026-09','2026-09-13 09:00:00'),
  ('aev_01jm','tnt_0016','aag_0178','disclosure_compliance',0.69,122,'2026-09','2026-09-24 09:00:00'),
  ('aev_01jn','tnt_0016','aag_0178','disclosure_compliance',0.71,72,'2026-09','2026-09-13 09:00:00'),
  ('aev_01jo','tnt_0016','aag_0176','script_adherence',0.78,103,'2026-10','2026-09-18 09:00:00'),
  ('aev_01jp','tnt_0016','aag_0178','outcome_accuracy',0.93,127,'2026-09','2026-09-14 09:00:00'),
  ('aev_01jq','tnt_0016','aag_0177','objection_handling',0.76,102,'2026-09','2026-09-28 09:00:00'),
  ('aev_01jr','tnt_0016','aag_0178','objection_handling',0.92,44,'2026-09','2026-09-22 09:00:00'),
  ('aev_01js','tnt_0016','aag_0178','outcome_accuracy',0.69,194,'2026-10','2026-10-10 09:00:00'),
  ('aev_01jt','tnt_0016','aag_0175','script_adherence',0.69,107,'2026-09','2026-09-19 09:00:00'),
  ('aev_01ju','tnt_0016','aag_0179','disclosure_compliance',0.76,188,'2026-09','2026-10-07 09:00:00'),
  ('aev_01jv','tnt_0016','aag_0177','disclosure_compliance',0.91,45,'2026-09','2026-09-28 09:00:00'),
  ('aev_01jw','tnt_0016','aag_0176','objection_handling',0.82,168,'2026-10','2026-10-07 09:00:00'),
  ('aev_01jx','tnt_0016','aag_0176','script_adherence',0.85,46,'2026-10','2026-09-15 09:00:00'),
  ('aev_01jy','tnt_0016','aag_0179','script_adherence',0.64,161,'2026-10','2026-10-09 09:00:00'),
  ('aev_01jz','tnt_0016','aag_0177','outcome_accuracy',0.84,195,'2026-09','2026-10-01 09:00:00'),
  ('aev_01k0','tnt_0016','aag_0175','script_adherence',0.78,179,'2026-09','2026-10-03 09:00:00'),
  ('aev_01k1','tnt_0016','aag_0176','objection_handling',0.74,123,'2026-09','2026-09-26 09:00:00'),
  ('aev_01k2','tnt_0016','aag_0179','script_adherence',0.74,43,'2026-09','2026-09-19 09:00:00'),
  ('aev_01k3','tnt_0016','aag_0178','disclosure_compliance',0.78,90,'2026-09','2026-09-19 09:00:00'),
  ('aev_01k4','tnt_0016','aag_0177','disclosure_compliance',0.69,39,'2026-09','2026-09-16 09:00:00'),
  ('aev_01k5','tnt_0016','aag_0177','script_adherence',0.74,168,'2026-09','2026-10-06 09:00:00'),
  ('aev_01k6','tnt_0016','aag_0179','outcome_accuracy',0.66,146,'2026-09','2026-10-04 09:00:00'),
  ('aev_01k7','tnt_0016','aag_0176','outcome_accuracy',0.73,85,'2026-09','2026-09-21 09:00:00'),
  ('aev_01k8','tnt_0016','aag_0178','objection_handling',0.68,133,'2026-10','2026-10-04 09:00:00'),
  ('aev_01k9','tnt_0016','aag_0175','objection_handling',0.77,143,'2026-09','2026-10-04 09:00:00'),
  ('aev_01ka','tnt_0016','aag_0178','disclosure_compliance',0.84,34,'2026-09','2026-09-27 09:00:00'),
  ('aev_01kb','tnt_0016','aag_0177','script_adherence',0.68,156,'2026-10','2026-10-09 09:00:00'),
  ('aev_01kc','tnt_0016','aag_0175','disclosure_compliance',0.95,120,'2026-09','2026-09-17 09:00:00'),
  ('aev_01kd','tnt_0016','aag_0179','script_adherence',0.67,180,'2026-09','2026-09-15 09:00:00'),
  ('aev_01ke','tnt_0016','aag_0175','outcome_accuracy',0.92,82,'2026-09','2026-10-11 09:00:00'),
  ('aev_01kf','tnt_0016','aag_0178','script_adherence',0.76,177,'2026-09','2026-09-18 09:00:00'),
  ('aev_01kg','tnt_0016','aag_0175','script_adherence',0.79,170,'2026-09','2026-09-26 09:00:00'),
  ('aev_01kh','tnt_0016','aag_0178','objection_handling',0.78,68,'2026-10','2026-09-28 09:00:00'),
  ('aev_01ki','tnt_0016','aag_0176','disclosure_compliance',0.93,40,'2026-09','2026-09-24 09:00:00'),
  ('aev_01kj','tnt_0016','aag_0175','outcome_accuracy',0.95,194,'2026-09','2026-09-20 09:00:00'),
  ('aev_01kk','tnt_0016','aag_0175','disclosure_compliance',0.91,132,'2026-09','2026-09-18 09:00:00'),
  ('aev_01kl','tnt_0016','aag_0175','script_adherence',0.95,152,'2026-09','2026-09-15 09:00:00'),
  ('aev_01km','tnt_0016','aag_0175','objection_handling',0.87,149,'2026-09','2026-09-28 09:00:00'),
  ('aev_01kn','tnt_0016','aag_0175','script_adherence',0.93,140,'2026-09','2026-09-22 09:00:00'),
  ('aev_01ko','tnt_0016','aag_0176','objection_handling',0.73,169,'2026-09','2026-10-02 09:00:00'),
  ('aev_01qy','tnt_0017','aag_017b','script_adherence',0.74,32,'2026-09','2026-10-07 09:00:00'),
  ('aev_01qz','tnt_0017','aag_017a','objection_handling',0.68,113,'2026-09','2026-10-10 09:00:00'),
  ('aev_01r0','tnt_0017','aag_017c','disclosure_compliance',0.67,190,'2026-09','2026-09-17 09:00:00'),
  ('aev_01r1','tnt_0017','aag_017c','disclosure_compliance',0.91,65,'2026-09','2026-09-20 09:00:00'),
  ('aev_01r2','tnt_0017','aag_017c','outcome_accuracy',0.85,20,'2026-09','2026-09-27 09:00:00'),
  ('aev_01r3','tnt_0017','aag_017b','script_adherence',0.83,126,'2026-09','2026-09-28 09:00:00'),
  ('aev_01r4','tnt_0017','aag_017b','objection_handling',0.65,108,'2026-09','2026-10-11 09:00:00'),
  ('aev_01r5','tnt_0017','aag_017b','objection_handling',0.72,25,'2026-10','2026-10-05 09:00:00'),
  ('aev_01r6','tnt_0017','aag_017b','objection_handling',0.91,61,'2026-09','2026-09-30 09:00:00'),
  ('aev_01r7','tnt_0017','aag_017a','script_adherence',0.84,137,'2026-09','2026-09-27 09:00:00');
INSERT INTO ai_evaluations VALUES
  ('aev_01r8','tnt_0017','aag_017c','objection_handling',0.78,143,'2026-10','2026-10-05 09:00:00'),
  ('aev_01r9','tnt_0017','aag_017b','objection_handling',0.7,69,'2026-10','2026-09-20 09:00:00');

-- consents: 108 rows
INSERT INTO consents VALUES
  ('cns_01kp','tnt_0016','con_00av','calls',1,'Зафиксировано при отправке веб-формы','2026-06-22 09:00:00'),
  ('cns_01kq','tnt_0016','con_00aw','calls',1,'Зафиксировано при отправке веб-формы','2026-08-04 09:00:00'),
  ('cns_01kr','tnt_0016','con_00ay','calls',1,'Зафиксировано при отправке веб-формы','2026-09-06 09:00:00'),
  ('cns_01ks','tnt_0016','con_00az','calls',0,'Зафиксировано при отправке веб-формы','2026-09-09 09:00:00'),
  ('cns_01kt','tnt_0016','con_00b0','calls',0,'Зафиксировано при отправке веб-формы','2026-08-08 09:00:00'),
  ('cns_01ku','tnt_0016','con_00b2','calls',1,'Зафиксировано при отправке веб-формы','2026-07-27 09:00:00'),
  ('cns_01kv','tnt_0016','con_00b3','calls',1,'Зафиксировано при отправке веб-формы','2026-10-06 09:00:00'),
  ('cns_01kw','tnt_0016','con_00b4','calls',1,'Зафиксировано при отправке веб-формы','2026-07-13 09:00:00'),
  ('cns_01kx','tnt_0016','con_00b5','calls',0,'Зафиксировано при отправке веб-формы','2026-06-25 09:00:00'),
  ('cns_01ky','tnt_0016','con_00b7','calls',1,'Зафиксировано при отправке веб-формы','2026-06-24 09:00:00'),
  ('cns_01kz','tnt_0016','con_00b8','calls',1,'Зафиксировано при отправке веб-формы','2026-06-22 09:00:00'),
  ('cns_01l0','tnt_0016','con_00ba','calls',1,'Зафиксировано при отправке веб-формы','2026-07-31 09:00:00'),
  ('cns_01l1','tnt_0016','con_00bb','calls',1,'Зафиксировано при отправке веб-формы','2026-06-25 09:00:00'),
  ('cns_01l2','tnt_0016','con_00bc','calls',1,'Зафиксировано при отправке веб-формы','2026-06-24 09:00:00'),
  ('cns_01l3','tnt_0016','con_00bd','calls',0,'Зафиксировано при отправке веб-формы','2026-08-28 09:00:00'),
  ('cns_01l4','tnt_0016','con_00bf','calls',1,'Зафиксировано при отправке веб-формы','2026-09-11 09:00:00'),
  ('cns_01l5','tnt_0016','con_00bg','calls',1,'Зафиксировано при отправке веб-формы','2026-08-26 09:00:00'),
  ('cns_01l6','tnt_0016','con_00bi','calls',1,'Зафиксировано при отправке веб-формы','2026-07-21 09:00:00'),
  ('cns_01l7','tnt_0016','con_00bj','calls',1,'Зафиксировано при отправке веб-формы','2026-10-02 09:00:00'),
  ('cns_01l8','tnt_0016','con_00bk','calls',1,'Зафиксировано при отправке веб-формы','2026-08-13 09:00:00'),
  ('cns_01l9','tnt_0016','con_00bl','calls',1,'Зафиксировано при отправке веб-формы','2026-07-03 09:00:00'),
  ('cns_01la','tnt_0016','con_00bn','calls',1,'Зафиксировано при отправке веб-формы','2026-10-04 09:00:00'),
  ('cns_01lb','tnt_0016','con_00bo','calls',1,'Зафиксировано при отправке веб-формы','2026-09-08 09:00:00'),
  ('cns_01lc','tnt_0016','con_00bq','calls',1,'Зафиксировано при отправке веб-формы','2026-10-07 09:00:00'),
  ('cns_01ld','tnt_0016','con_00br','calls',0,'Зафиксировано при отправке веб-формы','2026-07-10 09:00:00'),
  ('cns_01le','tnt_0016','con_00bs','calls',1,'Зафиксировано при отправке веб-формы','2026-06-25 09:00:00'),
  ('cns_01lf','tnt_0016','con_00bt','calls',1,'Зафиксировано при отправке веб-формы','2026-08-08 09:00:00'),
  ('cns_01lg','tnt_0016','con_00bv','calls',1,'Зафиксировано при отправке веб-формы','2026-09-22 09:00:00'),
  ('cns_01lh','tnt_0016','con_00bw','calls',1,'Зафиксировано при отправке веб-формы','2026-06-18 09:00:00'),
  ('cns_01li','tnt_0016','con_00by','calls',1,'Зафиксировано при отправке веб-формы','2026-07-01 09:00:00'),
  ('cns_01lj','tnt_0016','con_00bz','calls',1,'Зафиксировано при отправке веб-формы','2026-07-18 09:00:00'),
  ('cns_01lk','tnt_0016','con_00c1','calls',1,'Зафиксировано при отправке веб-формы','2026-08-05 09:00:00'),
  ('cns_01ll','tnt_0016','con_00c2','calls',0,'Зафиксировано при отправке веб-формы','2026-09-26 09:00:00'),
  ('cns_01lm','tnt_0016','con_00c4','calls',1,'Зафиксировано при отправке веб-формы','2026-10-04 09:00:00'),
  ('cns_01ln','tnt_0016','con_00c5','calls',1,'Зафиксировано при отправке веб-формы','2026-08-21 09:00:00'),
  ('cns_01lo','tnt_0016','con_00c7','calls',1,'Зафиксировано при отправке веб-формы','2026-07-11 09:00:00'),
  ('cns_01lp','tnt_0016','con_00c8','calls',1,'Зафиксировано при отправке веб-формы','2026-09-07 09:00:00'),
  ('cns_01lq','tnt_0016','con_00c9','calls',1,'Зафиксировано при отправке веб-формы','2026-08-05 09:00:00'),
  ('cns_01lr','tnt_0016','con_00ca','calls',0,'Зафиксировано при отправке веб-формы','2026-07-27 09:00:00'),
  ('cns_01ls','tnt_0016','con_00cc','calls',1,'Зафиксировано при отправке веб-формы','2026-08-04 09:00:00'),
  ('cns_01lt','tnt_0016','con_00cd','calls',1,'Зафиксировано при отправке веб-формы','2026-07-10 09:00:00'),
  ('cns_01lu','tnt_0016','con_00cf','calls',1,'Зафиксировано при отправке веб-формы','2026-07-25 09:00:00'),
  ('cns_01lv','tnt_0016','con_00cg','calls',0,'Зафиксировано при отправке веб-формы','2026-08-04 09:00:00'),
  ('cns_01lw','tnt_0016','con_00ch','calls',0,'Зафиксировано при отправке веб-формы','2026-08-26 09:00:00'),
  ('cns_01lx','tnt_0016','con_00cj','calls',1,'Зафиксировано при отправке веб-формы','2026-07-25 09:00:00'),
  ('cns_01ly','tnt_0016','con_00ck','calls',1,'Зафиксировано при отправке веб-формы','2026-08-07 09:00:00'),
  ('cns_01lz','tnt_0016','con_00cl','calls',1,'Зафиксировано при отправке веб-формы','2026-09-12 09:00:00'),
  ('cns_01m0','tnt_0016','con_00cm','calls',1,'Зафиксировано при отправке веб-формы','2026-07-30 09:00:00'),
  ('cns_01m1','tnt_0016','con_00co','calls',1,'Зафиксировано при отправке веб-формы','2026-08-31 09:00:00'),
  ('cns_01m2','tnt_0016','con_00cp','calls',1,'Зафиксировано при отправке веб-формы','2026-08-08 09:00:00');
INSERT INTO consents VALUES
  ('cns_01m3','tnt_0016','con_00cr','calls',0,'Зафиксировано при отправке веб-формы','2026-06-22 09:00:00'),
  ('cns_01m4','tnt_0016','con_00cs','calls',0,'Зафиксировано при отправке веб-формы','2026-07-11 09:00:00'),
  ('cns_01m5','tnt_0016','con_00ct','calls',1,'Зафиксировано при отправке веб-формы','2026-09-07 09:00:00'),
  ('cns_01m6','tnt_0016','con_00cv','calls',1,'Зафиксировано при отправке веб-формы','2026-08-22 09:00:00'),
  ('cns_01m7','tnt_0016','con_00cw','calls',1,'Зафиксировано при отправке веб-формы','2026-07-29 09:00:00'),
  ('cns_01m8','tnt_0016','con_00cx','calls',1,'Зафиксировано при отправке веб-формы','2026-09-21 09:00:00'),
  ('cns_01m9','tnt_0016','con_00cz','calls',1,'Зафиксировано при отправке веб-формы','2026-07-21 09:00:00'),
  ('cns_01ma','tnt_0016','con_00d0','calls',1,'Зафиксировано при отправке веб-формы','2026-08-06 09:00:00'),
  ('cns_01mb','tnt_0016','con_00d1','calls',1,'Зафиксировано при отправке веб-формы','2026-07-22 09:00:00'),
  ('cns_01mc','tnt_0016','con_00d2','calls',1,'Зафиксировано при отправке веб-формы','2026-08-11 09:00:00'),
  ('cns_01md','tnt_0016','con_00d4','calls',1,'Зафиксировано при отправке веб-формы','2026-09-09 09:00:00'),
  ('cns_01me','tnt_0016','con_00d5','calls',1,'Зафиксировано при отправке веб-формы','2026-09-08 09:00:00'),
  ('cns_01mf','tnt_0016','con_00d6','calls',0,'Зафиксировано при отправке веб-формы','2026-09-26 09:00:00'),
  ('cns_01mg','tnt_0016','con_00d8','calls',1,'Зафиксировано при отправке веб-формы','2026-08-03 09:00:00'),
  ('cns_01mh','tnt_0016','con_00d9','calls',1,'Зафиксировано при отправке веб-формы','2026-08-01 09:00:00'),
  ('cns_01mi','tnt_0016','con_00da','calls',1,'Зафиксировано при отправке веб-формы','2026-09-11 09:00:00'),
  ('cns_01mj','tnt_0016','con_00db','calls',0,'Зафиксировано при отправке веб-формы','2026-07-17 09:00:00'),
  ('cns_01mk','tnt_0016','con_00dd','calls',1,'Зафиксировано при отправке веб-формы','2026-07-24 09:00:00'),
  ('cns_01ml','tnt_0016','con_00de','calls',1,'Зафиксировано при отправке веб-формы','2026-09-12 09:00:00'),
  ('cns_01mm','tnt_0016','con_00df','calls',1,'Зафиксировано при отправке веб-формы','2026-07-03 09:00:00'),
  ('cns_01mn','tnt_0016','con_00dg','calls',1,'Зафиксировано при отправке веб-формы','2026-10-06 09:00:00'),
  ('cns_01mo','tnt_0016','con_00di','calls',1,'Зафиксировано при отправке веб-формы','2026-07-24 09:00:00'),
  ('cns_01mp','tnt_0016','con_00dj','calls',1,'Зафиксировано при отправке веб-формы','2026-07-03 09:00:00'),
  ('cns_01mq','tnt_0016','con_00dl','calls',0,'Зафиксировано при отправке веб-формы','2026-07-13 09:00:00'),
  ('cns_01mr','tnt_0016','con_00dm','calls',1,'Зафиксировано при отправке веб-формы','2026-06-15 09:00:00'),
  ('cns_01ms','tnt_0016','con_00dn','calls',1,'Зафиксировано при отправке веб-формы','2026-08-10 09:00:00'),
  ('cns_01mt','tnt_0016','con_00do','calls',1,'Зафиксировано при отправке веб-формы','2026-07-06 09:00:00'),
  ('cns_01mu','tnt_0016','con_00dq','calls',1,'Зафиксировано при отправке веб-формы','2026-08-23 09:00:00'),
  ('cns_01mv','tnt_0016','con_00dr','calls',1,'Зафиксировано при отправке веб-формы','2026-06-16 09:00:00'),
  ('cns_01mw','tnt_0016','con_00ds','calls',1,'Зафиксировано при отправке веб-формы','2026-08-25 09:00:00'),
  ('cns_01mx','tnt_0016','con_00dt','calls',1,'Зафиксировано при отправке веб-формы','2026-08-04 09:00:00'),
  ('cns_01my','tnt_0016','con_00dv','calls',0,'Зафиксировано при отправке веб-формы','2026-06-17 09:00:00'),
  ('cns_01mz','tnt_0016','con_00dw','calls',1,'Зафиксировано при отправке веб-формы','2026-08-26 09:00:00'),
  ('cns_01n0','tnt_0016','con_00dy','calls',1,'Зафиксировано при отправке веб-формы','2026-10-03 09:00:00'),
  ('cns_01n1','tnt_0016','con_00dz','calls',1,'Зафиксировано при отправке веб-формы','2026-10-06 09:00:00'),
  ('cns_01n2','tnt_0016','con_00e0','calls',1,'Зафиксировано при отправке веб-формы','2026-09-28 09:00:00'),
  ('cns_01n3','tnt_0016','con_00e1','calls',1,'Зафиксировано при отправке веб-формы','2026-09-12 09:00:00'),
  ('cns_01n4','tnt_0016','con_00e3','calls',1,'Зафиксировано при отправке веб-формы','2026-07-02 09:00:00'),
  ('cns_01n5','tnt_0016','con_00e4','calls',1,'Зафиксировано при отправке веб-формы','2026-06-21 09:00:00'),
  ('cns_01n6','tnt_0016','con_00e5','calls',1,'Зафиксировано при отправке веб-формы','2026-09-19 09:00:00'),
  ('cns_01ra','tnt_0017','con_00eo','calls',1,'Зафиксировано при отправке веб-формы','2026-07-17 09:00:00'),
  ('cns_01rb','tnt_0017','con_00ep','calls',1,'Зафиксировано при отправке веб-формы','2026-10-01 09:00:00'),
  ('cns_01rc','tnt_0017','con_00er','calls',1,'Зафиксировано при отправке веб-формы','2026-08-20 09:00:00'),
  ('cns_01rd','tnt_0017','con_00es','calls',0,'Зафиксировано при отправке веб-формы','2026-08-05 09:00:00'),
  ('cns_01re','tnt_0017','con_00eu','calls',1,'Зафиксировано при отправке веб-формы','2026-07-01 09:00:00'),
  ('cns_01rf','tnt_0017','con_00ew','calls',1,'Зафиксировано при отправке веб-формы','2026-06-16 09:00:00'),
  ('cns_01rg','tnt_0017','con_00ex','calls',0,'Зафиксировано при отправке веб-формы','2026-08-18 09:00:00'),
  ('cns_01rh','tnt_0017','con_00ez','calls',1,'Зафиксировано при отправке веб-формы','2026-09-17 09:00:00'),
  ('cns_01ri','tnt_0017','con_00f1','calls',1,'Зафиксировано при отправке веб-формы','2026-06-14 09:00:00'),
  ('cns_01rj','tnt_0017','con_00f3','calls',1,'Зафиксировано при отправке веб-формы','2026-08-11 09:00:00');
INSERT INTO consents VALUES
  ('cns_01rk','tnt_0017','con_00f4','calls',0,'Зафиксировано при отправке веб-формы','2026-07-21 09:00:00'),
  ('cns_01rl','tnt_0017','con_00f6','calls',1,'Зафиксировано при отправке веб-формы','2026-07-11 09:00:00'),
  ('cns_01rm','tnt_0017','con_00f8','calls',0,'Зафиксировано при отправке веб-формы','2026-08-27 09:00:00'),
  ('cns_01rn','tnt_0017','con_00fa','calls',1,'Зафиксировано при отправке веб-формы','2026-06-25 09:00:00'),
  ('cns_01ro','tnt_0017','con_00fb','calls',1,'Зафиксировано при отправке веб-формы','2026-06-27 09:00:00'),
  ('cns_01rp','tnt_0017','con_00fd','calls',1,'Зафиксировано при отправке веб-формы','2026-07-19 09:00:00'),
  ('cns_01rq','tnt_0017','con_00fe','calls',1,'Зафиксировано при отправке веб-формы','2026-09-06 09:00:00'),
  ('cns_01rr','tnt_0017','con_00fg','calls',1,'Зафиксировано при отправке веб-формы','2026-06-27 09:00:00');

-- suppression_list: 12 rows
INSERT INTO suppression_list VALUES
  ('sup_01n7','tnt_0016','+998 76 480 54 81',NULL,'opted_out','2026-09-16 09:00:00'),
  ('sup_01n8','tnt_0016','+998 99 958 28 37',NULL,'opted_out','2026-08-16 09:00:00'),
  ('sup_01n9','tnt_0016','+998 85 628 11 30',NULL,'do_not_call','2026-10-11 09:00:00'),
  ('sup_01na','tnt_0016','+998 70 251 97 14',NULL,'complaint','2026-08-28 09:00:00'),
  ('sup_01nb','tnt_0016','+998 77 324 51 84',NULL,'complaint','2026-09-24 09:00:00'),
  ('sup_01nc','tnt_0016','+998 92 972 47 34',NULL,'complaint','2026-09-22 09:00:00'),
  ('sup_01rs','tnt_0017','+998 90 110 99 52',NULL,'opted_out','2026-09-02 09:00:00'),
  ('sup_01rt','tnt_0017','+998 86 515 50 12',NULL,'do_not_call','2026-08-13 09:00:00'),
  ('sup_01ru','tnt_0017','+998 75 622 83 75',NULL,'complaint','2026-08-15 09:00:00'),
  ('sup_01rv','tnt_0017','+998 98 899 80 22',NULL,'complaint','2026-10-05 09:00:00'),
  ('sup_01rw','tnt_0017','+998 93 708 68 80',NULL,'opted_out','2026-08-31 09:00:00'),
  ('sup_01rx','tnt_0017','+998 70 634 10 37',NULL,'complaint','2026-08-17 09:00:00');

-- feature_flags: 48 rows
INSERT INTO feature_flags VALUES
  ('ff_01ry','tnt_0016','ai_agents',1,'2026-09-12 09:00:00'),
  ('ff_01rz','tnt_0016','voice_minutes',0,'2026-09-12 09:00:00'),
  ('ff_01s0','tnt_0016','custom_domain',0,'2026-09-12 09:00:00'),
  ('ff_01s1','tnt_0016','automations',1,'2026-09-12 09:00:00'),
  ('ff_01s2','tnt_0017','ai_agents',1,'2026-09-12 09:00:00'),
  ('ff_01s3','tnt_0017','voice_minutes',1,'2026-09-12 09:00:00'),
  ('ff_01s4','tnt_0017','custom_domain',1,'2026-09-12 09:00:00'),
  ('ff_01s5','tnt_0017','automations',1,'2026-09-12 09:00:00'),
  ('ff_01s6','tnt_0018','ai_agents',1,'2026-09-12 09:00:00'),
  ('ff_01s7','tnt_0018','voice_minutes',1,'2026-09-12 09:00:00'),
  ('ff_01s8','tnt_0018','custom_domain',1,'2026-09-12 09:00:00'),
  ('ff_01s9','tnt_0018','automations',1,'2026-09-12 09:00:00'),
  ('ff_01sa','tnt_0019','ai_agents',1,'2026-09-12 09:00:00'),
  ('ff_01sb','tnt_0019','voice_minutes',0,'2026-09-12 09:00:00'),
  ('ff_01sc','tnt_0019','custom_domain',0,'2026-09-12 09:00:00'),
  ('ff_01sd','tnt_0019','automations',1,'2026-09-12 09:00:00'),
  ('ff_01se','tnt_001a','ai_agents',0,'2026-09-12 09:00:00'),
  ('ff_01sf','tnt_001a','voice_minutes',0,'2026-09-12 09:00:00'),
  ('ff_01sg','tnt_001a','custom_domain',0,'2026-09-12 09:00:00'),
  ('ff_01sh','tnt_001a','automations',0,'2026-09-12 09:00:00'),
  ('ff_01si','tnt_001b','ai_agents',0,'2026-09-12 09:00:00'),
  ('ff_01sj','tnt_001b','voice_minutes',0,'2026-09-12 09:00:00'),
  ('ff_01sk','tnt_001b','custom_domain',0,'2026-09-12 09:00:00'),
  ('ff_01sl','tnt_001b','automations',0,'2026-09-12 09:00:00'),
  ('ff_01sm','tnt_001c','ai_agents',1,'2026-09-12 09:00:00'),
  ('ff_01sn','tnt_001c','voice_minutes',0,'2026-09-12 09:00:00'),
  ('ff_01so','tnt_001c','custom_domain',0,'2026-09-12 09:00:00'),
  ('ff_01sp','tnt_001c','automations',1,'2026-09-12 09:00:00'),
  ('ff_01sq','tnt_001d','ai_agents',1,'2026-09-12 09:00:00'),
  ('ff_01sr','tnt_001d','voice_minutes',1,'2026-09-12 09:00:00'),
  ('ff_01ss','tnt_001d','custom_domain',1,'2026-09-12 09:00:00'),
  ('ff_01st','tnt_001d','automations',1,'2026-09-12 09:00:00'),
  ('ff_01su','tnt_001e','ai_agents',1,'2026-09-12 09:00:00'),
  ('ff_01sv','tnt_001e','voice_minutes',0,'2026-09-12 09:00:00'),
  ('ff_01sw','tnt_001e','custom_domain',0,'2026-09-12 09:00:00'),
  ('ff_01sx','tnt_001e','automations',1,'2026-09-12 09:00:00'),
  ('ff_01sy','tnt_001f','ai_agents',0,'2026-09-12 09:00:00'),
  ('ff_01sz','tnt_001f','voice_minutes',0,'2026-09-12 09:00:00'),
  ('ff_01t0','tnt_001f','custom_domain',0,'2026-09-12 09:00:00'),
  ('ff_01t1','tnt_001f','automations',0,'2026-09-12 09:00:00'),
  ('ff_01t2','tnt_001g','ai_agents',1,'2026-09-12 09:00:00'),
  ('ff_01t3','tnt_001g','voice_minutes',0,'2026-09-12 09:00:00'),
  ('ff_01t4','tnt_001g','custom_domain',0,'2026-09-12 09:00:00'),
  ('ff_01t5','tnt_001g','automations',1,'2026-09-12 09:00:00'),
  ('ff_01t6','tnt_001h','ai_agents',1,'2026-09-12 09:00:00'),
  ('ff_01t7','tnt_001h','voice_minutes',1,'2026-09-12 09:00:00'),
  ('ff_01t8','tnt_001h','custom_domain',1,'2026-09-12 09:00:00'),
  ('ff_01t9','tnt_001h','automations',1,'2026-09-12 09:00:00');

-- audit_logs: 60 rows
INSERT INTO audit_logs VALUES
  ('aud_01ta','tnt_0016','usr_000x','Bekzod Rahimov','ai.campaign.started','ai',NULL,'{"via":"internal UI"}','10.0.100.65','2026-09-16 05:00:00'),
  ('aud_01tb','tnt_0016','usr_000w','Nilufar Ahmedova','deal.stage_changed','deal',NULL,'{"via":"internal UI"}','10.0.171.160','2026-09-30 02:00:00'),
  ('aud_01tc','tnt_0016','usr_000t','Rustam Nazarov','order.confirmed','order',NULL,'{"via":"internal UI"}','10.0.16.200','2026-09-16 14:00:00'),
  ('aud_01td','tnt_0016','usr_000r','Jasher Deen','deal.stage_changed','deal',NULL,'{"via":"internal UI"}','10.0.221.228','2026-09-18 08:00:00'),
  ('aud_01te','tnt_0016','usr_000v','Sardor Toshmatov','ai.campaign.started','ai',NULL,'{"via":"internal UI"}','10.0.225.137','2026-09-20 10:00:00'),
  ('aud_01tf','tnt_0016','usr_000x','Bekzod Rahimov','user.invited','user',NULL,'{"via":"internal UI"}','10.0.13.224','2026-09-25 17:00:00'),
  ('aud_01tg','tnt_0016','usr_0013','Demo User','login.success','login',NULL,'{"via":"internal UI"}','10.0.226.173','2026-09-18 17:00:00'),
  ('aud_01th','tnt_0016','usr_000s','Dilnoza Yusupova','settings.updated','settings',NULL,'{"via":"internal UI"}','10.0.50.109','2026-10-02 13:00:00'),
  ('aud_01ti','tnt_0016','usr_000v','Sardor Toshmatov','login.success','login',NULL,'{"via":"internal UI"}','10.0.17.125','2026-09-17 13:00:00'),
  ('aud_01tj','tnt_0016','usr_000v','Sardor Toshmatov','invoice.paid','invoice',NULL,'{"via":"internal UI"}','10.0.230.182','2026-10-07 09:00:00'),
  ('aud_01tk','tnt_0016','usr_000u','Kamila Ergasheva','login.success','login',NULL,'{"via":"internal UI"}','10.0.221.234','2026-10-08 11:00:00'),
  ('aud_01tl','tnt_0016','usr_000x','Bekzod Rahimov','login.success','login',NULL,'{"via":"internal UI"}','10.0.166.215','2026-09-25 11:00:00'),
  ('aud_01tm','tnt_0016','usr_000s','Dilnoza Yusupova','export.csv','export',NULL,'{"via":"internal UI"}','10.0.213.194','2026-09-13 00:00:00'),
  ('aud_01tn','tnt_0016','usr_000v','Sardor Toshmatov','role.changed','role',NULL,'{"via":"internal UI"}','10.0.169.132','2026-09-30 14:00:00'),
  ('aud_01to','tnt_0016','usr_000s','Dilnoza Yusupova','deal.stage_changed','deal',NULL,'{"via":"internal UI"}','10.0.197.52','2026-09-12 17:00:00'),
  ('aud_01tp','tnt_0016','usr_000r','Jasher Deen','export.csv','export',NULL,'{"via":"internal UI"}','10.0.186.200','2026-09-22 16:00:00'),
  ('aud_01tq','tnt_0016','usr_000r','Jasher Deen','user.invited','user',NULL,'{"via":"internal UI"}','10.0.90.54','2026-10-04 01:00:00'),
  ('aud_01tr','tnt_0016','usr_000w','Nilufar Ahmedova','role.changed','role',NULL,'{"via":"internal UI"}','10.0.94.55','2026-09-30 12:00:00'),
  ('aud_01ts','tnt_0016','usr_000s','Dilnoza Yusupova','ai.campaign.started','ai',NULL,'{"via":"internal UI"}','10.0.243.205','2026-10-04 18:00:00'),
  ('aud_01tt','tnt_0016','usr_000u','Kamila Ergasheva','order.confirmed','order',NULL,'{"via":"internal UI"}','10.0.68.197','2026-09-21 20:00:00'),
  ('aud_01tu','tnt_0016','usr_000t','Rustam Nazarov','order.confirmed','order',NULL,'{"via":"internal UI"}','10.0.158.213','2026-09-13 21:00:00'),
  ('aud_01tv','tnt_0016','usr_0013','Demo User','settings.updated','settings',NULL,'{"via":"internal UI"}','10.0.76.126','2026-09-21 17:00:00'),
  ('aud_01tw','tnt_0016','usr_000s','Dilnoza Yusupova','ai.campaign.started','ai',NULL,'{"via":"internal UI"}','10.0.247.233','2026-09-22 01:00:00'),
  ('aud_01tx','tnt_0016','usr_000r','Jasher Deen','login.success','login',NULL,'{"via":"internal UI"}','10.0.206.25','2026-10-03 08:00:00'),
  ('aud_01ty','tnt_0016','usr_0013','Demo User','export.csv','export',NULL,'{"via":"internal UI"}','10.0.8.147','2026-09-26 13:00:00'),
  ('aud_01tz','tnt_0016','usr_000x','Bekzod Rahimov','user.invited','user',NULL,'{"via":"internal UI"}','10.0.11.136','2026-09-24 21:00:00'),
  ('aud_01u0','tnt_0016','usr_000t','Rustam Nazarov','login.success','login',NULL,'{"via":"internal UI"}','10.0.31.118','2026-09-25 22:00:00'),
  ('aud_01u1','tnt_0016','usr_000s','Dilnoza Yusupova','ai.campaign.stopped','ai',NULL,'{"via":"internal UI"}','10.0.170.217','2026-09-18 12:00:00'),
  ('aud_01u2','tnt_0016','usr_000t','Rustam Nazarov','settings.updated','settings',NULL,'{"via":"internal UI"}','10.0.137.244','2026-09-30 22:00:00'),
  ('aud_01u3','tnt_0016','usr_000r','Jasher Deen','ai.campaign.started','ai',NULL,'{"via":"internal UI"}','10.0.215.148','2026-09-25 03:00:00'),
  ('aud_01u4','tnt_0017','usr_000r','Jasher Deen','ai.campaign.stopped','ai',NULL,'{"via":"internal UI"}','10.0.29.165','2026-09-12 06:00:00'),
  ('aud_01u5','tnt_0017','usr_000y','Zilola Karimova','invoice.paid','invoice',NULL,'{"via":"internal UI"}','10.0.187.210','2026-10-07 12:00:00'),
  ('aud_01u6','tnt_0017','usr_000y','Zilola Karimova','settings.updated','settings',NULL,'{"via":"internal UI"}','10.0.231.130','2026-09-17 05:00:00'),
  ('aud_01u7','tnt_0017','usr_000u','Kamila Ergasheva','login.success','login',NULL,'{"via":"internal UI"}','10.0.141.70','2026-10-06 05:00:00'),
  ('aud_01u8','tnt_0017','usr_000r','Jasher Deen','user.invited','user',NULL,'{"via":"internal UI"}','10.0.52.251','2026-10-05 08:00:00'),
  ('aud_01u9','tnt_0017','usr_0013','Demo User','login.success','login',NULL,'{"via":"internal UI"}','10.0.200.51','2026-10-09 07:00:00'),
  ('aud_01ua','tnt_0017','usr_000t','Rustam Nazarov','ai.campaign.stopped','ai',NULL,'{"via":"internal UI"}','10.0.36.197','2026-09-18 20:00:00'),
  ('aud_01ub','tnt_0017','usr_000t','Rustam Nazarov','order.confirmed','order',NULL,'{"via":"internal UI"}','10.0.104.30','2026-10-02 23:00:00'),
  ('aud_01uc','tnt_0017','usr_0013','Demo User','invoice.paid','invoice',NULL,'{"via":"internal UI"}','10.0.94.88','2026-09-26 17:00:00'),
  ('aud_01ud','tnt_0017','usr_000v','Sardor Toshmatov','ai.campaign.stopped','ai',NULL,'{"via":"internal UI"}','10.0.185.20','2026-09-29 10:00:00'),
  ('aud_01ue','tnt_0017','usr_000u','Kamila Ergasheva','export.csv','export',NULL,'{"via":"internal UI"}','10.0.20.129','2026-10-10 08:00:00'),
  ('aud_01uf','tnt_0017','usr_000w','Nilufar Ahmedova','login.success','login',NULL,'{"via":"internal UI"}','10.0.205.111','2026-09-21 00:00:00'),
  ('aud_01ug','tnt_0017','usr_000r','Jasher Deen','ai.campaign.stopped','ai',NULL,'{"via":"internal UI"}','10.0.117.141','2026-09-30 01:00:00'),
  ('aud_01uh','tnt_0017','usr_000u','Kamila Ergasheva','role.changed','role',NULL,'{"via":"internal UI"}','10.0.48.5','2026-10-11 12:00:00'),
  ('aud_01ui','tnt_0017','usr_000u','Kamila Ergasheva','invoice.paid','invoice',NULL,'{"via":"internal UI"}','10.0.205.89','2026-09-22 09:00:00'),
  ('aud_01uj','tnt_0017','usr_000y','Zilola Karimova','user.invited','user',NULL,'{"via":"internal UI"}','10.0.100.46','2026-09-28 06:00:00'),
  ('aud_01uk','tnt_0017','usr_000u','Kamila Ergasheva','export.csv','export',NULL,'{"via":"internal UI"}','10.0.81.253','2026-09-18 02:00:00'),
  ('aud_01ul','tnt_0017','usr_000t','Rustam Nazarov','login.success','login',NULL,'{"via":"internal UI"}','10.0.99.103','2026-10-01 10:00:00'),
  ('aud_01um','tnt_0017','usr_000t','Rustam Nazarov','deal.stage_changed','deal',NULL,'{"via":"internal UI"}','10.0.191.56','2026-09-24 09:00:00'),
  ('aud_01un','tnt_0017','usr_000t','Rustam Nazarov','deal.stage_changed','deal',NULL,'{"via":"internal UI"}','10.0.58.95','2026-09-27 07:00:00');
INSERT INTO audit_logs VALUES
  ('aud_01uo','tnt_0017','usr_000w','Nilufar Ahmedova','settings.updated','settings',NULL,'{"via":"internal UI"}','10.0.47.71','2026-10-08 12:00:00'),
  ('aud_01up','tnt_0017','usr_000v','Sardor Toshmatov','ai.campaign.started','ai',NULL,'{"via":"internal UI"}','10.0.152.29','2026-09-23 17:00:00'),
  ('aud_01uq','tnt_0017','usr_0013','Demo User','login.success','login',NULL,'{"via":"internal UI"}','10.0.100.192','2026-10-01 16:00:00'),
  ('aud_01ur','tnt_0017','usr_000t','Rustam Nazarov','login.success','login',NULL,'{"via":"internal UI"}','10.0.240.226','2026-09-30 15:00:00'),
  ('aud_01us','tnt_0017','usr_000x','Bekzod Rahimov','settings.updated','settings',NULL,'{"via":"internal UI"}','10.0.130.196','2026-09-14 16:00:00'),
  ('aud_01ut','tnt_0017','usr_000r','Jasher Deen','user.invited','user',NULL,'{"via":"internal UI"}','10.0.246.77','2026-10-05 02:00:00'),
  ('aud_01uu','tnt_0017','usr_000u','Kamila Ergasheva','ai.campaign.stopped','ai',NULL,'{"via":"internal UI"}','10.0.227.193','2026-09-21 20:00:00'),
  ('aud_01uv','tnt_0017','usr_000r','Jasher Deen','user.invited','user',NULL,'{"via":"internal UI"}','10.0.179.143','2026-09-21 17:00:00'),
  ('aud_01uw','tnt_0017','usr_000t','Rustam Nazarov','settings.updated','settings',NULL,'{"via":"internal UI"}','10.0.194.82','2026-09-17 01:00:00'),
  ('aud_01ux','tnt_0017','usr_000u','Kamila Ergasheva','invoice.paid','invoice',NULL,'{"via":"internal UI"}','10.0.251.119','2026-10-09 01:00:00');

PRAGMA foreign_keys = ON;
