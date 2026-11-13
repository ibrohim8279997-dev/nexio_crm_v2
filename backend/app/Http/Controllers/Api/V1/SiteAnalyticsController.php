<?php
namespace App\Http\Controllers\Api\V1;

use App\Support\ApiException;
use App\Support\Ctx;
use App\Support\H;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * First-party page-view analytics. Collection: POST /site/track (no cookie, no IP/user agent stored, DNT/GPC honoured).
 * Reporting: platform superadmin only. Disclosed in the Privacy and Cookie policies (src/pages/public/legal).
 */
class SiteAnalyticsController
{
    private const BOT = '/bot|crawl|spider|slurp|headless|preview|monitor|curl|wget|python|httpclient|facebookexternalhit/i';

    private function tz(): string
    {
        return (string) config('nexio.analytics_tz', 'Asia/Tashkent');
    }

    // ── collection ───────────────────────────────────────────────────────────
    public function track(Ctx $ctx)
    {
        $req = $ctx->request;
        if ($req->header('DNT') === '1' || $req->header('Sec-GPC') === '1') return H::json(['ok' => true, 'tracked' => false]);
        $ua = (string) $req->userAgent();
        if ($ua === '' || preg_match(self::BOT, $ua) || !empty($ctx->user['is_platform_admin'])) return H::json(['ok' => true, 'tracked' => false]);

        $b = H::body($ctx);
        $path = self::normalizePath((string) ($b['path'] ?? ''));
        if ($path === null) return H::json(['ok' => true, 'tracked' => false]);

        $ref = null;
        if (!empty($b['referrer']) && is_string($b['referrer'])) {
            $host = strtolower((string) parse_url($b['referrer'], PHP_URL_HOST));
            if ($host !== '' && $host !== strtolower($req->getHost())) $ref = mb_substr($host, 0, 120);
        }
        $device = !empty($b['native']) ? 'app' : (preg_match('/ipad|tablet/i', $ua) ? 'tablet' : (preg_match('/mobi|android|iphone/i', $ua) ? 'mobile' : 'desktop'));
        $key = (string) config('app.key');
        $visitor = !empty($ctx->user['id'])
            ? substr(hash_hmac('sha256', 'u:' . $ctx->user['id'], $key), 0, 24)
            : substr(hash_hmac('sha256', 'a:' . $req->ip() . '|' . $ua, $key), 0, 24);

        $now = CarbonImmutable::now($this->tz());
        DB::table('site_visits')->insert([
            'occurred_at' => H::now(), 'visit_day' => $now->format('Y-m-d'), 'visit_hour' => (int) $now->format('G'), 'path' => $path,
            'visitor' => $visitor, 'user_id' => $ctx->user['id'] ?? null, 'tenant_id' => $ctx->tenantId, 'referrer_host' => $ref, 'device' => $device,
        ]);
        return H::json(['ok' => true, 'tracked' => true]);
    }

    /** Drops the query string and replaces ids/tokens with ":id" so rows stay countable and never contain secrets. */
    public static function normalizePath(string $raw): ?string
    {
        $p = (string) parse_url(trim($raw), PHP_URL_PATH);
        if ($p === '' || $p[0] !== '/' || str_starts_with($p, '/platform') || str_starts_with($p, '/api')) return null;
        $segs = array_values(array_filter(explode('/', $p), fn ($s) => $s !== ''));
        $afterToken = false;
        foreach ($segs as $i => $s) {
            $idLike = $afterToken || preg_match('/^\d+$/', $s) || strlen($s) >= 20 || (strlen($s) >= 8 && preg_match('/\d/', $s)) || preg_match('/^[a-z]{2,6}_[0-9a-f]{8,}$/i', $s);
            $afterToken = in_array($s, ['invite', 'reset-password', 'verify-email'], true);
            if ($idLike) $segs[$i] = ':id';
        }
        return mb_substr('/' . implode('/', $segs), 0, 200);
    }

    // ── reporting (superadmin) ───────────────────────────────────────────────
    private function date(mixed $v, string $field): ?string
    {
        if ($v === null || $v === '') return null;
        if (!is_string($v) || !preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $v, $m) || !checkdate((int) $m[2], (int) $m[3], (int) $m[1])) {
            throw new ApiException(422, "«{$field}» YYYY-MM-DD ko‘rinishida bo‘lishi kerak", 'validation_failed');
        }
        return $v;
    }

    private function range(Ctx $ctx, int $defaultDays): array
    {
        $today = CarbonImmutable::now($this->tz())->startOfDay();
        $to = $this->date($ctx->query('to'), 'to') ?? $today->format('Y-m-d');
        $from = $this->date($ctx->query('from'), 'from') ?? CarbonImmutable::parse($to)->subDays($defaultDays - 1)->format('Y-m-d');
        if ($from > $to) throw new ApiException(422, 'Boshlanish sanasi tugash sanasidan keyin bo‘lishi mumkin emas', 'validation_failed');
        return [$from, $to];
    }

    /** Views and unique visitors per day / month / year. Empty periods are returned as zeros so the chart has no gaps. */
    public function series(Ctx $ctx)
    {
        H::requirePlatform($ctx);
        $g = H::oneOf($ctx->query('granularity'), 'granularity', ['day', 'month', 'year'], 'day');
        $today = CarbonImmutable::now($this->tz())->startOfDay();
        if ($g === 'day') {
            [$from, $to] = $this->range($ctx, 30);
            if (CarbonImmutable::parse($from)->diffInDays(CarbonImmutable::parse($to)) > 366) throw new ApiException(422, 'Kunlik grafik uchun oraliq 366 kundan oshmasligi kerak', 'validation_failed');
            $len = 10;
        } elseif ($g === 'month') {
            $to = $today->format('Y-m-d');
            $from = $today->startOfMonth()->subMonths(11)->format('Y-m-d');
            $len = 7;
        } else {
            $to = $today->format('Y-m-d');
            $from = (string) (DB::table('site_visits')->min('visit_day') ?? $today->format('Y-m-d'));
            $len = 4;
        }

        $rows = DB::table('site_visits')->whereBetween('visit_day', [$from, $to])
            ->groupByRaw("SUBSTR(visit_day, 1, {$len})")
            ->selectRaw("SUBSTR(visit_day, 1, {$len}) AS k, COUNT(*) AS views, COUNT(DISTINCT visitor) AS visitors")
            ->get()->keyBy('k');

        $points = [];
        $cur = CarbonImmutable::parse($from);
        $end = CarbonImmutable::parse($to);
        while ($cur <= $end) {
            $k = substr($cur->format('Y-m-d'), 0, $len);
            $r = $rows[$k] ?? null;
            $points[$k] = ['key' => $k, 'views' => (int) ($r->views ?? 0), 'visitors' => (int) ($r->visitors ?? 0)];
            $cur = match ($g) { 'day' => $cur->addDay(), 'month' => $cur->addMonth()->startOfMonth(), default => $cur->addYear()->startOfYear() };
        }
        $t = DB::table('site_visits')->whereBetween('visit_day', [$from, $to])->selectRaw('COUNT(*) AS views, COUNT(DISTINCT visitor) AS visitors')->first();
        return H::json(['granularity' => $g, 'from' => $from, 'to' => $to, 'points' => array_values($points),
            'totals' => ['views' => (int) $t->views, 'visitors' => (int) $t->visitors]]);
    }

    /** Everything about one period (a single day when from = to): pages, referrers, devices, hours and who the signed-in visitors are. */
    public function details(Ctx $ctx)
    {
        H::requirePlatform($ctx);
        [$from, $to] = $this->range($ctx, 1);
        $q = fn () => DB::table('site_visits')->whereBetween('visit_day', [$from, $to]);

        $t = $q()->selectRaw('COUNT(*) AS views, COUNT(DISTINCT visitor) AS visitors')->first();
        $members = (int) $q()->whereNotNull('user_id')->selectRaw('COUNT(DISTINCT visitor) AS c')->value('c');

        $hours = array_fill(0, 24, 0);
        if ($from === $to) {
            foreach ($q()->groupBy('visit_hour')->selectRaw('visit_hour AS h, COUNT(*) AS c')->get() as $r) $hours[(int) $r->h] = (int) $r->c;
        }

        $pages = $q()->groupBy('path')->orderByDesc('views')->limit(50)
            ->selectRaw('path, COUNT(*) AS views, COUNT(DISTINCT visitor) AS visitors')->get()
            ->map(fn ($r) => ['path' => $r->path, 'views' => (int) $r->views, 'visitors' => (int) $r->visitors])->all();
        $referrers = $q()->groupBy('referrer_host')->orderByDesc('c')->limit(20)->selectRaw('referrer_host AS host, COUNT(*) AS c')->get()
            ->map(fn ($r) => ['host' => $r->host, 'views' => (int) $r->c])->all();
        $devices = $q()->groupBy('device')->orderByDesc('c')->selectRaw('device, COUNT(*) AS c')->get()
            ->map(fn ($r) => ['device' => $r->device, 'views' => (int) $r->c])->all();

        $users = $q()->whereNotNull('user_id')->groupBy('user_id')->orderByDesc('views')->limit(50)
            ->selectRaw('user_id, COUNT(*) AS views, MAX(occurred_at) AS last_seen')->get();
        $ids = $users->pluck('user_id')->all();
        $info = $ids ? DB::table('users')->whereIn('id', $ids)->get(['id', 'name', 'email'])->keyBy('id') : collect();
        $tenant = $ids ? DB::table('site_visits as v')->join('tenants as t', 't.id', '=', 'v.tenant_id')->whereBetween('v.visit_day', [$from, $to])
            ->whereIn('v.user_id', $ids)->groupBy('v.user_id', 't.name')->selectRaw('v.user_id AS uid, t.name AS tname')->get()->groupBy('uid') : collect();
        $byUser = [];
        if ($ids) {
            foreach ($q()->whereIn('user_id', $ids)->groupBy('user_id', 'path')->selectRaw('user_id, path, COUNT(*) AS c')->get() as $r) {
                $byUser[$r->user_id][] = ['path' => $r->path, 'views' => (int) $r->c];
            }
        }
        $people = $users->map(function ($u) use ($info, $tenant, $byUser) {
            $pages = $byUser[$u->user_id] ?? [];
            usort($pages, fn ($a, $b) => $b['views'] <=> $a['views']);
            return ['user_id' => $u->user_id, 'name' => $info[$u->user_id]->name ?? '—', 'email' => $info[$u->user_id]->email ?? null,
                'companies' => ($tenant[$u->user_id] ?? collect())->pluck('tname')->all(),
                'views' => (int) $u->views, 'last_seen' => $u->last_seen, 'pages' => array_slice($pages, 0, 8)];
        })->all();

        return H::json([
            'from' => $from, 'to' => $to,
            'totals' => ['views' => (int) $t->views, 'visitors' => (int) $t->visitors, 'members' => $members, 'guests' => max(0, (int) $t->visitors - $members),
                'pages_per_visitor' => $t->visitors ? round($t->views / $t->visitors, 2) : 0],
            'hours' => $hours, 'pages' => $pages, 'referrers' => $referrers, 'devices' => $devices, 'people' => $people,
        ]);
    }
}
