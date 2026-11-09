<?php
namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;

/** Raised after a quote moves to a new status (including system expiry). Listeners record activity and notify webhooks. */
class QuoteStatusChanged
{
    use Dispatchable;

    public function __construct(
        public string $tenantId,
        public string $quoteId,
        public string $number,
        public string $from,
        public string $to,
        public ?string $actorId = null,
        public ?string $actorName = null,
        public ?string $dealId = null,
    ) {}
}
