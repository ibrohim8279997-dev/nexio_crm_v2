<?php
namespace App\Services;

use App\Support\ApiException;
use App\Support\H;
use App\Support\OutboundEndpoint;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

/**
 * Outbound webhooks. Each event becomes a delivery row per matching endpoint, signed with
 * HMAC-SHA256 over "<timestamp>.<body>", retried with exponential backoff, then parked as "dead" for manual replay.
 */
class WebhookService
{
    public const EVENTS = ['lead.created', 'lead.converted', 'order.confirmed', 'invoice.paid'];
    public const MAX_ATTEMPTS = 6;
    /** Seconds to wait after failed attempt N (1-based) before the next one. */
    private const BACKOFF = [60, 300, 1800, 7200, 21600];

    public static function newSecret(): string
    {
        return 'whsec_' . bin2hex(random_bytes(24));
    }

    public static function sign(string $secret, int $timestamp, string $body): string
    {
        return 'sha256=' . hash_hmac('sha256', $timestamp . '.' . $body, $secret);
    }

    /** Queue an event for every active endpoint of the company that subscribed to it. Never throws into the caller. */
    public static function dispatch(string $tenantId, string $event, array $data): void
    {
        try {
            $endpoints = DB::table('webhook_endpoints')->where('tenant_id', $tenantId)->where('is_active', 1)->get();
            foreach ($endpoints as $ep) {
                $events = H::parseJson($ep->events, []);
                if (!in_array('*', $events, true) && !in_array($event, $events, true)) continue;
                $id = self::enqueue($tenantId, $ep->id, $event, $data);
                dispatch(fn () => self::deliver($id))->afterResponse();
            }
        } catch (\Throwable $e) {
            report($e);
        }
    }

    public static function enqueue(string $tenantId, string $endpointId, string $event, array $data): string
    {
        $id = H::newId('whd');
        $payload = json_encode(['id' => $id, 'event' => $event, 'created_at' => gmdate('c'), 'data' => $data], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        DB::table('webhook_deliveries')->insert([
            'id' => $id, 'tenant_id' => $tenantId, 'endpoint_id' => $endpointId, 'event' => $event, 'payload' => $payload,
            'status' => 'pending', 'attempts' => 0, 'next_attempt_at' => H::now(), 'created_at' => H::now(),
        ]);
        return $id;
    }

    /** One delivery attempt. Returns the resulting status. */
    public static function deliver(string $deliveryId): string
    {
        $d = DB::table('webhook_deliveries')->where('id', $deliveryId)->first();
        if (!$d || $d->status !== 'pending') return $d->status ?? 'missing';
        $ep = DB::table('webhook_endpoints')->where('id', $d->endpoint_id)->where('tenant_id', $d->tenant_id)->first();
        $attempt = (int) $d->attempts + 1;

        if (!$ep || !$ep->is_active) return self::finish($d, $attempt, 'dead', null, 'Endpoint удалён или отключён');
        try {
            // Re-validated on every attempt: a hostname can start resolving to an internal address later.
            $target = OutboundEndpoint::parse($ep->url . '');
            $ts = time();
            $secret = Crypt::decryptString($ep->secret);
            $res = Http::timeout(5)->connectTimeout(3)->withOptions(['allow_redirects' => false])->withHeaders([
                'Content-Type' => 'application/json', 'User-Agent' => 'NexioCRM-Webhooks/1.0',
                'X-Nexio-Event' => $d->event, 'X-Nexio-Delivery' => $d->id, 'X-Nexio-Timestamp' => (string) $ts,
                'X-Nexio-Signature' => self::sign($secret, $ts, $d->payload),
            ])->withBody($d->payload, 'application/json')->post($target['url']);
            if ($res->successful()) return self::finish($d, $attempt, 'success', $res->status(), null);
            return self::retryOrDie($d, $attempt, $res->status(), 'HTTP ' . $res->status());
        } catch (ApiException $e) {
            return self::finish($d, $attempt, 'dead', null, mb_substr($e->getMessage(), 0, 280));   // unsafe URL: retrying cannot help
        } catch (\Throwable $e) {
            return self::retryOrDie($d, $attempt, null, mb_substr($e->getMessage(), 0, 280));
        }
    }

    private static function retryOrDie(object $d, int $attempt, ?int $code, string $error): string
    {
        if ($attempt >= self::MAX_ATTEMPTS) return self::finish($d, $attempt, 'dead', $code, $error);
        DB::table('webhook_deliveries')->where('id', $d->id)->update([
            'attempts' => $attempt, 'response_code' => $code, 'error' => $error,
            'next_attempt_at' => gmdate('Y-m-d H:i:s', time() + self::BACKOFF[min($attempt, count(self::BACKOFF)) - 1]),
        ]);
        return 'pending';
    }

    private static function finish(object $d, int $attempt, string $status, ?int $code, ?string $error): string
    {
        DB::table('webhook_deliveries')->where('id', $d->id)->update([
            'status' => $status, 'attempts' => $attempt, 'response_code' => $code, 'error' => $error, 'next_attempt_at' => null,
            'delivered_at' => $status === 'success' ? H::now() : null,
        ]);
        return $status;
    }

    /** Called by the scheduler: retry everything that is due. */
    public static function retryDue(int $limit = 100): int
    {
        $ids = DB::table('webhook_deliveries')->where('status', 'pending')->where('attempts', '>', 0)->where('next_attempt_at', '<=', H::now())->orderBy('next_attempt_at')->limit($limit)->pluck('id');
        foreach ($ids as $id) self::deliver($id);
        return $ids->count();
    }
}
