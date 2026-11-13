# Pricing: Nexio CRM

Inputs: the 20-buyer simulated panel ([pricing-curve.md](pricing-curve.md)), competitor list prices ([competitors.md](competitors.md)). The CFO has not run yet, so there is no margin figure. Cost per customer is an open question and the price below must be checked against it in `/founder-cfo`.

## 1. The price

**Current Starter price, $89/mo for 5 users, is above the panel's acceptable range ($15.50 to $69.97) and is the most expensive 5-user entry plan in the competitor table.**

- Panel: OPP and IPP both at **$35.56**. Median "a bargain" $30, "getting expensive" $55, "too expensive" $90. 0 of 20 would buy at $89.
- Competitors, 5 users: Bitrix24 Basic about $49 to $69, Kommo Base $75, shamCRM from $15 for 3 users. Nexio at $89 is above all of them.
- Recommendation to test: **Starter at $39 to $49 per month for 5 users.** That is inside the acceptable range, near the least-resistance point, and at or below Bitrix24 and Kommo for the same team size.

Business ($199, 15 users) and Enterprise ($349, 50 users) were not priced by their own panel cohort, because only 8 buyers had 7 to 15 staff and they gave one set of answers each. Evidence so far: those buyers said "too expensive" between $100 and $300 for their whole team (P011: $160, P015: $100, P019: $300, P020: $100, P013: $120). Treat $199 as untested, not as wrong. At 15 users Kommo Base is $225, so $199 is only slightly below it.

## 2. The ladder

| Rung | Who | Price to test | Reason to step up |
| --- | --- | --- | --- |
| Lean (new) | 1 to 3 users, contacts, deals, Telegram only | $19 to $25 | The panel's small-shop owners said $15 to $30. A cheap rung competes with shamCRM ($15 for 3 users). |
| Starter | up to 5 users | $39 to $49 | Projects, tasks, quotes, invoices |
| Business | up to 15 users | $149 to $199 (test) | Client portal, finance, helpdesk, automation |
| Enterprise | up to 50 users | from $349, contact sales | SSO, voice (only when voice is real) |

The Lean rung is new: it does not exist in the plans migration (`backend/database/migrations/2026_11_10_000400_pricing_page_plans.php` has only Starter, Business and Enterprise, checked 13 Nov), so it must be built before it can be sold. It matches the objection "I only need contacts and deals" (P014, P017). Whether it cannibalises Starter is unknown.

## 3. The opening offer

- **Free data migration from amoCRM, Bitrix24 or Excel for the first 50 customers**, offered until a fixed end date. 16 of 20 buyers named free migration or import as part of what would flip them (counted from the answer files by keyword).
- A trial long enough to use: 14 days, extendable once. Several buyers asked for two weeks of real team use.
- No fake "was $X" anchors. Announce the new prices as the prices.

Do not put AI voice in any price line until it works.

## 4. What to test with real buyers

1. Two landing pages with Starter at **$39** and **$49** (same page otherwise). Measure sign-ups and trial starts.
2. A pre-sale: 10 to 20 firms from one niche pay a deposit for a founding-customer rate, with migration included. This also tests currency: show the price both in USD and in so'm.
3. Ask each trial user the four Van Westendorp questions in a survey. Real answers replace the simulated ones.

## 5. The panel's top price objections (verbatim, for marketing)

1. "89 dollars a month is more than 1,000 a year, and I earn about 6,000. That is a sixth of my income for a program, and my notebook and Telegram chats work for free." (P001)
2. "I pay about $15 per user elsewhere and cannot see why a flat $89 is better" (a pitch-stage objection, P010 and P015: the flat plan only wins above about 6 users)
3. "I might open the free trial, but $199 a month for my team is a lot to commit to when I'd have to move everything over myself." (P002)

## Limits

Simulated buyers lean towards price sensitivity when told to be sceptical, so $35.56 is a test point, not a verdict. Only 20 answers, thin per segment. The 0% buy rate in the first panel comes from buyers told to be unagreeable, with incomes set by guesses in `customer.json`. Real owner incomes in Uzbekistan were not researched; the founder judged the guessed incomes roughly right (13 Nov), and named small and growing companies as the first segments.
