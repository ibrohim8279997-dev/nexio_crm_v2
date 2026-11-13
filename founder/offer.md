# The offer: Nexio CRM

Built from `founder/panel-v1/` (the 20 buyers who saw the old pitch), the competitor map, the board's conditions and `founder/pricing.md`. **Costs below are effort estimates, not CFO numbers**: `founder/numbers.json` does not exist yet, so `/founder-cfo` must confirm that every item is affordable.

## Offer name

**The Founding 50: we move you in, you pay $29 to $49, you can leave in 30 days.**

## The problem list (buyer's words, where possible)

Before buying
1. "$89 a month is a lot, and Bitrix24 has a free plan." (price, 13 of 20 passed on price)
2. "I don't know a single company that uses it." (no references, raised by almost every buyer)
3. "I would have to move all my clients and retrain my staff." (switching effort)
4. "I can't tell which plan I need." (plan confusion)
5. "Is my data safe? Backups?" (data safety, P009, P017)
6. "AI calls don't work yet, so I'd pay for a promise." (P020, P019, P012)
7. "My Excel works and costs nothing." (habit, P001, P016)
8. "What if it doesn't work for my business?" (risk)

During
9. Importing clients from Excel, Telegram, amoCRM or Bitrix24
10. Learning a new tool, staff resistance
11. Not knowing the first thing to do after signup (board, Product lens)
12. Support slow or in the wrong language (the competitors' top complaint)

After
13. Lock-in, being stuck if it breaks (P001)
14. Price rising later
15. The vendor disappearing (1 to 5 customers)

## Solutions, scored (value to buyer 1-5 / cost to deliver 1-5)

| Problem | Solution | Value | Cost | Keep |
| --- | --- | ---: | ---: | --- |
| 1 | Lean $29 (3 users) and Team $49 (5 users) | 5 | 3 (margin, CFO to check) | yes |
| 2, 15 | Founding customers named as references after 60 days; buyers may call them; 30-day refund | 4 | 1 | yes |
| 3, 9, 10 | Free migration by the Nexio team plus a first-use call in Uzbek or Russian | 5 | 3 (staff hours) | yes, capped at 50 customers |
| 4 | Two cards on the page, picked by headcount | 3 | 1 | yes |
| 5 | Daily backup copied off-server (the repo's runbook says to) | 4 | 2 | **yes, must be live before the first founding customer** |
| 6 | Take AI voice off every plan and say so | 3 | 1 | yes |
| 7 | Run Excel and Nexio side by side during the first 2 weeks (the migration team does the first import) | 3 | 2 | yes |
| 8, 13 | 30-day refund of the first month | 4 | 2 (claim rate unknown) | yes |
| 12 | Answer inside one working day in Telegram | 4 | 3 (people) | test it, only if staff exist |
| 14 | Price locked for 12 months | 3 | 1 | yes |
| 11 | Guided first run | 4 | 4 (needs building) | later, not promised |

Dropped: AI voice and AI minutes in the pitch (not built); "export all data" (the repo shows a CSV export for deals and reports only, so it is not promised); any call-center or inventory pitch.

## The stack

**Core (the outcome, not the features):** "Your clients, deals and payments in one place on your phone, set up for you in a week, for the price of about a Bitrix24 seat pack or less."

**Bonuses (each kills one objection)**
1. Free migration of your client list from Excel, Telegram, amoCRM or Bitrix24 (objection 3, 9).
2. Price locked for 12 months at the founding rate (objection 14, 1).
3. Daily backup copied off-server, in writing (objection 5).

**Guarantee:** if it does not work for you, the first month's money back within 30 days, no questions. *Cost to model:* at $29 to $49, one refund is at most $49 plus about 2 to 3 hours of migration time per claim (an estimate to confirm). The CFO must set a claim rate before this is promised publicly.

**Urgency (true):** a cap of 50 founding companies and an end date, 31 Jan 2027. The cap is real only if the team can migrate 50 customers; say 50 only if that is true.

## Value equation (1 to 10)

| Element | Before (old pitch) | After | What moved it |
| --- | ---: | ---: | --- |
| Dream outcome | 4 | 5 | Stated as an outcome, not a feature list |
| Perceived likelihood | 2 | 5 | Refund, references to call after 60 days, off-server backup. Still low: nothing is proven yet |
| Time to the result | 3 | 6 | Migration done for them |
| Effort and sacrifice | 2 | 6 | Free migration, side-by-side Excel, no staff retraining burden |

## Re-test

Same seed 876453, same 20 buyer cards, shown the new pitch (`founder/pitch.md`; old pitch kept as `founder/pitch-v1.md`; old results in `founder/panel-v1/`).

| | Old pitch ($89 Starter) | New offer ($29 / $49, free migration, refund) |
| --- | ---: | ---: |
| Buy | 0 of 20 | **0 of 20** |
| Passed on price | 13 | 4 |
| Passed on trust | 5 | 11 |
| Passed on habit | 2 | 5 |

**The buy rate did not move.** The offer did what it was built to do on price (13 down to 4) but the objection simply became trust (5 up to 11) and habit (2 up to 5). Nobody was moved to yes by price, migration or the refund alone. What the buyers say would flip them now is almost the same in every answer: **a person they know and can call who already uses it**, plus a free month with their own clients moved in. For example (P003): "I do not know a single company that uses it, and they say only 1 to 5 firms pay today. I would not move my clients out of Excel and Telegram into something with no references".

What this means:
- The next lever is not price or bonuses; it is **proof**. That is the paid pilot (launch plan): 3 to 5 real founding customers whose owners agree to take calls. Until then an offer cannot clear the trust objection.
- The 30-day refund and free migration are still worth keeping; they are what a buyer asks for after trust exists. Several buyers said they would want a **free first month with their data moved in** before paying anything, which the pilot can offer at a small, known cost.
- Panel prices dropped once $29 was shown (acceptable range $8 to $25 instead of $8 to $40, IPP $15): simulated buyers anchor to the number in front of them, so treat ranges as a guide to testing, not truth. $29 is still above the new range; test $19 as well.
- These are simulated buyers and lean agreeable if anything, so a 0% result is a warning, not a measurement. Real buyers are the only test that counts.
