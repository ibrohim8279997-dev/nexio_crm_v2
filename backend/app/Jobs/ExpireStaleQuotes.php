<?php
namespace App\Jobs;

use App\Events\QuoteStatusChanged;
use App\Support\H;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;

/** Moves draft/sent quotes whose valid_until date has passed to `expired` (scheduled daily). */
class ExpireStaleQuotes implements ShouldQueue
{
    use Dispatchable, Queueable;

    public function handle(): int
    {
        $today = gmdate('Y-m-d');
        $n = 0;
        $rows = DB::table('quotes')->whereIn('status', ['draft', 'sent'])->whereNotNull('valid_until')->where('valid_until', '<', $today)->get();
        foreach ($rows as $q) {
            // Compare-and-set so a quote accepted a moment ago is never expired by a racing run.
            $changed = DB::table('quotes')->where('id', $q->id)->where('status', $q->status)->update(['status' => 'expired', 'updated_at' => H::now()]);
            if ($changed) {
                $n++;
                QuoteStatusChanged::dispatch($q->tenant_id, $q->id, $q->number, $q->status, 'expired', null, null, $q->deal_id);
            }
        }
        return $n;
    }
}
