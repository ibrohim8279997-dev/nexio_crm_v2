# Offer: Nexio CRM

Built from the first panel's objections (`founder/panel-v1/results.md`), the competitor complaints, the board's conditions and `founder/pricing.md`. The CFO has not run yet, so cost scores below are rough ranks, not money. Costs in dollars are open questions for `/founder-cfo`.

## 1. The problem list (in the buyer's words)

| # | Problem | Before / during / after | Source |
| --- | --- | --- | --- |
| 1 | "$89 a month is too much next to what I earn" | before | 10 of 20 passed on price |
| 2 | "Moving my data will take weeks and nobody does it for me" | before | 16 of 20 named free migration as a flip |
| 3 | "My spreadsheet and Telegram chats are free and work well enough" | before | 5 passed on habit |
| 4 | "I do not know this company, no customers I can ask" | before | 5 passed on trust |
| 5 | "Will it still exist and be supported in a year?" | before / after | P005, P009, P020 |
| 6 | "I only need contacts and deals, not finance and inventory" | before | P014, P017 |
| 7 | "I pay about $15 per user now, flat $89 is not obviously better" | before | P010, P015 |
| 8 | "My team will not learn another tool" | during | P011 |
| 9 | "Is AI voice real or a demo?" | before | P008 |
| 10 | "My clients pay me in so'm, I avoid foreign cards" | before | P017 |
| 11 | "What if I lose my client data?" | after | P009 |
| 12 | "Support is slow" | after | competitor complaints (Kommo, Bitrix24) |
| 13 | "Price grows with users and add-ons" | after | competitor complaints (Kommo) |
| 14 | "Setup by an integrator went wrong" | during | amoCRM complaints (crmindex) |

## 2. Solutions, scored (value to buyer 1 to 5, cost to deliver 1 to 5, 5 = costly)

| Problem | Solution | Value | Cost | Keep? |
| --- | --- | ---: | ---: | --- |
| 1, 6, 7 | **Lean plan**: 3 users, contacts, deals, Telegram only, $25 | 5 | 2 | Yes |
| 1 | Starter at $49 (test $39 to $49) instead of $89 | 5 | 3 | Yes, price test |
| 2, 14 | **Free done-for-you migration** (Excel, Telegram, amoCRM, Bitrix24) for the first 50 customers | 5 | 4 | Yes, capped |
| 3, 8 | Trial with your own data loaded by us, 14 days | 4 | 2 | Yes |
| 4 | Honest statement: no references yet. Offer to talk to the founder directly. | 3 | 1 | Yes |
| 5, 11 | Month-to-month, cancel any time; data export on request | 4 | 2 | Yes (export must exist: check) |
| 10 | Pay in so'm via Click or Payme | 4 | 1 | Already built |
| 9 | Remove AI voice from every price line until it works | 4 | 1 | Yes |
| 12 | Founder answers support in Telegram during the first 50 customers | 3 | 3 | Maybe |
| 13 | Flat price per plan, no per-user fees | 4 | 1 | Already built |
| 2 | Migration for unlimited customers | 5 | 5 | No, cost unknown |
| 4 | Fake reviews or logos | n/a | n/a | Never |

## 3. The stack

- **The core:** "Your clients and deals out of Excel and Telegram chats and into one place your team actually uses, with your data moved for you."
- **Bonus 1, the migration:** free for the first 50 companies. Kills "it will take weeks". Needs a scope limit (contacts and deals only, up to a set number) once the CFO prices it.
- **Bonus 2, the Lean entry plan:** $25 for 3 users. Kills "I only need contacts and deals" and "too expensive".
- **Bonus 3, month to month with a refund:** no lock-in.
- **Guarantee:** "If your team is not using it after 30 days of paying, we refund that month in full." Cost = one month of revenue per claim; the claim rate is unknown. Model it with the CFO before it goes public.
- **Urgency, true only:** the free migration is limited to the first 50 companies. That is a real limit; show a real counter.
- **Name:** "Founding 50": free data move, a Lean or Starter plan, and a direct line to the founder.

## 4. Value equation, before and after (simulated judgement, 1 to 10)

| Element | Before | After | What moves it |
| --- | ---: | ---: | --- |
| Dream outcome (clients in one place, team uses it) | 5 | 6 | Core described by outcome, not modules |
| Likelihood it works | 3 | 5 | Guarantee, cancel any time. Still no references. |
| Time to result | 3 | 7 | Data already loaded when the trial starts |
| Effort and sacrifice | 2 | 7 | We do the migration; Lean plan is smaller |

## 5. Re-test

Same seed (672231), same 20 buyer cards, new pitch in `founder/pitch.md` (old one in `founder/pitch-v1.md`, old results in `founder/panel-v1/`).

Result: see the section below, added after the run.

## Re-test result (same seed 672231, same 20 buyer cards)

| | Before (pitch v1) | After (new offer) |
| --- | ---: | ---: |
| Buy | 0 of 20 | **1 of 20** (5%) |
| Pass because of price | 10 | 1 |
| Pass because of habit | 5 | 5 |
| Pass because of trust | 5 | **13** |

What moved:
- **Price and migration objections mostly dropped.** The one buyer was P017, a small shop owner on amoCRM who wanted exactly the $25 contacts, deals and Telegram plan with free migration.
- **Trust became the main wall.** 13 of 19 passers said they cannot find anyone who uses it. Typical flip conditions now: "two or three Uzbek companies I can call", "a company I know using it for months", a live demo moving a sample of their data.
- **Habit did not move** (5 before, 5 after): their team will not change how it works, whatever the price or migration.
- Several small-income buyers still found even $25 heavy against incomes of $500 a month or less. Those incomes are the guesses in `customer.json`, not researched facts.

Reading it plainly: the offer fixed the price and effort problems for one segment and exposed the real problem, which is **no proof**. More discounts or bonuses will not fix that. What fixes it is 3 to 5 real customers who will take a call, which is also board condition 1. Treat the 5% as a sign of direction only: simulated buyers, 20 of them, one buyer is the whole difference.

**Caveat found later (13 Nov):** the Lean plan used in this re-test does not exist in the product's plans migration (only Starter, Business and Enterprise do). The one buyer who said yes (P017) wanted exactly that Lean plan, so the 5% result depends on a plan that has not been built. Treat the re-test as a test of the idea of a small plan, not of the current product.
