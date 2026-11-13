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

## Founder answers (13 Nov 2026)

- **Customers:** 1 to 5 paying customers today. Nobody has agreed to act as a reference.
- **Team:** 4 to 10 people (founder included).
- **Price currency:** plans are priced in USD and converted to so'm for Click and Payme.
- **Starter price:** the founder chose to keep Starter at $89 and not run the $39 to $49 test.
- **AI voice:** real-time voice is expected within 3 months. Until then it will be labelled "coming soon" on the plans, not removed.
- **Segments:** retail and shops, plus services; small and growing companies. Rough income guesses in the buyer panel were judged about right.
- **Name and domains:** the founder owns the domains; the "Nexio" trademark is not registered.
- **Servers and law:** servers are in Uzbekistan; no lawyer has reviewed the personal-data rules.
- **Backups:** only on the same server, nothing off-server.
- **Production:** the founder's own server (CloudPanel per the repo docs), not Cloudflare.
- **Code:** light-theme success green was darkened from `#10b981` to `#047857` (text contrast 2.5 to 5.5); dark theme keeps `#10b981`.
- **Not answered:** current real prices paid for amoCRM or Bitrix24, the real monthly server price, the income the founder needs, whether the founder knows shamCRM.

