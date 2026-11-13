# CFO inputs and where they came from

**Nothing here is a real cost.** No invoices, payroll, hosting bill, customer count or conversion data exist in the repo or in the conversation. Every number in `numbers.json` is an estimate by the model, marked "est." in its label. Replace them with real figures before relying on the result.

- **Unit.** One paying account for one month. `days_per_month` is set to 1, so the tool's "per day" columns mean "per month" here: "break-even per day" = break-even accounts per month.
- **Price $60.** Estimated blended revenue per account: a guessed mix of 40% Lean ($25), 45% Starter ($49), 15% Business ($199) from `founder/pricing.md` and `founder/offer.md`. The mix is a guess; prices are still under test.
- **Variable $5.50.** Payment fee 2.5% (guess, check Click and Payme merchant rates), server share per account (production is a Laravel app on a CloudPanel server per `docs/ARCHITECTURE.md`, so the real cost is a mostly fixed server bill that this model spreads per account; it understates cost at low volume; the real price is unknown), support and AI tokens.
- **Fixed $750.** Helper, tools and accountant guesses. **Founder pay is an open input, set to 0.** A real salary would raise fixed costs.
- **Startup $3,000.** Mostly the free migrations promised in the offer: 50 accounts at 3 hours each and $10 an hour (both guesses).
- **Ramp 2 to 50 accounts.** Pure assumption, aimed at the "Founding 50" cap. The panel gave 1 buyer of 20 in the second run (5%), 0 of 20 in the first. That is an upper-bound style signal from simulated buyers, not a conversion rate, so it cannot confirm this ramp. It only says month 1 is unlikely to be big.
- Revenue is before tax. Sales tax or VAT in Uzbekistan was not researched.
