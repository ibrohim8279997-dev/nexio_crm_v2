# Operations: Nexio CRM

Facts about how the system runs come from the repo's `docs/OPERATIONS.md` and `docs/SECURITY.md`. Prices, wages and legal requirements were **not** researched or invented; they are listed as quotes or checks the founder must get. This is a one-person-to-small-team SaaS, so "operations" means: keep it up, move customers in, answer them, get paid.

**Production host (confirmed from the repo).** `docs/ARCHITECTURE.md` says the Cloudflare Worker and D1 code (`api/`, `wrangler.toml`, `migrations/`) is a legacy API that is *not used in production*. Production is the Laravel app on a CloudPanel server (`crmb.nexiolab.uz`, deployed with `deploy.sh`, PM2 for the frontend). So hosting is a **mostly fixed server bill**, not a cost per account. The CFO model spreads it as part of the $3 per account, which understates the cost at low volume. The real server price is still unknown: get it, add it as a fixed cost and re-run `/founder-cfo`.

## 1. The daily cycle

| Step | Who sees it | What |
| --- | --- | --- |
| 1. Check health | internal | Open `GET /api/v1/platform/health`; the health alert posts to `ALERT_WEBHOOK_URL` if something degrades |
| 2. Support inbox | customer | Answer new questions (Telegram first) within the same day |
| 3. New trials | customer | For each trial that asked for migration, receive their Excel/Telegram/amoCRM/Bitrix24 data |
| 4. Run the migration | customer | Import, check counts, send a "your clients are in" message |
| 5. Check payments | customer | Click/Payme/card confirmations match subscriptions; failed renewals followed up |
| 6. Webhooks and jobs | internal | Look for `webhook_deliveries` with status `dead`, failed queue jobs |
| 7. Follow-ups | customer | Day 3, day 10, day 28 of each trial: is the team using it? |
| 8. Deploy, if needed | internal | `./deploy.sh` after taking a backup if migrations are included |

## 2. Suppliers and services (get quotes; no prices claimed)

| Need | Options to quote | Notes |
| --- | --- | --- |
| Server hosting | The current CloudPanel server provider; a local data-centre provider | Ask: monthly price, backups, location of the servers (see section 6, data location) |
| Payment collection | Click and Payme merchant accounts, a card acquirer | Ask: commission, payout delay, onboarding documents. The CFO used 2.5% as a guess |
| SMS / OTP | A local SMS gateway | Ask: price per SMS, sender-name approval time |
| Email sending | A transactional email provider | Ask: free tier, domain verification |
| Error monitoring | Sentry (the app supports it via `SENTRY_LARAVEL_DSN`) | Free tier exists; check current limits |
| Off-server backups | Object storage (S3-compatible, R2 or MinIO supported by the app) | The repo says a backup on the same disk is not disaster recovery |

If real prices differ from `numbers.json` (hosting $3 per account, payment 2.5%), update the file and re-run `/founder-cfo`.

## 3. People

Nobody is hired in the data. The CFO's estimate assumed one part-time support/onboarding helper at $500 a month. Weekly rota for the slow first month:

| Role | Hours | What |
| --- | --- | --- |
| Founder | variable | Demos, migrations until a helper is trained, deploys, billing issues |
| Support/onboarding helper (not hired) | part-time | Telegram inbox, migrations, follow-ups |

Wage assumption: from the CFO file (estimate). Local payroll costs and taxes were not researched; ask the accountant.

## 4. Routines

**A. New customer migration (target: same day for a small file)**
1. Get the file or export (Excel, amoCRM, Bitrix24) and the customer's written OK to handle their client data.
2. Open it, count rows, remove obvious duplicates only with the customer's agreement.
3. Import into a staging tenant first.
4. Check 10 random records against the original.
5. Move to the customer's real account.
6. Message the customer: counts imported, anything not imported and why.
7. Book a 20-minute walkthrough with the person who will use it daily.

**B. Complaint**
1. Reply within the same working day, from a person, not a template.
2. Say what you will do and by when.
3. Fix it, tell them, and offer the refund if the 30-day promise applies.
4. Record the cause in one line in a shared sheet; review it weekly.

**C. Deploy** (from the repo runbook)
1. Take a backup if the change includes migrations.
2. `cd` to the site folder and run `./deploy.sh` on the server.
3. Check `GET /up` and the platform health endpoint.
4. If a migration breaks, `php artisan migrate:rollback --step=1` only for migrations that define `down()`.

**D. Weekly**
1. Check that the last backup is under 36 hours old, and copy backups off the server.
2. Review dead webhooks, failed jobs and the disk space check.
3. Count demos, trials, finished migrations, paying accounts, refunds (the numbers in `marketing.md`).

## 5. Tools (smallest stack)

The app itself is the CRM for the business's own customers (dogfooding is cheap and credible). Beyond that: Telegram for support, a spreadsheet for the migration log, Sentry for errors, an accounting tool the accountant already uses. Prices not researched.

## 6. Licences, permits, data rules: a checklist to confirm

Rules change and differ by case. Confirm each with the authority or a local lawyer or accountant. Not legal advice. The official legal database is [lex.uz](https://lex.uz/); the IT Park programme is at [it-park.uz](https://it-park.uz/).

- [ ] **Business registration** (individual entrepreneur or a company) and tax registration.
- [ ] **Merchant agreements** with Click and Payme; their onboarding documents and any category limits for software subscriptions.
- [ ] **Personal data law.** The CRM stores customers' customers' personal data (names, phones, chats). Uzbekistan has a personal data law with requirements about where citizens' data is stored and processed. Check this one with a lawyer *before* choosing between servers in Uzbekistan and abroad. I did not verify the current text.
- [ ] **Privacy policy and terms of service** in Uzbek and Russian. The repo history mentions a privacy policy rewrite; have it reviewed.
- [ ] **IT Park resident status.** Ask whether it applies and what tax treatment it gives; confirm on the official page.
- [ ] **Telegram bot and messaging terms**, and **call recording consent** for the telephony plugin (recordings are stored; the repo restricts who can read them).
- [ ] **Invoices and VAT** for business customers; ask the accountant what to charge and how to issue invoices in so'm.
- [ ] **Insurance** (professional liability or cyber): ask a broker whether it is worth it at this size.

## 7. Risk register

| # | Risk | Likelihood | Impact | Plan |
| --- | --- | --- | --- | --- |
| 1 | No customer will act as a reference | High (no customers yet) | High (trust is the main wall) | Founding-50 pilots with written permission; free migration in return |
| 2 | A migration loses or corrupts customer data | Medium | High | Staging import, spot-check 10 records, keep original file, written OK |
| 3 | Backups only on the same server | Medium | Very high | Copy `storage/app/backups` off-server (S3 or R2) before taking real customers |
| 4 | Server bill is fixed but the CFO model spreads it per account | High | Low | Get the real monthly server price, add it as a fixed cost, re-run the CFO |
| 5 | Key person (the founder) unavailable | Medium | High | Runbook above, a second person with server access, the standby DB feature already in the repo |
| 6 | Payment provider holds or rejects the account | Low to medium | High | Apply early; keep card as a second path |
| 7 | AI voice or telephony oversold | Medium | High | Not in the price; label as "not ready" until real-time voice exists (the repo says it is a sandbox simulation) |
| 8 | Support falls behind during the first 50 | High | Medium | Same-day reply rule; cap trials per week |
| 9 | Personal-data rules breached | Unknown | High | Lawyer review before launch |
| 10 | Slow growth: fewer than 14 paying accounts | High (per CFO, break-even) | High | Watch the three weekly numbers; stop spend if no trials after 10 demos |

## Open questions for the founder

The real monthly server price, who handles support, who else can deploy, whether IT Park applies, real Click/Payme rates.
