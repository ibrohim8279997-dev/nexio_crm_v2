# Operations: Nexio CRM

A SaaS runs differently from a shop, so the usual sections are adapted: "suppliers" are the services Nexio depends on, "equipment" is the server, and the daily cycle is onboarding, support and keeping the system up. Facts about the system come from `docs/OPERATIONS.md` in the repo; outside facts are linked. Prices of the founder's own server and team are **not known** and stay open.

## 1. The daily cycle

| Step | What happens | Customer sees it? |
| --- | --- | --- |
| 1. Check health | `GET /api/v1/platform/health`, alert webhook, Sentry if set. Look at failed jobs, dead webhooks and the last backup age (over 36 h = failed) | No |
| 2. Support inbox | Answer Telegram and email questions; target: reply within one working day | Yes |
| 3. Onboarding | Founding customers: receive Excel / Telegram / amoCRM / Bitrix24 data, import it, check it, show it to the owner | Yes |
| 4. Billing | Hourly renewals (`nexio:billing:renew`); watch failed Click / Payme charges; send receipts | Yes |
| 5. Build and deploy | `./deploy.sh` on the server; backup first for any migration | Sometimes (maintenance) |
| 6. Backup | `nexio:backup` at 02:00; copy the folder off-server (not yet set up per the founder) | No |
| 7. Review | Weekly numbers: demos, pre-sales, refunds, churn, support time | No |

## 2. The services Nexio depends on

| Need | Option | Price / how to get it | Status |
| --- | --- | --- | --- |
| **Hosting** | The founder's own server (CloudPanel on the Uzbekistan server per `docs/ARCHITECTURE.md`) | Monthly cost **not given**. Ask the hosting provider for the invoice | Open; CFO uses an estimate |
| **Off-server backup** | A second location: S3-compatible storage (e.g. Cloudflare R2, Backblaze B2) or a second VPS via rsync | Public price pages on the provider's site; a few dollars a month for small data (estimate, not checked) | **Must be live before the first founding customer** |
| **Payments** | Click and Payme merchant accounts | No verified online merchant rate found. A third-party listing says Click 1% on Uzcard/Humo and 1.5 to 2.5% on Visa ([source](https://paymentproviders.io/providers/click-uzb.md)); national QR payments cost merchants 0.65% ([source](https://kun.uz/en/news/2026/06/23/uzbekistan-rolls-out-mandatory-qr-payment-system-charging-merchants-on-each-transaction-a21a28)). Rates are negotiated: ask both for the merchant tariff | The CFO's 2.5% is the high end, so conservative |
| **Telegram bot / notifications** | Telegram Bot API | Free | Exists in the product |
| **Email / SMS** | Any transactional email service; a local SMS gateway | Prices not researched | Open |
| **Error tracking** | Sentry (optional in the repo) | Free tier exists; check current plan | Optional |

If the real server or payment fees differ from `numbers.json`, update it and have `/founder-cfo` re-run.

## 3. People

Known: the founder says 4 to 10 people including the founder, but not who does what or what they cost. The model has **no payroll** (every $1,000 a month adds about 25 accounts to break-even, see `cfo.md`). Proposed split for the first 50 customers:

| Role | Hours a week (first month) | Does |
| --- | ---: | --- |
| Founder / sales | 25 | Calls, demos, pre-sales, references |
| Onboarding (migration) | 10 to 15 | Imports data for founding customers, 2 to 3 h each |
| Support | 8 | Telegram replies, same working day |
| Developer | 10 | Bug fixes, backup job, import tools |

Open: whether these are existing salaried staff (a cost the model leaves out) or contractors. Payroll taxes in Uzbekistan were not researched: accountant.

## 4. Routines (SOPs)

**A. Onboard a founding customer (target: live within 3 working days of payment)**
1. Customer pays the first month by Click or Payme (refundable for 30 days). Send the welcome message with a named contact.
2. Ask for their Excel, Telegram export or amoCRM/Bitrix24 export. Do not ask them to clean it.
3. Import into a test account first; check counts of clients, phones and debts against the original.
4. Fix duplicates and phone formats; tell the owner what you changed.
5. Open it with the owner on a 20-minute call using their own list; set up users.
6. Day 7: check in; day 14: ask for problems; day 30: ask about the refund and a referral; day 60: ask for permission to be named as a reference.

**B. Handle a complaint**
1. Reply within one working day, in their language, from a named person.
2. Say what happened, in plain words; do not blame the customer.
3. Fix it or give the date; offer the month back if it is within 30 days.
4. Write the cause in a shared log; if the same cause happens twice, change the product or the routine.

**C. Deploy**
1. Run a backup first if the change includes migrations.
2. `./deploy.sh` from `docs/OPERATIONS.md`; check `GET /up` and the health endpoint.
3. If it fails, `php artisan migrate:rollback --step=1` only for migrations with `down()`; announce in the customer group if there was downtime.

**D. Weekly backup check**
1. Confirm `last.json` is under 36 hours old.
2. Confirm the off-server copy exists and its size is sane.
3. Once a month, restore the latest backup into a staging database and open it (restore runbook is in `docs/OPERATIONS.md`).

**E. Close the week**
Count demos, pre-sales, refunds, support tickets and hours; compare with `marketing.md` thresholds; decide the single change for next week.

## 5. Tools

Smallest stack: the product itself as the CRM (use Nexio to track its own customers); Telegram for support; a shared spreadsheet or Notion for the SOPs and the issue log; GitHub Actions (already in `.github/workflows/ci.yml`) for tests; Sentry for errors (optional). No new paid tool is needed to start.

## 6. Licences, permits, legal (confirm each with the authority; not legal advice)

- **Company registration and tax numbers:** confirm the company's current legal form and tax status with the tax authority or an accountant.
- **IT Park residency:** the IT Park programme lists tax benefits for resident software companies (reported as 0% corporate tax and 7.5% personal income tax, with VAT excluded). Figures differ by source; read the official rules at [it-park.uz](https://www.it-park.uz/en/itpark/news/useful-guide-what-support-programs-does-it-park-provide) and ask the accountant if the company qualifies. This could change the CFO's cost lines.
- **Personal-data law:** the March 2026 amendments loosened the rule that all personal data must be stored in Uzbekistan; biometric, genetic and telecom subscriber data still must ([summary](https://kun.uz/en/news/2026/03/28/uzbekistan-amends-personal-data-law-to-facilitate-global-payment-systems)). Cabinet lists were not yet approved as of the sources found. The founder's server is in Uzbekistan, which is the safe position, but **no lawyer has reviewed it**; check consent wording, the database-registration duty and a data-processing agreement for customers. This is a board condition.
- **Payment providers:** merchant agreements with Click and Payme; confirm what documents they require.
- **Trademark:** see `brand.md`; "Nexio" is not registered in Uzbekistan.
- **Terms and privacy policy:** customers store their own clients' data; a short terms of service and a privacy policy in Uzbek and Russian are needed before the first founding customer.
- **Insurance:** professional liability / cyber insurance availability in Uzbekistan was not researched.

## 7. Risk register

| # | Risk | Likelihood | Impact | Plan |
| --- | --- | --- | --- | --- |
| 1 | The server or its disk fails and the only backup is on it | Medium | **Critical** (customers' client lists lost) | Off-server backup before launch; monthly restore test; standby database exists but is on the same server |
| 2 | A founding customer's data import goes wrong and trust is lost | Medium | High | Import to a test account first; check counts; keep the original file |
| 3 | A key person is ill and support goes quiet | Medium | Medium | Two people can answer; auto-reply with the expected time |
| 4 | Click or Payme changes fees or pauses a merchant | Low | Medium | Card as a second route; keep merchant contacts |
| 5 | A personal-data complaint or a lawyer finds a gap | Low | High | Lawyer review; clear consent text; data kept in Uzbekistan |
| 6 | Support load from 50 migrations is higher than 3 h each | High | Medium | Cap founding customers; measure hours on the first 5; raise the price or shrink the offer |
| 7 | A deploy breaks billing renewal | Low | High | Backup first; watch `billing:renew` and failed charges after each deploy |
| 8 | Refund rate above 1 in 4 | Medium | Medium | Ask why at day 14; change the product or who is onboarded |
| 9 | A bigger rival (shamCRM, Bitrix24) cuts price or copies the offer | Medium | Medium | Win on named references and fast support, not on features |
| 10 | The founder promises AI voice or a feature that is not built | Medium | High | Keep voice off every price sheet and message until it works |

## Open questions

Real server cost; payroll and who does what; Click and Payme merchant tariffs; IT Park eligibility; legal review of personal-data duties; insurance. Update `numbers.json` and re-run `/founder-cfo` when any of these is known.
