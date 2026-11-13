# Launch plan: Nexio CRM

Today is Thursday 12 Nov 2026. No target date was given, so the dates below are a **proposal**: start Monday 16 Nov, public launch **Monday 14 Dec 2026**. Lead times for payment merchant approval, the privacy-law check and any hire are unknown (see `ops.md`); if they are longer than two weeks, the whole plan slides. The only owner in the data is the founder. A helper is not hired.

## 1. Test before you spend: the paid pilot

The business is a subscription with a trust problem, so the cheapest real test is **paid founding accounts**, not a waitlist. A waitlist costs the buyer nothing and says little here.

**The test:** offer the Founding-50 deal (free migration, Lean $25 or Starter $49, 30-day refund) to companies from the outreach list in `marketing.md`, and take the first month's payment up front through Click, Payme or card.

**Success line, written before running it:**
- From about 10 demos: **at least 3 paying accounts** by Friday 11 Dec.
- **At least 2 of those agree in writing to be named as a reference.**
- **At least 2 migrations finished** with the customer confirming the data is correct.

**Compared with the panel:** the simulated buyers gave 0 of 20, then 1 of 20 (5%). Simulated buyers lean agreeable but this run was told to be sceptical, so neither number predicts real conversion. If real buyers fall well below 3 of 10 demos, trust the real buyers, go back to `/founder-offer` and `/founder-pricing`, and do not start paid ads. If 3 or more pay, scale the same outreach rather than changing it.

**Costs avoided if the test fails:** the $1,000 launch-ad budget in the CFO file is not spent until the gate passes. The migration cost (about $30 per account, estimate) is the real spend.

## 2. The countdown

| Week | Dates | Block | Tasks | Owner |
| --- | --- | --- | --- | --- |
| 1 | 16 to 20 Nov | Money and legal | Get the real monthly server price (production is CloudPanel, see `ops.md`); start Click/Payme merchant applications; book the lawyer for the personal-data check | Founder |
| 1 | 16 to 20 Nov | Product | Remove or label AI voice and telephony on every plan page; build and seed the Lean plan (it does not exist in the plans migration, checked 13 Nov); check data export exists | Founder |
| 1 | 16 to 20 Nov | Product | Test migration on 3 real spreadsheets; write the migration checklist (`ops.md` routine A) | Founder |
| 2 | 23 to 27 Nov | Money and legal | Privacy policy and terms reviewed; accountant on invoicing in so'm; off-server backups set up | Founder + accountant |
| 2 | 23 to 27 Nov | Brand | Fix white-on-green contrast; trademark search for "Nexio" in classes 9 and 42; domain check | Founder |
| 2 | 23 to 27 Nov | Marketing | Build the 60-company list; record the 2-minute migration video; write the landing page | Founder |
| 3 | 30 Nov to 4 Dec | Test | Marketing calendar D-14 to D-8: partner calls, first 20 company messages, first demos (starts Mon 30 Nov) | Founder |
| 4 | 7 to 11 Dec | Test | Landing page live, next 20 messages, pilots offered, payments taken, migrations run | Founder |
| 4 | Fri 11 Dec | **Gate** | Count against the success line. Decide: launch, delay, or change the offer | Founder |
| 5 | Mon 14 Dec | **Launch** | See run sheet | Founder |

**Critical path (tasks that move the date if they slip):**
1. Payment merchant approval. Without a way to take payment, no paid pilot is possible.
2. The personal-data lawyer check, if it requires moving servers.
3. Migration tested on real files. If it is slower than 3 hours per account, the free-migration promise and the CFO model break.
4. Getting the first 3 demos booked. Everything after depends on it.

A soft open is the paid pilot itself: friendly, small, and with real money.

## 3. Launch day: Monday 14 Dec (Tashkent time)

Only run this if the gate on 11 Dec passed.

| Time | What |
| --- | --- |
| 08:30 | Check health endpoint, backups under 36 hours old, payment test (a real small payment and refund) |
| 09:00 | Landing page live with the true "Founding 50" counter showing the real number left |
| 09:30 | Send the offer to the first 20 companies (marketing D-day row) |
| 10:00 | Post the migration video in the owner groups where rules allow |
| 10:30 | Message every partner with the launch note |
| 11:00 to 17:00 | Answer every reply within an hour; book demos; run migrations for paid accounts |
| 17:30 | Note every objection in the sheet |
| 18:00 | Count: demos, trials, paid, refunds |

**If something breaks:** payments down, switch to the card path and tell customers by Telegram; migration fails, stop new migrations, tell the affected customer, restore from the original file; health check red, check the alert, roll back the last deploy; the risk register in `ops.md` has the rest.

## 4. First 30 days

Numbers to track weekly (from `marketing.md` and `cfo.md`; remember the CFO's "a day" means "a month" here):

| Number | Target | Change something if |
| --- | --- | --- |
| Demos per 20 companies contacted | at least 2 | below 2 for two weeks |
| Trials that finish migration | at least half | below half |
| Paying accounts after 30 days | zero after first 10 trials is a stop signal | zero after 10 trials |
| Written reference permissions | at least 3 by day 30 | none by day 14 |
| Paying accounts vs CFO ramp | 2 in month 1, 4 in month 2 (estimates) | far below; revisit with real data |
| Refund requests | note each reason | more than 1 in 5 paying accounts |

**Reviews:**
- **Day 7 (Mon 21 Dec):** how many demos, what did people object to, did any migration go wrong? Adjust the message, not the price.
- **Day 14 (Mon 28 Dec):** are the first customers using it, not just paying? Ask each how the team is doing. Collect references. Check support response times.
- **Day 30 (Wed 13 Jan 2027):** compare real paid accounts against the break-even of 14. Decide: continue, change the segment, or stop. Replace the CFO's estimated inputs with real ones and re-run `/founder-cfo`.

## Open questions

Who is the second person on the team; how long Click/Payme approval takes; whether the data law requires local servers; whether a 3-hour migration holds on real files.
