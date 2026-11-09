<?php

namespace App\Http\Controllers;

use App\Services\CbuExchangeRateService;
use App\Services\InvoiceSettlement;
use App\Services\PaymeError;
use App\Support\H;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Payme (Paycom) Merchant API — JSON-RPC 2.0 over HTTPS with Basic auth ("Paycom:<KEY>").
 * Amounts are in tiyin. The account is the invoice id. Every response is HTTP 200; failures are JSON-RPC errors.
 */
class PaymeWebhookController
{
    private const TIMEOUT_MS = 43_200_000; // a created transaction expires after 12 hours

    public function handle(Request $request)
    {
        $payload = $request->json()->all();
        $id = $payload['id'] ?? null;
        try {
            $this->authorize($request);
            $params = is_array($payload['params'] ?? null) ? $payload['params'] : [];
            $result = match ((string) ($payload['method'] ?? '')) {
                'CheckPerformTransaction' => $this->checkPerform($params),
                'CreateTransaction' => $this->create($params),
                'PerformTransaction' => $this->perform($params),
                'CancelTransaction' => $this->cancel($params),
                'CheckTransaction' => $this->check($params),
                'GetStatement' => $this->statement($params),
                default => throw new PaymeError(-32601, (string) ($payload['method'] ?? '')),
            };
            return response()->json(['jsonrpc' => '2.0', 'id' => $id, 'result' => $result]);
        } catch (PaymeError $e) {
            return response()->json(['jsonrpc' => '2.0', 'id' => $id, 'error' => $e->toArray()]);
        }
    }

    private function authorize(Request $request): void
    {
        $key = (string) config('payments.payme.key');
        $given = (string) $request->header('Authorization');
        $expected = 'Basic ' . base64_encode('Paycom:' . $key);
        if ($key === '' || !hash_equals($expected, $given)) throw new PaymeError(-32504);
    }

    private static function nowMs(): int
    {
        return (int) round(microtime(true) * 1000);
    }

    /** The invoice must exist and be unpaid, and the amount must equal what we asked for (tiyin). */
    private function invoiceFor(array $params): object
    {
        $invoiceId = (string) ($params['account']['invoice_id'] ?? '');
        $invoice = $invoiceId !== '' ? DB::table('invoices')->where('id', $invoiceId)->first() : null;
        if (!$invoice) throw new PaymeError(-31050, 'invoice_id');
        if (!is_numeric($params['amount'] ?? null) || (int) $params['amount'] !== $this->expectedTiyin($invoice)) throw new PaymeError(-31001, 'amount');
        if ($invoice->status !== 'open' && $invoice->status !== 'overdue') throw new PaymeError(-31008, 'invoice_id');
        return $invoice;
    }

    public function expectedTiyin(object $invoice): int
    {
        $items = H::parseJson($invoice->line_items, []);
        foreach (is_array($items) ? $items : [] as $item) {
            if (($item['kind'] ?? null) === 'click_conversion' && is_array($item['click_quote'] ?? null)) { // shared UZS quote
                return (int) round(((float) $item['click_quote']['target_amount']) * 100);
            }
        }
        $quote = (new CbuExchangeRateService())->quote((float) $invoice->total, (string) $invoice->currency);
        return (int) round(((float) $quote['target_amount']) * 100);
    }

    private function checkPerform(array $params): array
    {
        $this->invoiceFor($params);
        return ['allow' => true];
    }

    private function view(object $tx): array
    {
        return ['create_time' => (int) $tx->create_time, 'transaction' => $tx->id, 'state' => (int) $tx->state];
    }

    private function create(array $params): array
    {
        $paymeId = (string) ($params['id'] ?? '');
        if ($paymeId === '') throw new PaymeError(-32600, 'id');
        // An error thrown inside DB::transaction would roll back the expiry-cancel write, so it is thrown after commit.
        $error = null;
        $result = DB::transaction(function () use ($params, $paymeId, &$error) {
            $tx = DB::table('payme_transactions')->where('payme_id', $paymeId)->lockForUpdate()->first();
            if ($tx) {
                if ((int) $tx->state !== 1) { $error = new PaymeError(-31008, 'id'); return null; }
                if (self::nowMs() - (int) $tx->create_time > self::TIMEOUT_MS) {
                    $this->markCancelled($tx, 4);
                    $error = new PaymeError(-31008, 'id');
                    return null;
                }
                return $this->view($tx);
            }
            $invoice = $this->invoiceFor($params);
            // One pending transaction per invoice: a second one must wait for the first to finish or expire.
            $pending = DB::table('payme_transactions')->where('invoice_id', $invoice->id)->where('state', 1)->first();
            if ($pending && self::nowMs() - (int) $pending->create_time <= self::TIMEOUT_MS) throw new PaymeError(-31008, 'invoice_id');
            $row = [
                'id' => H::newId('pmt'), 'tenant_id' => $invoice->tenant_id, 'invoice_id' => $invoice->id, 'payme_id' => $paymeId,
                'amount' => (int) $params['amount'], 'state' => 1, 'payme_time' => (int) ($params['time'] ?? self::nowMs()),
                'create_time' => self::nowMs(), 'created_at' => H::now(),
            ];
            DB::table('payme_transactions')->insert($row);
            return $this->view((object) $row);
        });
        if ($error) throw $error;
        return $result;
    }

    private function find(array $params): object
    {
        $tx = DB::table('payme_transactions')->where('payme_id', (string) ($params['id'] ?? ''))->first();
        if (!$tx) throw new PaymeError(-31003, 'id');
        return $tx;
    }

    private function markCancelled(object $tx, int $reason): int
    {
        $state = (int) $tx->state === 2 ? -2 : -1;
        DB::table('payme_transactions')->where('id', $tx->id)->update(['state' => $state, 'reason' => $reason, 'cancel_time' => self::nowMs()]);
        return $state;
    }

    private function perform(array $params): array
    {
        $error = null; // thrown after commit so the expiry-cancel write is not rolled back
        $result = DB::transaction(function () use ($params, &$error) {
            $tx = $this->find($params);
            if ((int) $tx->state === 2) return ['transaction' => $tx->id, 'perform_time' => (int) $tx->perform_time, 'state' => 2];
            if ((int) $tx->state !== 1) { $error = new PaymeError(-31008, 'id'); return null; }
            if (self::nowMs() - (int) $tx->create_time > self::TIMEOUT_MS) {
                $this->markCancelled($tx, 4);
                $error = new PaymeError(-31008, 'id');
                return null;
            }
            $invoice = DB::table('invoices')->where('id', $tx->invoice_id)->first();
            if (!$invoice) { $error = new PaymeError(-31050, 'invoice_id'); return null; }
            $now = self::nowMs();
            DB::table('payme_transactions')->where('id', $tx->id)->update(['state' => 2, 'perform_time' => $now]);
            (new InvoiceSettlement())->settle($invoice, 'payme', (string) $tx->payme_id, $tx->amount / 100);
            return ['transaction' => $tx->id, 'perform_time' => $now, 'state' => 2];
        });
        if ($error) throw $error;
        return $result;
    }

    private function cancel(array $params): array
    {
        return DB::transaction(function () use ($params) {
            $tx = $this->find($params);
            $reason = isset($params['reason']) && is_numeric($params['reason']) ? (int) $params['reason'] : null;
            if ((int) $tx->state === 1) {
                $state = $this->markCancelled($tx, $reason ?? 3);
                $tx = DB::table('payme_transactions')->where('id', $tx->id)->first();
                return ['transaction' => $tx->id, 'cancel_time' => (int) $tx->cancel_time, 'state' => $state];
            }
            // A performed payment has already activated the purchase; reversing it is a manual, audited refund, not an API call.
            if ((int) $tx->state === 2) throw new PaymeError(-31007, 'id');
            return ['transaction' => $tx->id, 'cancel_time' => (int) $tx->cancel_time, 'state' => (int) $tx->state];
        });
    }

    private function full(object $tx): array
    {
        return [
            'create_time' => (int) $tx->create_time, 'perform_time' => (int) ($tx->perform_time ?? 0), 'cancel_time' => (int) ($tx->cancel_time ?? 0),
            'transaction' => $tx->id, 'state' => (int) $tx->state, 'reason' => $tx->reason !== null ? (int) $tx->reason : null,
        ];
    }

    private function check(array $params): array
    {
        return $this->full($this->find($params));
    }

    private function statement(array $params): array
    {
        $from = (int) ($params['from'] ?? 0); $to = (int) ($params['to'] ?? 0);
        $rows = DB::table('payme_transactions')->where('payme_time', '>=', $from)->where('payme_time', '<=', $to)->orderBy('payme_time')->get();
        return ['transactions' => $rows->map(fn ($tx) => [
            'id' => $tx->payme_id, 'time' => (int) $tx->payme_time, 'amount' => (int) $tx->amount, 'account' => ['invoice_id' => $tx->invoice_id], ...$this->full($tx),
        ])->all()];
    }
}
