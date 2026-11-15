# Pricing: Nexio CRM

Inputs: the 20-buyer panel (`founder/panel/`, simulated), `founder/pricing-curve.md`, the competitor map (`founder/competitors.md`) and the plan prices in the repo. **The CFO has not run yet, so margin and break-even are not computed here.** Prices below are what to test; `/founder-cfo` must confirm the floor before any of them is used.

## What buyers said

Acceptable range from the panel: **$8.01 to $40.14 a month**. Least resistance (OPP) $10.14; indifference point (IPP) $19.98. Median answers: too cheap $5, a bargain $15, getting expensive $30, too expensive $60.
Starter at **$89 is above the range, and above the "too expensive" point of 19 of 20 buyers** (their "too expensive" answers run $40 to $89; only one buyer's reached $89).
Caveat: the 20 buyers are simulated, most of them one-person or very small businesses, and the pitch asked them to value a 5-user plan. A bigger firm may sit higher.

## What competitors charge (about 5 users, per month)

| Option | Price |
| --- | --- |
| shamCRM | from $15 for 3 users (own blog; conflicts with other figures) |
| Bitrix24 Basic | $49 annual / $69 monthly, flat for 5 users. Free plan for 1 to 2 users |
| Zoho / Pipedrive | about $70 for 5 |
| Kommo (amoCRM) Base | about $75 for 5 |
| **Nexio Starter** | **$89** |

Nexio is the dearest at 5 users. At 15 users Nexio Business is $199; Bitrix24 Standard is $99 for 50 users.

## The price

**Do not sell Starter at $89 as the entry price.** Evidence: it is 2.2 times the panel's top of range and above every competitor at the same size. Nothing in the research says Nexio is worth a premium: no references, voice not built.

Recommended to test (inside the buyers' range and level with the market):

- **Entry: $29 a month for up to 3 users, CRM only.** This is inside the panel range, close to the $30 "getting expensive" median, and the repo's self-serve custom plan already prices 2 users at $29 ($9 base + $10 per user) and 1 user at $19. So the entry plan needs no new pricing engine, only a visible plan card. (Founder to confirm that the custom plan's CRM-only price is what the CFO can afford.)
- **Starter: test $49 for 5 users**, level with Bitrix24 Basic, instead of $89. The founder earlier chose to keep $89 and not test $39 to $49; the panel and the competitor prices say this is the biggest barrier, so it is the first thing to test with real buyers.
- Business ($199, 15 users) and Enterprise ($349, 50 users): leave until the entry price is proven; they are not yet comparable to Bitrix24's $99 for 50 users and need a reason (finance, helpdesk, portal included) to be believed.

## The ladder

| Rung | For | Price to test | Reason to step up |
| --- | --- | --- | --- |
| Lean | 1 to 3 people, owners leaving Excel and Telegram | $29 | Starts where the buyer is; migration included |
| Team | up to 5 people | $49 | More users, projects, Telegram inbox |
| Business | up to 15 | $199 (unchanged for now) | Finance, helpdesk, automations, client portal |
| Enterprise | 25 to 50 | from $349 | SSO, API, voice when built |

Voice and AI calls stay off every rung until they work.

## The opening offer

**Founding 50**, ends **31 Jan 2027**: the first 50 companies get the Lean or Team price locked for 12 months, free data migration from Excel, Telegram or amoCRM/Bitrix24 by the Nexio team, and a 30-day money-back refund. In return each agrees in writing to be named as a reference after 60 days. No "was" price; the locked price is the real price.

## What to test with real buyers

Run two prices for the same entry plan to 20 owners each from the founder's own contacts: **$19 versus $29** (Lean), then **$49 versus $89** for 5 users. Method: a one-page price sheet or Telegram message with a pre-sale link (first month paid through Click or Payme, refundable). Success bar to set before testing: for example 5 paid out of 20 contacts at one price.

## The panel's top three price objections (verbatim)

1. "$89 a month is more than a whole month of what I take home for some weeks, and Bitrix24 has a free plan. I would only try the free trial if someone sets it up for me, and I would not pay after." (P002)
2. "I make $7,000 a year, so $89 a month is a big bite, and I run everything from Telegram and a notebook just fine. I don't know a single company that uses Nexio and my friends haven't mentioned it." (P004)
3. "My Excel works and I know every row of it. $89 a month is more than a tenth of what I earn in a year, for something that makes me move everything and retrain people." (P001)

## CFO check (added after `/founder-cfo`)

At a $45 blended price (estimates): contribution $40.40 per account (90%), break-even 20 accounts. At a $29 blended price break-even is 32 accounts and year 1 loses $4,360; at $55 break-even is 16 and year 1 earns $840. So $29 works only as an entry door to the $49 plan, not as the whole business. The buyers' range ($8 to $25 once $29 was shown) and the CFO's floor do not overlap: price alone cannot close this gap; trust and volume must.

## Open

Margin at $29, $49 and $89 and the break-even need `/founder-cfo` (real server cost, support time, payment fees). If the CFO finds $29 below the cost to serve, the entry rung moves up and the free-migration promise shrinks accordingly.
