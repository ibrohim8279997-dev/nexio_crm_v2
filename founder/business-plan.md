# Nexio CRM (by Nexio Lab) · Business plan

**Verdict: Not yet**

- ✓ Each paying account-month earns $54.50 before fixed costs (91% contribution).
- ✓ Year 1 operating profit: $5,770.
- ✗ 1 of 20 simulated buyers buy (5%, the bar is 25%).

| key number | |
| --- | ---: |
| Price | $60.00 a paying account-month |
| Profit margin at plan | 66% per paying account-month |
| Break-even | 14 paying account-months a day |
| Year 1 operating profit | $5,770 |
| Startup spend | $3,000 |
| Cash needed before it pays for itself | $4,746 |
| Startup money earned back | month 11 |
| Buyer panel | 1 buy · 19 pass |

## The idea

- What it is: A multi-tenant SaaS CRM (web, plus Android/iOS apps via Capacitor) with contacts, leads, deals, tasks and projects, finance and invoices, helpdesk/SLA, client portals, automation, API/webhooks, plugins (inventory, delivery, telephony, AI assistant) and AI sales agents/calls.
- Who it is for: Small and growing companies, apparently in Uzbekistan (Uzbek/Russian/English UI, Click and Payme payments, CBU exchange rates, Telegram integration), that currently use amoCRM-style tools. Exact target segment/industry is not stated in the repo and still needs the founder's answer.
- What it sells, at what price (from the plans migration, per month, yearly = 10x monthly; currency is read from the plan record, charges converted to UZS for Click/Payme):
  - Starter 89, up to 5 users, no AI minutes, 5 GB
  - Business 199, up to 15 users, 200 AI minutes, 25 GB (badged "most popular")
  - Enterprise 349 ("from", contact sales), up to 50 users, 500 AI minutes, 100 GB, SSO, voice calls
  - New signups start on a Starter trial.
- Where and how: Online SaaS, self-serve signup with trial, card/Click/Payme billing. Real-time voice (ASR/TTS/SIP) is not implemented; AI calls are sandbox simulations.
- Budget and constraints: Not stated. Open question for the founder (money, runway, team size, current paying customers).

### Founder answers (13 Nov 2026)

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

## Summary

**The business.** Nexio CRM is a multi-tenant cloud CRM (web, Android, iOS) in Uzbek, Russian and English for small and growing Uzbek companies that keep clients in Excel, Telegram chats, amoCRM or Bitrix24. It takes payment in so'm through Click and Payme.

**Verdict: Not yet.** Computed by `compile.py`, not written by hand.

| Number | Value | Check |
| --- | --- | --- |
| Contribution per paying account-month | $54.50 of $60 (91%) | passes |
| Year 1 operating profit | $5,770 (estimates; startup $3,000, cash needed $4,746) | passes |
| Simulated buyers who buy | 1 of 20 (5%); bar is 25% | **fails** |

The first two rest on estimated inputs: no real costs, team size, hosting bill or customer data exist, so a "pass" there is not evidence. The one failing check is the only one that touches buyers, and the buyers are simulated.

**What has to change, and which skill changes it.**
1. **No proof.** 13 of 19 simulated buyers who passed said they could not find anyone who uses it. Fix: 3 real paying customers who agree in writing to be references. That is the paid pilot in `founder/launch.md`, not another skill run.
2. **Price is above the buyers' range.** The first panel's acceptable range was $15.50 to $69.97 and Starter was $89. The founder chose to keep $89 and not run the $39 to $49 test. Advice (`/founder-pricing`): at least ask the 1 to 5 paying customers whether they would still pay it, and consider a $25 Lean plan (not built yet).
3. **Unmet board conditions.** The segment is now only partly named (shops and services, small and growing companies); customer data is rough (1 to 5 paying customers, none willing to be a reference); revenue is unknown; no measured CAC, no confirmed currency or pre-sale. Fix: real data from the founder and the pilot.

**What the board, the competitors and the panel agreed on.** All three board members voted FUND IF (average 4/10) and raised the same risks: no proof, no niche, selling unbuilt AI voice. The panel agreed on price and migration effort first, then, once those were addressed, on trust. Where they differ: the board saw breadth as a focus problem; the competitor map suggests the one real gap is a done-for-you migration plus flat pricing above about 6 users, and shamCRM is the closest and fastest rival.

**Biggest risk and the plan.** Trust: no references. Plan: the Founding-50 offer with free migration and a 30-day refund, in exchange for written reference permission.

**What the founder needs to start.** About $4,746 in the CFO's base case (estimate), $6,124 if volume is half of plan. Founder pay is assumed 0, and the model counts no salaries although the founder reports a team of 4 to 10: every $1,000 of monthly payroll adds about 19 accounts to the break-even. At 50 paying accounts the business earns about $1,975 a month, which is not a living; it needs several hundred accounts or a higher blended price.

**One thing to do this week.** Book 5 demos with owners of small companies from your own contacts, offer to migrate their data free, and ask each one to pay the first month. Do not spend on ads, and do not change the price page until you have heard what real owners say.

*The buyers in the panel are simulated and the numbers are projections built on estimates. Real customers and real quotes confirm or overturn them. This is not financial, legal or tax advice.*

## What the board said

### The vote

**3 FUND IF, 0 fund, 0 pass.** Average score **4.0 / 10** (Offers 4, Monopoly 4, Product 4).

Nobody passed, but nobody funded outright either. Every member says the idea is workable only if conditions are met, and every member points at the same missing facts.

### Risks raised by more than one member (most dangerous first)

1. **No proof yet.** No customer count, revenue, churn or case study is known. (Offers, Monopoly, Product)
2. **No narrow niche.** The target segment is unnamed; the product is a broad suite competing with amoCRM-style tools. (Offers, Monopoly, Product)
3. **Selling what is not built.** AI voice calls are sandbox simulations, but plans advertise AI minutes and voice calls. (Offers, Monopoly, Product)
4. **No repeatable way to find buyers.** Acquisition channel and CAC are unknown; self-serve signup is not a channel. (Offers, Monopoly)
5. **Price and currency unclear.** 89 / 199 / 349 per month, currency unconfirmed, may be high for small local firms. (Offers, Monopoly)
6. **Switching effort.** Moving data and retraining a team from amoCRM is a high-effort ask with no defined migration path or first-value moment. (Offers, Product)

### Where the members disagree or stress different things

- **Offers lens:** the offer is a feature list, not a result. Fix it with one measurable outcome, a done-for-you amoCRM migration and an affordable guarantee.
- **Monopoly lens:** nothing is shown to be 10x better, and a feature list can be copied in months. Wants a small market owned first and breadth cut (inventory, delivery, helpdesk).
- **Product lens:** the first five minutes are undefined. Wants a guided first run with fast import and one named "it works" moment.
- Offers treats breadth as stackable assets for bonuses; Monopoly and Product treat it as a focus problem.

### Conditions (checklist for the rest of the pack)

- [ ] Name one niche and one core job (consumer, competitors)
- [ ] State one measurable outcome with a time frame (offer)
- [ ] Show paying customers, revenue, churn, trial-to-paid (founder input, still unknown)
- [ ] Show a repeatable channel to the first 100 customers with a measured CAC (marketing, cfo)
- [ ] Confirm price currency and willingness to pay with real pre-sales, not trials alone (pricing, consumer)
- [ ] Package done-for-you amoCRM migration with a guarantee the CFO can afford (offer, cfo)
- [ ] Do not sell simulated AI voice or telephony as working until it works (offer, marketing)
- [ ] Ship a guided first run with amoCRM/Excel import and a first-value moment (ops, launch)
- [ ] Cut or hide modules that do not serve the chosen wedge (offer, ops)
- [ ] Disclose team size, runway and budget (cfo)

### The strongest version of this business

Not "a full CRM for everyone", which is what the repo describes. The strongest version the board can see is a **local-first, Telegram and Click/Payme-native CRM for one named type of Uzbek company that is leaving amoCRM**, sold with a done-for-you migration and a guarantee, using only features that work today. AI voice stays off the price list until it ships. Inventory, delivery and helpdesk stay as later plugins, not part of the pitch. This differs from the pitch in the repo, which is a broad suite with AI voice as a headline.

### Open questions the board could not answer

Customer count, revenue, churn, CAC, team size, runway, price currency, exact segment. The founder has to supply these. The consumer panel, pricing and CFO skills will test the rest.

## The competition

Public sources only, searched Nov 2026. Several pages could not be read directly (Capterra returned 403), so ratings and complaint themes come from search summaries. Treat them as leads to verify, not final facts. Pricing for Uzbekistan in UZS was not found for any competitor.

### 1. Who is there (most direct first)

| Competitor | Type | Comparable price | Their own line | Rating |
| --- | --- | --- | --- | --- |
| [Kommo (sold as amoCRM)](https://amocrm.com/buy/) | Direct | Base $15 / Advanced $25 / Enterprise $45 per user per month. 5 users on Base = $75/mo | not captured | 4.3 (198 reviews, [Capterra](https://www.capterra.com/p/120048/amoCRM/pricing/)) |
| [Bitrix24](https://www.bitrix24.com/prices/) | Direct | Basic about $49 to $69/mo for 5 users (sources conflict, [Capterra](https://www.capterra.com/p/113540/Bitrix24/pricing/) vs [Vendr](https://www.vendr.com/marketplace/bitrix24)); free tier exists | not captured | 4.2 (1,008 reviews, [Software Advice](https://www.softwareadvice.com/crm/bitrix24-profile/reviews/)) |
| [shamCRM](https://shamcrm.com/en/blog/24/vnedrenie-crm-sistemy-dlia-malogo-biznesa-posagovoe-rukovodstvo) | Direct, local | "Basic plan from 15 USD per month for 3 users. 14 days free." 5-user price not found | "a cloud CRM with AI agents for Central Asia" | not found |
| 1C:CRM | Indirect | "from 15,000,000 UZS" per a [shamCRM comparison](https://shamcrm.com/en/blog/24/vnedrenie-crm-sistemy-dlia-malogo-biznesa-posagovoe-rukovodstvo), unit and date not stated | n/a | not found |
| Spreadsheets, Telegram chats, paper | Substitute | free | n/a | n/a |

**Nexio for comparison** (from the repo): Starter 89 (5 users), Business 199 (15 users), Enterprise from 349 (50 users), per month, currency unconfirmed.

Market share, one survey: Bitrix24 59.5%, amoCRM 18.9%, Zendesk 13.5% in Uzbekistan ([wmtips](https://www.wmtips.com/technologies/crm/country/uz/) summary via search; the survey itself was not opened, so treat as unverified).

### 2. Price range (entry plan, 5 users, per month, USD)

- Lowest: Bitrix24 Basic about $49 (shamCRM unknown at 5 users; $15 for 3 users)
- Median of what is known: Kommo Base $75
- Highest: Nexio Starter $89 (flat); Bitrix24 Basic up to $69

At 15 users: Kommo Base $225, Advanced $375; Nexio Business $199 flat. Nexio is only cheaper once a team passes about 6 users on Kommo Base. Under that it is the most expensive entry plan in the table. This is arithmetic on list prices, not market data.

### 3. Positioning map (price vs. local fit)

```
                       strong local fit (UZ payments, Telegram, UZ language)
                                  |
                      shamCRM     |     Nexio
                      (cheap)     |     (priced above)
  low price ----------------------+------------------------- high price
                      Bitrix24    |     Kommo / amoCRM
                      (free tier) |     1C:CRM
                                  |
                       global tool, local fit added by integrators
```

Local-fit claims are vendor marketing and unverified for everyone but Nexio's own repo.

### 4. What customers complain about

| Theme | Where | Evidence |
| --- | --- | --- |
| Support is slow or inconsistent | Kommo, Bitrix24 | Kommo: described as the most common complaint ([Capterra](https://www.capterra.com/p/120048/Kommo/reviews/) summary). Bitrix24: "3-4 days to resolve issues" ([Software Advice](https://www.softwareadvice.com/crm/bitrix24-profile/reviews/)) |
| Price grows with users and add-ons | Kommo | "$15/user/month headline is misleading"; Zapier, WhatsApp API and add-ons can cost more than the seat ([Prospeo](https://prospeo.io/s/amocrm-pricing-reviews-pros-and-cons), an affiliate-style source) |
| Interface complicated, slow | Bitrix24 | "too clumsy once you start using it"; slow loading, laggy ([Capterra](https://www.capterra.com/p/113540/Bitrix24/reviews/?rating=1) summary) |
| Bad implementation by integrators | amoCRM (RU market) | Paid for setup, "system not fully configured" after two weeks; 30-day turnkey promised, deadlines missed ([crmindex](https://crmindex.ru/products/amo_crm/reviews)) |
| Integrations with 1C, local services are hard | amoCRM | [crmindex](https://crmindex.ru/products/amo_crm/reviews) |

All themes are thin to moderate. Counts were not available from the summaries.

### 5. The gap

Evidence supports a narrower gap than the repo suggests:

1. **Seat pricing vs. flat pricing.** Kommo charges per user and add-ons grow the bill. Nexio's flat plans win above about 6 users. This is a real gap but only for teams of 7 to 50.
2. **Done-for-you migration.** Complaints about integrators botching setup suggest a guaranteed, fixed-price migration is rare and valued. No competitor was found offering one, but this was not searched directly.
3. **Local payments and language.** shamCRM already claims Payme, Click, Oson and Telegram mini apps for Uzbekistan. Nexio's local-fit advantage is not unique. It is only a gap against Kommo and Bitrix24.
4. **No gap found in AI voice.** Nexio's is simulated, and shamCRM already advertises AI agents and call review.

### 6. The threat

- **shamCRM**, fastest to copy: local, already on Click/Payme/Telegram, $15 entry price, targets the same buyer.
- **Kommo** could cut price or add local payments, but has shown no sign of it in these sources.
- **Bitrix24** has the market share and a free tier. The risk is inertia, not a rival feature.

### Open questions this skill could not answer

Competitor prices in UZS, what amoCRM and Bitrix24 charge in Uzbekistan, whether anyone sells a fixed-price migration, and review counts per complaint theme. `/founder-consumer` and `/founder-pricing` will test what buyers actually say about price.

## The buyer panel

**1 buy · 19 pass** (5% buy) out of 20 simulated buyers. Seed 672231, so the same cards can be dealt again.

These are simulated buyers, not customers. Use this to find objections and weak spots, then confirm the big ones with real people before you spend.

### By segment

| group | buyers | buy rate |
| --- | ---: | ---: |
| Owner of a small shop or service business (3 to 6 staff) | 8 | 12% |
| Head of sales at a larger firm (15 to 50 staff) | 4 | 0%  (thin) |
| Owner or sales head of a growing company (7 to 15 staff) | 8 | 0% |

### By buying behaviour

| group | buyers | buy rate |
| --- | ---: | ---: |
| Unhappy amoCRM user | 3 | 33%  (thin) |
| Wants it set up for them | 2 | 0%  (thin) |
| Spreadsheet and Telegram user | 5 | 0%  (thin) |
| Price comparer | 3 | 0%  (thin) |
| Needs local payments | 2 | 0%  (thin) |
| Bitrix24 user | 3 | 0%  (thin) |
| Trusts a recommendation | 2 | 0%  (thin) |

### By income

| group | buyers | buy rate |
| --- | ---: | ---: |
| under $10,000 | 6 | 17%  (thin) |
| $24,000 and up | 7 | 0%  (thin) |
| $10,000 to $24,000 | 7 | 0%  (thin) |

### Why they pass

| reason | buyers | in their words |
| --- | ---: | --- |
| trust | 13 | "Free moving of my data is nice, but 25 dollars a month is a real chunk when I make about 500 a month, and nobody I know uses this. I would try the free 14 days at most, I would not pay yet." (P001) · "My team works in a spreadsheet and it costs me nothing. A new CRM with no customer references, and I would be paying monthly out of a $13,000 year, is a hard sell when amoCRM and Bitrix24 have trials too." (P003) |
| habit | 5 | "My team already lives in Excel and Telegram and it works well enough. I would maybe try the free 14 days, but I would not pay in the first month because getting 10+ salespeople to change how they report is the real work, not the price." (P002) · "I run everything in Excel and Telegram and it works well enough, and I have never paid for a CRM. I might look at the free trial, but paying dollars every month for something nobody I know uses is a hard sell." (P007) |
| price | 1 | "I make about $5,000 a year, so 25 dollars a month is 300 a year, that is a real chunk of my income for something Excel and Telegram already do for free. My staff will not change their habit just because I pay." (P018) |

### Why they buy

| reason | buyers | in their words |
| --- | ---: | --- |
| price | 1 | "amoCRM charges me per seat and the bill keeps growing, so a flat 25 dollars for contacts and deals only is exactly what I need. Free migration and a 14-day trial with my own clients means I lose nothing by trying it." (P017) |

### What would flip a no

- Another shop owner I know in Uzbekistan telling me they use it, plus someone doing the move in front of me and a lower price, around 10 to 15 dollars.
- Seeing a similar-sized Uzbek company that moved from Excel and Telegram and kept using it, plus a demo where my own spreadsheet is imported in one day and sales reports build themselves.
- Names of two or three companies like mine in Uzbekistan that use it, and a cheaper plan at around 15 dollars that I could test with my own clients.
- Two or three real shops like mine in Uzbekistan that I can phone, who have used it for months, plus the free move of my data from amoCRM done and shown to me before I pay anything.
- Two or three real Uzbek companies of my size I can call who use it, plus a way to export all my data any time.
- Another shop owner I know, or a Telegram channel I trust, tells me they use it, plus I try it free for 14 days and see my Telegram chats really come in.
- Another shop owner I know and trust showing me it working, plus the free migration done in a couple of days and a price of 15 dollars or less in so'm.
- A shop owner in Uzbekistan I can phone who uses it daily, plus a Lean price under about $15 after a trial where my own Telegram chats really work.
- Two or three real shops like mine in Uzbekistan I can call who already use it, plus a cheaper plan paid in so'm through Click.
- Two or three real Uzbek companies my size that I can call and that have used it for months, plus a clear side-by-side showing I pay less than I do now for my number of users.
- Seeing another company like mine in Uzbekistan using it daily, or you set it up and train my sales team in person during the trial and it actually replaces the end-of-day spreadsheet.
- A friend or another company owner I know already using it and telling me the migration from amoCRM went smoothly, or a live demo where you move a sample of my data.

Buyers say they would buy **1.0 times** in the first month on average.

20 buyers gave all four price answers. Run founder-pricing's van_westendorp.py on the answers folder.

## Pricing

Inputs: the 20-buyer simulated panel ([pricing-curve.md](pricing-curve.md)), competitor list prices ([competitors.md](competitors.md)). The CFO has not run yet, so there is no margin figure. Cost per customer is an open question and the price below must be checked against it in `/founder-cfo`.

**Founder decision (13 Nov):** keep Starter at $89 and do not run the $39 to $49 test. The analysis below stands as advice: $89 is above the panel's acceptable range, and 0 of 20 simulated buyers took it. The $89 price is only safe to keep if the real paying customers confirm they accept it. Ask the 1 to 5 current customers what they pay, and what they would have refused to pay.

### 1. The price

**Current Starter price, $89/mo for 5 users, is above the panel's acceptable range ($15.50 to $69.97) and is the most expensive 5-user entry plan in the competitor table.**

- Panel: OPP and IPP both at **$35.56**. Median "a bargain" $30, "getting expensive" $55, "too expensive" $90. 0 of 20 would buy at $89.
- Competitors, 5 users: Bitrix24 Basic about $49 to $69, Kommo Base $75, shamCRM from $15 for 3 users. Nexio at $89 is above all of them.
- Recommendation to test: **Starter at $39 to $49 per month for 5 users.** That is inside the acceptable range, near the least-resistance point, and at or below Bitrix24 and Kommo for the same team size.

Business ($199, 15 users) and Enterprise ($349, 50 users) were not priced by their own panel cohort, because only 8 buyers had 7 to 15 staff and they gave one set of answers each. Evidence so far: those buyers said "too expensive" between $100 and $300 for their whole team (P011: $160, P015: $100, P019: $300, P020: $100, P013: $120). Treat $199 as untested, not as wrong. At 15 users Kommo Base is $225, so $199 is only slightly below it.

### 2. The ladder

| Rung | Who | Price to test | Reason to step up |
| --- | --- | --- | --- |
| Lean (new) | 1 to 3 users, contacts, deals, Telegram only | $19 to $25 | The panel's small-shop owners said $15 to $30. A cheap rung competes with shamCRM ($15 for 3 users). |
| Starter | up to 5 users | $39 to $49 | Projects, tasks, quotes, invoices |
| Business | up to 15 users | $149 to $199 (test) | Client portal, finance, helpdesk, automation |
| Enterprise | up to 50 users | from $349, contact sales | SSO, voice (only when voice is real) |

The Lean rung is new: it does not exist in the plans migration (`backend/database/migrations/2026_11_10_000400_pricing_page_plans.php` has only Starter, Business and Enterprise, checked 13 Nov), so it must be built before it can be sold. It matches the objection "I only need contacts and deals" (P014, P017). Whether it cannibalises Starter is unknown.

### 3. The opening offer

- **Free data migration from amoCRM, Bitrix24 or Excel for the first 50 customers**, offered until a fixed end date. 16 of 20 buyers named free migration or import as part of what would flip them (counted from the answer files by keyword).
- A trial long enough to use: 14 days, extendable once. Several buyers asked for two weeks of real team use.
- No fake "was $X" anchors. Announce the new prices as the prices.

Do not put AI voice in any price line until it works.

### 4. What to test with real buyers

1. Two landing pages with Starter at **$39** and **$49** (same page otherwise). Measure sign-ups and trial starts.
2. A pre-sale: 10 to 20 firms from one niche pay a deposit for a founding-customer rate, with migration included. This also tests currency: show the price both in USD and in so'm.
3. Ask each trial user the four Van Westendorp questions in a survey. Real answers replace the simulated ones.

### 5. The panel's top price objections (verbatim, for marketing)

1. "89 dollars a month is more than 1,000 a year, and I earn about 6,000. That is a sixth of my income for a program, and my notebook and Telegram chats work for free." (P001)
2. "I pay about $15 per user elsewhere and cannot see why a flat $89 is better" (a pitch-stage objection, P010 and P015: the flat plan only wins above about 6 users)
3. "I might open the free trial, but $199 a month for my team is a lot to commit to when I'd have to move everything over myself." (P002)

### Limits

Simulated buyers lean towards price sensitivity when told to be sceptical, so $35.56 is a test point, not a verdict. Only 20 answers, thin per segment. The 0% buy rate in the first panel comes from buyers told to be unagreeable, with incomes set by guesses in `customer.json`. Real owner incomes in Uzbekistan were not researched; the founder judged the guessed incomes roughly right (13 Nov), and named small and growing companies as the first segments.

## The offer

Built from the first panel's objections (`founder/panel-v1/results.md`), the competitor complaints, the board's conditions and `founder/pricing.md`. The CFO has not run yet, so cost scores below are rough ranks, not money. Costs in dollars are open questions for `/founder-cfo`.

### 1. The problem list (in the buyer's words)

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

### 2. Solutions, scored (value to buyer 1 to 5, cost to deliver 1 to 5, 5 = costly)

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

### 3. The stack

- **The core:** "Your clients and deals out of Excel and Telegram chats and into one place your team actually uses, with your data moved for you."
- **Bonus 1, the migration:** free for the first 50 companies. Kills "it will take weeks". Needs a scope limit (contacts and deals only, up to a set number) once the CFO prices it.
- **Bonus 2, the Lean entry plan:** $25 for 3 users. Kills "I only need contacts and deals" and "too expensive".
- **Bonus 3, month to month with a refund:** no lock-in.
- **Guarantee:** "If your team is not using it after 30 days of paying, we refund that month in full." Cost = one month of revenue per claim; the claim rate is unknown. Model it with the CFO before it goes public.
- **Urgency, true only:** the free migration is limited to the first 50 companies. That is a real limit; show a real counter.
- **Name:** "Founding 50": free data move, a Lean or Starter plan, and a direct line to the founder.

### 4. Value equation, before and after (simulated judgement, 1 to 10)

| Element | Before | After | What moves it |
| --- | ---: | ---: | --- |
| Dream outcome (clients in one place, team uses it) | 5 | 6 | Core described by outcome, not modules |
| Likelihood it works | 3 | 5 | Guarantee, cancel any time. Still no references. |
| Time to result | 3 | 7 | Data already loaded when the trial starts |
| Effort and sacrifice | 2 | 7 | We do the migration; Lean plan is smaller |

### 5. Re-test

Same seed (672231), same 20 buyer cards, new pitch in `founder/pitch.md` (old one in `founder/pitch-v1.md`, old results in `founder/panel-v1/`).

Result: see the section below, added after the run.

### Re-test result (same seed 672231, same 20 buyer cards)

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

## The numbers

**Read this first: every input is an estimate.** No real costs, team, hosting bill or customer data exist in the project (production is a fixed-cost CloudPanel server; the model spreads it per account, which understates cost at low volume), so `numbers.json` holds model guesses (see `cfo-sources.md`). **The founder reported a team of 4 to 10 people, but the model's fixed costs ($750 a month) include only one part-time helper and no salaries.** Every extra $1,000 of monthly payroll adds about 19 accounts to the break-even ($1,000 divided by the $54.50 contribution). For example, $3,000 of payroll on top of $750 means about 69 paying accounts to break even, not 14. That figure is an example, not data: real payroll is unknown. The model also assumed a blended price of $60 built from the test prices; the founder is keeping Starter at $89, so the real blend would be higher, but fewer buyers accept that price in the panel. The tool's "a day" columns mean "a month" here: the unit is one paying account for one month. An accountant should check structure, payroll and tax before money moves. This is not financial, tax or legal advice.

- **The margin:** each paying account-month leaves $54.50 of $60 (91%). At 50 paying accounts the profit margin is 66%. That is a software margin; it is not where the risk is.
- **The line to watch: volume.** Break-even is 14 paying accounts, and the plan assumes 50 by month 12. At half the volume (25 accounts) year 1 loses $1,615 and the cash need rises to $6,124. At 30% of plan (15 accounts) it loses $4,569. The ramp is a pure assumption, and the buyer panel (0 of 20, then 1 of 20) cannot support or contradict it.
- **Cash:** about $4,746 before it pays for itself in the base case; $6,124 at half volume. Founder pay is set to 0. Any salary adds directly to fixed costs.
- **The scale problem:** 50 accounts is $3,000 of revenue a month and about $1,975 of monthly profit at the end of year 1. That is not a living for a team. The numbers say this business only works with several hundred accounts or a much higher blended price.
- **Three ways to improve the margin** (run with the tool, not guessed):
  1. Blended price $60 to $70 (push accounts to Business): year 1 profit $5,770 to $8,480, break-even 14 to 12 accounts.
  2. Hold the blended price at $60 and keep the Lean plan small. If the mix drifts to Starter-only ($49), year 1 profit falls to $2,788 and the startup spend is not earned back within the year (-$212).
  3. Get to plan volume faster. This is the biggest lever. Each doubling of early volume matters more than any cost cut, because fixed costs are only $750 a month.
- **Board conditions about money:** the board asked for "price currency and willingness to pay confirmed with real pre-sales" and "paying customers and retention numbers". Neither is met: there are no real numbers yet. The refund guarantee in the offer has no cost in this model: one refunded month costs about $60 per claim, and the claim rate is unknown. The migration promise is the largest startup line ($1,500 for 50 accounts) and rests on a 3-hour-per-account guess.

---

## Unit economics: Nexio CRM (ESTIMATES - no real cost, team or customer data was provided)

Every number below comes from the input file. Nothing is looked up or guessed.

### One paying account-month

| line | per paying account-month |
| --- | ---: |
| Price | $60.00 |
| Payment fees (Click/Payme/card), est. 2.5% of 60 | -$1.50 |
| Server share, storage, email/Telegram per account (est.; real server cost is fixed) | -$3.00 |
| Support and AI tokens per account (est.) | -$1.00 |
| **Contribution** (what each paying account-month leaves to pay the fixed costs) | **$54.50** (91%) |

### The margin that matters

Fixed costs: $750 a month (Part-time support/onboarding helper (est.) $500, Tools, domain, monitoring, SMS base (est.) $150, Accountant (est.) $100, Founder pay (OPEN INPUT, set to 0) $0).

- **Break-even: 14 paying account-months a day.** Below that you lose money every month.
- **Profit margin at your plan** (50 a day): **66%** of every sale, after every cost.

### Year 1, month by month

| month | paying account-months a day | revenue | profit | cumulative (after $3,000 startup) |
| ---: | ---: | ---: | ---: | ---: |
| 1 | 2 | $120 | -$641 | -$3,641 |
| 2 | 4 | $240 | -$532 | -$4,173 |
| 3 | 7 | $420 | -$368 | -$4,542 |
| 4 | 10 | $600 | -$205 | -$4,746 |
| 5 | 14 | $840 | $13 | -$4,734 |
| 6 | 18 | $1,080 | $231 | -$4,502 |
| 7 | 23 | $1,380 | $504 | -$3,999 |
| 8 | 28 | $1,680 | $776 | -$3,223 |
| 9 | 33 | $1,980 | $1,048 | -$2,174 |
| 10 | 38 | $2,280 | $1,321 | -$854 |
| 11 | 44 | $2,640 | $1,648 | $794 |
| 12 | 50 | $3,000 | $1,975 | $2,770 |

- **Year 1 operating profit: $5,770** on $16,260 of revenue.
- After the $3,000 startup spend: $2,770.
- Startup money earned back: month 11.
- Cash you need before it pays for itself: **$4,746**.

### What if

| scenario | margin at plan | break-even a day | year 1 profit |
| --- | ---: | ---: | ---: |
| Base plan | 66% | 14 | $5,770 |
| Price -10% | 62% | 16 | $4,144 |
| Volume -20% | 60% | 14 | $2,816 |
| Unit costs +15% | 64% | 14 | $5,546 |

No red flags in these numbers. They are only as good as the inputs: check every cost against a real quote.

## Marketing

Evidence used: the two buyer panels (`founder/panel-v1/results.md`, `founder/panel/results.md`), `competitors.md`, `offer.md`, `pricing.md`, `cfo.md`. The CFO inputs are estimates, so the budget below is a ceiling to check, not a plan to trust.

**What the evidence says about marketing.** After the new offer, 13 of 19 simulated passers said "nobody I know uses it". Price and migration objections mostly went away. So the job of marketing is not to explain features or shout about price. It is to **get a handful of real, named, reachable customers**, then put them in front of the next buyer. Everything below is built around that.

**A price claim to avoid.** Search results show amoCRM priced "from 499 RUB" per user per month in Russian-language sources ([shamCRM comparison](https://shamcrm.com/en/blog/24/vnedrenie-crm-sistemy-dlia-malogo-biznesa-posagovoe-rukovodstvo), unverified) while the global Kommo list is $15 per user ([Kommo](https://amocrm.com/buy/)). If the local price is about 499 RUB, then "our flat price is cheaper than amoCRM" is false for small teams. Do not run that claim until the real local amoCRM, Bitrix24 and shamCRM prices are checked.

### 1. Positioning

Panel evidence is thin: the only buyer in the second run was a small shop owner who paid amoCRM per seat and wanted contacts, deals and Telegram only (P017). One buyer is not a segment; treat this as the first thing to test.

Three versions:

1. **Migration-led (recommended).** For small Uzbek companies whose clients are stuck in Excel, Telegram chats or an old CRM, Nexio is the CRM we set up for you: we move your clients over, your team starts with real data on day one, and you pay in so'm. *Supported by:* 16 of 20 first-run buyers named free migration as a flip condition, and amoCRM complaints centre on bad implementations.
2. **Telegram-first.** For owners who run sales in Telegram, Nexio is the CRM where the chat and the deal live together. *Weak:* shamCRM already advertises Telegram and Payme/Click, and the Telegram connection was a doubt for P008 ("proof Telegram works").
3. **Flat price.** For teams of 7 to 15 tired of per-seat bills. *Weak:* see the price warning above, and panel buyers at this size said even $100 for the team is a lot.

**Recommendation: version 1.** It matches the objection that moved most, and the competitor map shows nobody offering a fixed-price, done-for-you migration (not searched exhaustively).

### 2. Channels

The audience is owners and sales heads in Uzbekistan. Cost figures are not researched; none are given as facts.

| Channel | Why it fits | How you will know it worked |
| --- | --- | --- |
| **Founder-led outreach and live demos** (calls, visits, Telegram DMs to owners) | Trust is the wall; a person you can talk to beats an ad | Demos booked per 20 contacted; trials started |
| **Referral partners**: accountants, 1C integrators, web and Telegram-bot agencies who already serve small firms | They are the "someone I trust" the panel asked for | Warm intros per partner; accounts that arrive via a partner |
| **Telegram groups and channels for local business owners** (answer questions, show the migration, no spam) | Where the target already spends time; matches Telegram-heavy habits | Replies and DMs from owners, not likes |

Not doing yet, and why: paid social and search (no proof to put on the landing page, so clicks would hit the trust wall), influencers (disclosure rules and no budget data), cold email (low trust for this buyer).

### 3. 30-day calendar (relative days; no launch date was given)

D = launch day. All copy is a draft to be translated and checked by a native speaker in Uzbek and Russian.

| Day | Channel | What goes out |
| --- | --- | --- |
| D-14 | Outreach | Make a list of 60 target companies (shops and service firms with 3 to 15 staff) |
| D-13 | Partners | Contact 10 accountants and agencies, ask for a 15-minute call |
| D-12 | Product | Record a 2-minute screen video: an Excel file turning into a pipeline in Nexio (no fake data claims; label sample data as sample) |
| D-11 | Telegram | Join 5 owner groups, read the rules, introduce yourself, answer questions only |
| D-10 | Outreach | Message the first 20 companies: offer a free migration demo on a sample of their data |
| D-9 to D-8 | Demos | Run demos. Write down every objection verbatim |
| D-7 | Partners | Follow up with partners, offer a referral arrangement (disclose any payment) |
| D-6 | Landing page | Page with price, the migration offer, the 30-day refund, and the sentence "We do not yet have customer references" |
| D-5 | Outreach | Message the next 20 companies |
| D-4 | Telegram | Post the migration video in groups where the rules allow it |
| D-3 | Demos | Offer 5 owners a free pilot in exchange for permission to be named as a reference **only if they agree in writing** |
| D-2 | Product | Check migration tooling on 3 real spreadsheets |
| D-1 | Prep | Confirm the Founding 50 counter is real and shows the true number left |
| **D** | Launch | Landing page live, partner messages out, first 20 companies get the offer |
| D+1 to D+3 | Outreach | Demo every reply within 24 hours |
| D+4 | Telegram | A post on what changed after the first migration (only if a real one happened) |
| D+7 | Review | Count demos, trials, migrations done. Compare against the three numbers below |
| D+8 to D+10 | Outreach | Next 40 companies, message adjusted by what people objected to |
| D+11 | Partners | Ask partners for a second round of intros |
| D+14 | Content | Publish a short, honest "how we moved a 300-contact spreadsheet" with the customer's written permission |
| D+16 | Review | Stop channels that produced no trials; double the ones that did |

When there is nothing new: show how a migration works step by step, answer one common question from the DMs, show a Telegram chat turning into a deal.

### 4. Ten hooks (drafts, English plus Russian)

Each answers one panel objection. None is a testimonial; none quotes a simulated buyer as a customer.

1. **"Moving will take weeks."** "We move your clients in for you. Your team opens Nexio and the data is already there." / «Мы сами перенесём ваших клиентов. Команда откроет Nexio — данные уже на месте.»
2. **"Nobody I know uses it."** "We are new. We will tell you exactly who uses it, and you can call them. We do not have a list yet." (Say this only while true.) / «Мы новые. Пока у нас нет списка клиентов — не будем делать вид.»
3. **"Excel and Telegram work fine."** "Keep Telegram. Nexio puts the chat and the deal in one place." / «Telegram остаётся. В Nexio чат и сделка в одном месте.»
4. **"I only need contacts and deals."** (Run this only once the Lean plan is built.) "Lean plan: contacts, deals, Telegram. 25 dollars a month, 3 users." / «Lean: контакты, сделки, Telegram. 25 долларов в месяц, 3 пользователя.»
5. **"My team won't learn another tool."** "Your team starts with their own clients already inside." / «Команда начинает со своими клиентами внутри.»
6. **"What if it fails?"** "If your team is not using it after 30 days of paying, we refund that month." (Check that the refund rule is real before publishing.)
7. **"Will the company still exist?"** "Month to month, cancel any time. Ask us how to export your clients." (Check export exists.)
8. **"Do I pay in dollars?"** "Pay in so'm with Click or Payme." / «Платите в сумах через Click или Payme.»
9. **"Is the AI voice real?"** "AI voice is coming soon. It is not in the price today." (Turns a weakness into honesty. The founder expects real-time voice within 3 months, so say "coming soon", not a date, unless you can keep the date. Do not sell it as working.)
10. **"Support is slow."** "In the first 50 companies, the founder answers in Telegram." (Only if the founder will.)

### 5. Budget and the most you can pay for a customer

- **Budget:** the CFO estimate lists $1,000 for launch ads and content. Nothing above suggests spending it on paid ads before there are references. A better use of that money is the free migrations and partner referral fees. That split is a suggestion; no budget was given by the founder.
- **Most you can pay to win a customer.** Contribution per paying account-month is **$54.50** (from `cfo.md`, an estimate). Churn is unknown, so the lifetime is unknown. Take a cautious rule: be paid back within 3 months, so the ceiling is 3 x $54.50 = **$163.50 per paying account**. Include the free migration (estimated $30 per account: 3 hours at $10) in that, leaving about **$133 for everything else**.
- **Funnel check (do not trust):** the panel gave 1 purchase from 20 simulated buyers in the second run. If the real rate from a demo to a paying account were 1 in 20, a $133 budget per account means about $6.65 per demo. A demo run by the founder costs time, not cash, which is why direct outreach is the first channel.

### 6. Three numbers to watch weekly

| Number | Change something if |
| --- | --- |
| Demos booked per 20 companies contacted | below 2 |
| Trials that finish a migration | below half of trials started |
| Paying accounts after 30 days | zero after the first 10 trials |

Add a fourth once there are customers: how many agree, in writing, to be a named reference.

### Rules followed

No fake reviews, follower counts or logos. Panel quotes are research, not testimonials. Disclose any paid partnership. Follow Uzbek advertising rules for claims; they were not researched. Not legal advice.

## Brand

This is an **audit, not a rename.** The product is already called Nexio CRM, "by Nexio Lab", and has a logo, colours and an app icon. The pack's job here is to check the name for risk, tie the voice to the positioning from `marketing.md`, and fix what the audit finds. No image tool was used; the logo section is a brief.

### 1. What exists today (from the repo)

- **Name:** Nexio CRM, by Nexio Lab.
- **Primary colour:** `#2563eb` (blue); dark theme background `#0B1220`; success `#10b981`; warning `#f59e0b`; soft surfaces in light and dark.
- **Logo (from the SVG in the repo):** the word "Nexio" in bold navy `#092d62`, "CRM" in a light weight, and "BY NEXIO LAB" in grey `#64748b`, set in Arial.
- **App icon files:** in `resources/` (icon, foreground, background, splash) and `public/`.
- **Languages:** Uzbek, Russian, English.

### 2. Name check: the risks, honestly

"Nexio" is a short, invented-sounding word that is easy to say in Uzbek, Russian and English. That is its strength. The risks:

- **It is generic.** "Nexus"-style names are very common in software. Expect other companies named Nexio or close to it. I could not search trademark registers from here, so this is **unchecked**, not cleared.
- **Domains.** A DNS lookup (public, no registration check) returned: `nexio.com` is taken (Cloudflare name servers); `nexiolab.uz` has name servers (taken); `nexiolab.com` is parked/for sale (Afternic name servers); `nexio.uz`, `nexiocrm.uz` and `nexiocrm.com` returned no name servers. **No name servers does not prove a domain is free** (a registered domain can have none, and `.uz` lookups can fail). Confirm with a registrar's search before relying on it.
- **Confusion with competitors:** none of the names in `competitors.md` (Kommo, amoCRM, Bitrix24, shamCRM, 1C) is close. No conflict found there.

Founder answers (13 Nov): the founder owns the domains; the "Nexio" trademark is not registered. Registering it, after the register searches below, is still open.

What you must finish yourself (a trademark lawyer confirms; nothing here is registered or cleared):

| Check | Where | Class |
| --- | --- | --- |
| Trademark, international | [WIPO Global Brand Database](https://branddb.wipo.int/) | Nice classes 9 (software), 42 (SaaS) |
| Trademark, Uzbekistan | Intellectual Property Agency of the Republic of Uzbekistan (find the current official register; I did not verify its address) | classes 9 and 42 |
| Trademark, EU / US | [EUIPO](https://euipo.europa.eu/eSearch/), [USPTO](https://tmsearch.uspto.gov/) | classes 9 and 42, only if selling there |
| Domains | any registrar search for nexio.uz, nexiocrm.uz, nexiocrm.com | n/a |
| Handles | Telegram (the key one here), Instagram, YouTube, X | n/a |

**Recommendation:** keep the name for now, and do the trademark search **before** spending on ads or printing anything. If the search turns up a conflict in class 9 or 42, the fallback is a descriptive-evocative name; do not pick one before the search results exist.

### 3. Promise, tagline, voice

**One-line promise:** "Your clients, moved in for you, in one place your team actually uses." Drawn from the migration-led positioning in `marketing.md`; it replaces the feature list (CRM, finance, helpdesk, inventory, AI) the repo currently leads with.

**Tagline options:**
1. "Your clients, already inside." / «Ваши клиенты уже внутри.»
2. "Move in. We carry the boxes." / «Переезжайте. Коробки несём мы.»
3. "A CRM your team will actually open." / «CRM, которую команда реально откроет.»

**Voice, three adjectives:**

| Adjective | Do | Don't |
| --- | --- | --- |
| **Plain** | Short sentences, the word the owner would use | Say "synergy", "ecosystem", "AI-powered" |
| **Honest** | Say what is not ready ("no AI voice yet", "no references yet") | Imply customers, logos or ratings that do not exist |
| **Helpful** | Offer to do the migration, answer in Telegram | Make the buyer figure it out |

**Before / after (from `pitch.md`):**
- Before: "Nexio CRM is a cloud CRM in Uzbek, Russian and English for small and growing companies. It keeps contacts, leads, deals, tasks, quotes and invoices in one place..."
- After: "Your clients are in Excel and Telegram. We move them into Nexio for you, free, so your team starts with real data on day one."

### 4. The look: audit and fixes

**Colour contrast checks (WCAG AA needs 4.5:1 for normal text, 3:1 for large text and UI):**

| Pair | Ratio | Verdict |
| --- | ---: | --- |
| White text on primary blue `#2563eb` | 5.17 | Passes AA |
| Navy `#092d62` text on white | 13.43 | Passes AAA |
| Grey `#64748b` text on white | 4.76 | Passes AA (small "BY NEXIO LAB" is borderline in size) |
| Primary blue `#2563eb` on dark `#0B1220` (the token alone) | 3.62 | Looks like a fail, but the dark theme already remaps `text-primary` to `#7db4fb` in `src/styles/index.css`, which passes. **Not a real problem; my first audit missed the override** |
| White text on success green `#10b981` | 2.54 | **Fails.** Do not put white text on this green; use dark text or a darker green |
| Amber `#f59e0b` with dark `#111827` text | 8.26 | Passes |

One fix, done in code on 13 Nov: the success green used for text was `#10b981` (2.54 on white, 2.41 on the light badge background). In the light theme `--color-success` is now `#047857` (5.48 on white, 5.21 on the badge); the dark theme keeps `#10b981`. Where it appears in the app: `text-success` badges and deltas, and `bg-success` dots and bars, which now look darker in the light theme. Never put white text on a `#10b981` button.

**Type:** the logo uses Arial, a system font, which any platform can render but looks generic. The rest of the app uses `var(--font-sans)`; check what it resolves to. A free upgrade path: **Inter** (SIL Open Font License, from Google Fonts, supports Cyrillic and Latin, including the Uzbek Latin letters). Verify Uzbek letters (oʻ, gʻ) render correctly before adopting any font.

**Logo brief:** it must read at 32 px on an app icon, so the icon should be a single letter mark ("N") or a simple shape, not the full wordmark. It must work in light and dark theme, in one colour (for stamps and invoices), and in Cyrillic documents next to Russian text. Avoid: arrows, "connection node" clichés that every CRM uses, and anything resembling a competitor's mark. Any generated concept is a concept, not a finished logo.

**First five touchpoints:**
1. **Landing page:** the promise, the migration offer, the plain sentence "we do not yet have customer references".
2. **Signup and first run:** import step first (Excel, Telegram, amoCRM, Bitrix24), then the pipeline view.
3. **Confirmation and invoice emails:** in the buyer's language, in so'm if they pay through Click or Payme, with the 30-day refund line.
4. **First Telegram post:** the migration video from `marketing.md`.
5. **Reply to the first complaint:** same-day, a person, an apology only if deserved, a fix date.

### Rules followed

No competitor name, logo, colour or tagline copied. No font suggested without a free licence. Not legal advice on trademarks.

## Operations

Facts about how the system runs come from the repo's `docs/OPERATIONS.md` and `docs/SECURITY.md`. Prices, wages and legal requirements were **not** researched or invented; they are listed as quotes or checks the founder must get. This is a one-person-to-small-team SaaS, so "operations" means: keep it up, move customers in, answer them, get paid.

**Production host (confirmed from the repo).** `docs/ARCHITECTURE.md` says the Cloudflare Worker and D1 code (`api/`, `wrangler.toml`, `migrations/`) is a legacy API that is *not used in production*. Production is the Laravel app on a CloudPanel server (`crmb.nexiolab.uz`, deployed with `deploy.sh`, PM2 for the frontend). So hosting is a **mostly fixed server bill**, not a cost per account. The CFO model spreads it as part of the $3 per account, which understates the cost at low volume. The real server price is still unknown: get it, add it as a fixed cost and re-run `/founder-cfo`.

### 1. The daily cycle

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

### 2. Suppliers and services (get quotes; no prices claimed)

| Need | Options to quote | Notes |
| --- | --- | --- |
| Server hosting | The current CloudPanel server provider; a local data-centre provider | Ask: monthly price, backups, location of the servers (see section 6, data location) |
| Payment collection | Click and Payme merchant accounts, a card acquirer | Ask: commission, payout delay, onboarding documents. The CFO used 2.5% as a guess |
| SMS / OTP | A local SMS gateway | Ask: price per SMS, sender-name approval time |
| Email sending | A transactional email provider | Ask: free tier, domain verification |
| Error monitoring | Sentry (the app supports it via `SENTRY_LARAVEL_DSN`) | Free tier exists; check current limits |
| Off-server backups | Object storage (S3-compatible, R2 or MinIO supported by the app) | The repo says a backup on the same disk is not disaster recovery |

If real prices differ from `numbers.json` (hosting $3 per account, payment 2.5%), update the file and re-run `/founder-cfo`.

### 3. People

Nobody is hired in the data. The CFO's estimate assumed one part-time support/onboarding helper at $500 a month. Weekly rota for the slow first month:

| Role | Hours | What |
| --- | --- | --- |
| Founder | variable | Demos, migrations until a helper is trained, deploys, billing issues |
| Support/onboarding helper (not hired) | part-time | Telegram inbox, migrations, follow-ups |

Wage assumption: from the CFO file (estimate). Local payroll costs and taxes were not researched; ask the accountant.

### 4. Routines

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

### 5. Tools (smallest stack)

The app itself is the CRM for the business's own customers (dogfooding is cheap and credible). Beyond that: Telegram for support, a spreadsheet for the migration log, Sentry for errors, an accounting tool the accountant already uses. Prices not researched.

### 6. Licences, permits, data rules: a checklist to confirm

Rules change and differ by case. Confirm each with the authority or a local lawyer or accountant. Not legal advice. The official legal database is [lex.uz](https://lex.uz/); the IT Park programme is at [it-park.uz](https://it-park.uz/).

- [ ] **Business registration** (individual entrepreneur or a company) and tax registration.
- [ ] **Merchant agreements** with Click and Payme; their onboarding documents and any category limits for software subscriptions.
- [ ] **Personal data law.** The CRM stores customers' customers' personal data (names, phones, chats). Uzbekistan has a personal data law with requirements about where citizens' data is stored and processed. Check this one with a lawyer *before* choosing between servers in Uzbekistan and abroad. I did not verify the current text.
- [ ] **Privacy policy and terms of service** in Uzbek and Russian. The repo history mentions a privacy policy rewrite; have it reviewed.
- [ ] **IT Park resident status.** Ask whether it applies and what tax treatment it gives; confirm on the official page.
- [ ] **Telegram bot and messaging terms**, and **call recording consent** for the telephony plugin (recordings are stored; the repo restricts who can read them).
- [ ] **Invoices and VAT** for business customers; ask the accountant what to charge and how to issue invoices in so'm.
- [ ] **Insurance** (professional liability or cyber): ask a broker whether it is worth it at this size.

### 7. Risk register

| # | Risk | Likelihood | Impact | Plan |
| --- | --- | --- | --- | --- |
| 1 | No customer will act as a reference | High (no customers yet) | High (trust is the main wall) | Founding-50 pilots with written permission; free migration in return |
| 2 | A migration loses or corrupts customer data | Medium | High | Staging import, spot-check 10 records, keep original file, written OK |
| 3 | Backups only on the same server (confirmed by the founder, 13 Nov) | High | Very high | Do this first: copy `storage/app/backups` off-server (S3 or R2) now. 1 to 5 customers' data is already at risk |
| 4 | Server bill is fixed but the CFO model spreads it per account | High | Low | Get the real monthly server price, add it as a fixed cost, re-run the CFO |
| 5 | Key person (the founder) unavailable | Medium | High | Runbook above, a second person with server access, the standby DB feature already in the repo |
| 6 | Payment provider holds or rejects the account | Low to medium | High | Apply early; keep card as a second path |
| 7 | AI voice or telephony oversold | Medium | High | Not in the price; label as "not ready" until real-time voice exists (the repo says it is a sandbox simulation) |
| 8 | Support falls behind during the first 50 | High | Medium | Same-day reply rule; cap trials per week |
| 9 | Personal-data rules breached (servers are in Uzbekistan, which helps, but no lawyer has reviewed it) | Unknown | High | Lawyer review before launch |
| 10 | Slow growth: fewer than 14 paying accounts | High (per CFO, break-even) | High | Watch the three weekly numbers; stop spend if no trials after 10 demos |

### Open questions for the founder

The real monthly server price, who handles support, who else can deploy, whether IT Park applies, real Click/Payme rates.

## Launch plan

Today is Thursday 12 Nov 2026. No target date was given, so the dates below are a **proposal**: start Monday 16 Nov, public launch **Monday 14 Dec 2026**. Lead times for payment merchant approval, the privacy-law check and any hire are unknown (see `ops.md`); if they are longer than two weeks, the whole plan slides. The only owner in the data is the founder. A helper is not hired.

### 1. Test before you spend: the paid pilot

The business is a subscription with a trust problem, so the cheapest real test is **paid founding accounts**, not a waitlist. A waitlist costs the buyer nothing and says little here.

**The test:** offer the Founding-50 deal (free migration, Lean $25 or Starter $49, 30-day refund) to companies from the outreach list in `marketing.md`, and take the first month's payment up front through Click, Payme or card.

**Success line, written before running it:**
- From about 10 demos: **at least 3 paying accounts** by Friday 11 Dec.
- **At least 2 of those agree in writing to be named as a reference.**
- **At least 2 migrations finished** with the customer confirming the data is correct.

**Compared with the panel:** the simulated buyers gave 0 of 20, then 1 of 20 (5%). Simulated buyers lean agreeable but this run was told to be sceptical, so neither number predicts real conversion. If real buyers fall well below 3 of 10 demos, trust the real buyers, go back to `/founder-offer` and `/founder-pricing`, and do not start paid ads. If 3 or more pay, scale the same outreach rather than changing it.

**Costs avoided if the test fails:** the $1,000 launch-ad budget in the CFO file is not spent until the gate passes. The migration cost (about $30 per account, estimate) is the real spend.

### 2. The countdown

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

### 3. Launch day: Monday 14 Dec (Tashkent time)

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

### 4. First 30 days

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

### Open questions

Who is the second person on the team; how long Click/Payme approval takes; whether the data law requires local servers; whether a 3-hour migration holds on real files.

_The panel is simulated buyers and the numbers are projections from your inputs. Confirm demand with real customers and costs with real quotes before you spend. Not financial, legal or tax advice._
