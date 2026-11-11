<?php

namespace App\Services;

use App\Support\BotsSignature;
use App\Support\BotsTables;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Site side. Notices which tables a request/job wrote to and, once it is done, tells the bot server so it can
 * pull them. The bot server also reconciles every 5 minutes, so a lost notice only delays the mirror.
 */
final class BotsPublisher
{
    /** @var array<string, true> */
    private static array $dirty = [];
    private static bool $armed = false;

    public static function boot(): void
    {
        if (config('bots.role') !== 'site' || (string) config('bots.push_url') === '' || (string) config('bots.secret') === '') return;

        DB::listen(function ($query) {
            if (!preg_match('/^\s*(?:insert\s+(?:or\s+\w+\s+)?into|replace\s+into|update(?:\s+or\s+\w+)?|delete\s+from|truncate(?:\s+table)?)\s+[`"\[]?(\w+)/i', $query->sql, $m)) return;
            $table = strtolower($m[1]);
            if (BotsTables::excluded($table)) return;
            self::$dirty[$table] = true;
            if (!self::$armed) {
                self::$armed = true;
                app()->terminating(fn () => self::flush());   // after the HTTP response has been sent
            }
        });
        // A queue worker never "terminates" between jobs, so flush after each one.
        Event::listen(JobProcessed::class, fn () => self::flush());
        Event::listen(JobFailed::class, fn () => self::flush());
    }

    public static function flush(): void
    {
        self::$armed = false;
        if (!self::$dirty) return;
        $tables = array_keys(self::$dirty);
        self::$dirty = [];
        $body = json_encode(['tables' => $tables]);
        $url = (string) config('bots.push_url');
        try {
            Http::connectTimeout(3)->timeout(5)->withBody($body, 'application/json')
                ->withHeaders(BotsSignature::headers('POST', (string) parse_url($url, PHP_URL_PATH), [], $body))->post($url);
        } catch (\Throwable $e) {
            // Never surface: the next change or the 5-minute reconcile catches the bot up.
            Log::warning('Bots push failed', ['exception' => $e::class]);
        }
    }
}
