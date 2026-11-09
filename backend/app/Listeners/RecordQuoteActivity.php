<?php
namespace App\Listeners;

use App\Events\QuoteStatusChanged;
use App\Services\WebhookService;
use App\Support\H;
use Illuminate\Support\Facades\DB;

/** Writes a timeline entry for the quote and queues a `quote.<status>` webhook for subscribed endpoints. */
class RecordQuoteActivity
{
    public function handle(QuoteStatusChanged $e): void
    {
        DB::table('activities')->insert([
            'id' => H::newId('act'), 'tenant_id' => $e->tenantId, 'kind' => 'system',
            'subject' => "Quote {$e->number}: {$e->from} → {$e->to}", 'body' => null,
            'entity_type' => $e->dealId ? 'deal' : null, 'entity_id' => $e->dealId,
            'actor_id' => $e->actorId, 'actor_name' => $e->actorName ?? 'system',
            'meta' => json_encode(['quote_id' => $e->quoteId, 'from' => $e->from, 'to' => $e->to], JSON_UNESCAPED_UNICODE),
            'created_at' => H::now(),
        ]);
        WebhookService::dispatch($e->tenantId, 'quote.' . $e->to, ['id' => $e->quoteId, 'number' => $e->number, 'from' => $e->from, 'to' => $e->to]);
    }
}
