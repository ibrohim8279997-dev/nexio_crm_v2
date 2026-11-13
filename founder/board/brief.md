# Board brief: Nexio CRM

Evaluate this business idea. You have no other context. Facts below come from the product's own repository; nothing is invented. Anything marked "unknown" is an open question, not a weakness to assume.

# Nexio CRM (by Nexio Lab)

- What it is: A multi-tenant SaaS CRM (web, plus Android/iOS apps via Capacitor) with contacts, leads, deals, tasks and projects, finance and invoices, helpdesk/SLA, client portals, automation, API/webhooks, plugins (inventory, delivery, telephony, AI assistant) and AI sales agents/calls.
- Who it is for: Small and growing companies, apparently in Uzbekistan (Uzbek/Russian/English UI, Click and Payme payments, CBU exchange rates, Telegram integration), that currently use amoCRM-style tools. Exact target segment/industry is not stated in the repo and still needs the founder's answer.
- What it sells, at what price (from the plans migration, per month, yearly = 10x monthly; currency is read from the plan record, charges converted to UZS for Click/Payme):
  - Starter 89, up to 5 users, no AI minutes, 5 GB
  - Business 199, up to 15 users, 200 AI minutes, 25 GB (badged "most popular")
  - Enterprise 349 ("from", contact sales), up to 50 users, 500 AI minutes, 100 GB, SSO, voice calls
  - New signups start on a Starter trial.
- Where and how: Online SaaS, self-serve signup with trial, card/Click/Payme billing. Real-time voice (ASR/TTS/SIP) is not implemented; AI calls are sandbox simulations.
- Budget and constraints: Not stated. Open question for the founder (money, runway, team size, current paying customers).

## Additional facts from the repo
- Stack: React/TypeScript SPA, Laravel API, plugin architecture; a docs PDF "Nexio_vs_amoCRM" shows the founder positions against amoCRM.
- Features documented: RBAC, audit trail, tenant isolation, webhooks, quotes, orders, inventory, delivery, Telegram, AI assistant with token limits per plan, client portals (portal accounts do not consume seats).
- Known gaps: AI voice is simulated; controllers partly large/legacy.
- Unknown: customer count, revenue, churn, acquisition channel, CAC, team size, budget, exact currency/country of sale.
