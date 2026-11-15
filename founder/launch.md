# Launch plan: Nexio CRM

Written 13 Nov 2026. **Launch day L = Monday 14 Dec 2026** (the earliest date that leaves time for the backup, the legal pages and the merchant agreements; see the critical path). The Founding 50 offer ends 31 Jan 2027.

## 1. The test before the money (the founding-member pilot)

The business is a subscription, so the test is a paid founding offer: real owners pay the first month up front (refundable for 30 days). The panel said 0 buy out of 20, twice, so the real test matters more than anything in this pack.

**Test:** show the Founding 50 sheet (Lean $29, Team $49, free migration, 30-day refund, price locked 12 months) to **60 owners the founder knows or can be introduced to**, hold at least 15 demos, and ask each to pay the first month.

**Success line, written before the test:**
- **Pass:** at least **5 paid first months** by 31 Dec, of which **at least 3 agree in writing to be references**. That is above the CFO's month-1 ramp of 1 account and would be the first real proof.
- **Fail:** fewer than **2 paid out of 15 demos** by 21 Dec. Then do not spend on anything else; go back to `/founder-offer` and `/founder-pricing` and ask the buyers who said no what they would pay for, in their own words.
- **In between (2 to 4 paid):** keep going to 31 Jan, but change one thing: price, or who you are asking.

Compare with the panel: the simulated buyers said 0 of 20. If real owners pay, trust the real owners; if they don't, the panel and the real test agree and the offer needs more than a lower price.

## 2. The countdown (13 Nov to 14 Dec)

Owners: **F** = founder, **D** = developer, **S** = support/onboarding, **A** = accountant/lawyer (external). The critical path tasks are marked **CP**: if they slip, L slips.

| Week | Dates | Task | Owner | Deadline |
| --- | --- | --- | --- | --- |
| 1 | 16 to 22 Nov | **CP** Set up the off-server backup copy (rsync/S3), run a restore test | D | 22 Nov |
| 1 | | **CP** Ask a lawyer to review personal-data duties, database registration and a customer data-processing agreement | F, A | brief sent 18 Nov; answer by 4 Dec |
| 1 | | Write the terms of service and privacy policy (Uzbek, Russian) from the lawyer's template | F | 27 Nov |
| 1 | | Confirm company status; ask the accountant about IT Park eligibility and VAT | F, A | 22 Nov |
| 2 | 23 to 29 Nov | **CP** Apply for Click and Payme merchant accounts; ask for the merchant tariff (also feeds the CFO) | F | applied 23 Nov; live by 10 Dec |
| 2 | | Build the Lean and Team plan cards in the product (the custom plan already prices 1 to 3 users) and remove voice from every price sheet | D | 29 Nov |
| 2 | | Fix the page title / tagline; reserve the Telegram handle; file the name in Uzbekistan if the lawyer says go | F | 29 Nov |
| 2 | | Build the 60-name contact list; draft the Founding 50 sheet (Uzbek, Russian) | F | 29 Nov |
| 3 | 30 Nov to 6 Dec | Marketing calendar L-14 to L-8 (see `marketing.md`): outreach to 60 owners, first demos | F | daily |
| 3 | | Load-test the import with three real Excel files; fix the worst bugs | D | 4 Dec |
| 3 | | **Soft open**: 3 friendly owners use it free for a week; collect everything that confuses them | F, S | 6 Dec |
| 4 | 7 to 13 Dec | **CP** Backup verified, lawyer's notes applied, Click/Payme live, welcome message tested | D, F | 10 Dec |
| 4 | | Publish the Founding 50 sheet; prepare payment links | F | 7 Dec |
| 4 | | Train the support person on SOPs A and B in `ops.md` | F | 11 Dec |
| 4 | 14 Dec | **Launch day** | all | 14 Dec |

If the lawyer's review is not back by 4 Dec, launch without any claim about data location and hold the backup claim until the copy is tested.

## 3. Launch day run sheet (Monday 14 Dec)

| Time | What | Who |
| --- | --- | --- |
| 08:30 | Health check: `/up`, health endpoint, last backup under 36 h, off-server copy present | D |
| 09:00 | Open the Founding 50 sheet with live payment links (Click, Payme); post in 3 owner groups | F |
| 09:30 | Message every demo contact personally: "Founding 50 is open until 31 Jan" | F |
| 10:00 to 13:00 | Demos and calls | F |
| 13:00 | Check the first payments arrived; send the welcome message to each payer | S |
| 14:00 to 17:00 | Start the first migrations; answer Telegram | S, D |
| 17:30 | Count: demos, paid, refund questions, bugs | F |
| 18:00 | Write the one change for tomorrow | F |

**If something breaks:**
- Payment link fails: take the payment manually (card) and refund or reconcile after; tell the buyer.
- Site or API down: restore from the latest backup only if the database is damaged; otherwise roll back the deploy (SOP C).
- An import loses data: stop, keep the original file, tell the owner the same day, and offer the month back.
- Someone asks for AI voice: say it is not built.

## 4. The first 30 days (14 Dec to 13 Jan)

Weekly numbers (thresholds from `marketing.md` and the CFO):

| Number | Level that means "change something" |
| --- | --- |
| Demos held per week | under 2 two weeks running: your network is too small; change the channel |
| Paid first months per demo | under 1 in 6 after 20 demos: change price or offer |
| Refunds in 30 days | over 1 in 4 paid accounts: the product or who you sell to is wrong |
| Accounts live vs. CFO break-even | break-even is **20 paying accounts**; by month 3 the CFO ramp assumes 4 |
| Migration hours per customer | over 4 h average: shrink the free migration or raise the price |

**Reviews:**
- **Day 7 (21 Dec):** How many demos and pre-sales? What did each no say, in their own words? Did any import fail?
- **Day 14 (28 Dec):** Is anyone using it daily? Which questions came up three times? Is support reply time under one working day?
- **Day 30 (13 Jan):** Did we pass the success line? How many references can be called? What is the real cost per customer (hours, fees) versus the CFO's estimate? Update `numbers.json` and re-run `/founder-cfo`.

## What this plan does not do

No paid ads, no hire before there are five paying customers, no AI voice promise, no claim about references until three customers have said yes in writing.
