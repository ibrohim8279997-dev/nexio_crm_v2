<?php

namespace App\Console\Commands;

use App\Services\HealthChecks;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Runs the health checks and raises an alert (critical log + optional webhook) when any fail.
 * The same failure set is re-alerted at most every 30 minutes; recovery sends one "resolved" notice.
 * ALERT_WEBHOOK_URL accepts any endpoint that takes {"text": "..."} (Slack, Mattermost, relay bots).
 */
class HealthAlert extends Command
{
    protected $signature = 'nexio:health:alert';
    protected $description = 'Check platform health and alert when degraded';

    public function handle(): int
    {
        $r = HealthChecks::run();
        $sig = md5(implode(',', $r['failed']));
        $lastSig = Cache::get('nexio:alert:sig');

        if ($r['failed']) {
            if ($lastSig === $sig) return self::FAILURE;           // same incident, already announced recently
            Cache::put('nexio:alert:sig', $sig, 1800);
            $lines = array_map(fn ($c) => "• {$c['name']}: {$c['detail']}", array_filter($r['checks'], fn ($c) => !$c['ok']));
            $text = '⚠ Nexio CRM degraded (' . config('app.url') . ")\n" . implode("\n", $lines);
            Log::critical($text);
            $this->send($text);
            $this->error($text);
            return self::FAILURE;
        }

        if ($lastSig !== null) {
            Cache::forget('nexio:alert:sig');
            $this->send('✅ Nexio CRM recovered (' . config('app.url') . ')');
        }
        $this->info('Healthy');
        return self::SUCCESS;
    }

    private function send(string $text): void
    {
        $url = (string) config('nexio.alerts.webhook_url');
        if ($url === '' || !str_starts_with($url, 'https://')) return;
        try {
            Http::timeout(8)->post($url, ['text' => $text]);
        } catch (\Throwable $e) {
            Log::warning('Alert webhook failed: ' . $e->getMessage());
        }
    }
}
