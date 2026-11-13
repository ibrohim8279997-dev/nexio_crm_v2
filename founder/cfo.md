# CFO's note

**Read this first: every input is an estimate.** No real costs, team, hosting bill or customer data exist in the project (production is a fixed-cost CloudPanel server; the model spreads it per account, which understates cost at low volume), so `numbers.json` holds model guesses (see `cfo-sources.md`). The tool's "a day" columns mean "a month" here: the unit is one paying account for one month. An accountant should check structure, payroll and tax before money moves. This is not financial, tax or legal advice.

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

# Unit economics: Nexio CRM (ESTIMATES - no real cost, team or customer data was provided)

Every number below comes from the input file. Nothing is looked up or guessed.

## One paying account-month

| line | per paying account-month |
| --- | ---: |
| Price | $60.00 |
| Payment fees (Click/Payme/card), est. 2.5% of 60 | -$1.50 |
| Server share, storage, email/Telegram per account (est.; real server cost is fixed) | -$3.00 |
| Support and AI tokens per account (est.) | -$1.00 |
| **Contribution** (what each paying account-month leaves to pay the fixed costs) | **$54.50** (91%) |

## The margin that matters

Fixed costs: $750 a month (Part-time support/onboarding helper (est.) $500, Tools, domain, monitoring, SMS base (est.) $150, Accountant (est.) $100, Founder pay (OPEN INPUT, set to 0) $0).

- **Break-even: 14 paying account-months a day.** Below that you lose money every month.
- **Profit margin at your plan** (50 a day): **66%** of every sale, after every cost.

## Year 1, month by month

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

## What if

| scenario | margin at plan | break-even a day | year 1 profit |
| --- | ---: | ---: | ---: |
| Base plan | 66% | 14 | $5,770 |
| Price -10% | 62% | 16 | $4,144 |
| Volume -20% | 60% | 14 | $2,816 |
| Unit costs +15% | 64% | 14 | $5,546 |

No red flags in these numbers. They are only as good as the inputs: check every cost against a real quote.
