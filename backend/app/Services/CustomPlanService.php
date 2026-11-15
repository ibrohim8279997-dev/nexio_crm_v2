<?php

namespace App\Services;

use App\Support\ApiException;
use App\Support\H;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

/**
 * Self-serve custom plan: validates a configuration, prices it from config('nexio.custom_plan') and stores it as a
 * private, immutable row in `plans` owned by one tenant. The price is always computed here, never taken from the client.
 */
final class CustomPlanService
{
    public const CODE_PREFIX = 'custom_';
    public const SETTING_KEY = 'custom_plan_prices';

    private array $cfg;

    public function __construct()
    {
        $this->cfg = self::effectiveConfig();
    }

    /**
     * Prices the superadmin may change, as dotted config path => label. Bounds and steps stay in config/nexio.php.
     * @return array<string,string>
     */
    public static function priceFields(): array
    {
        $fields = [
            'base' => 'Asosiy to‘lov (oyiga)', 'seat.price' => '1 ta xodim (oyiga)', 'storage_gb.price' => '1 GB xotira (oyiga)',
            'ai_minutes.price' => '1 AI daqiqa', 'contacts.price_per_1000' => '1000 ta kontakt (oyiga)',
        ];
        foreach ((array) config('nexio.custom_plan.modules') as $code => $def) {
            if (empty($def['always'])) $fields["modules.$code.price"] = 'Modul: ' . $code;
        }
        return $fields;
    }

    /** Prices from config/nexio.php, without superadmin overrides. @return array<string,float> */
    public static function defaultPrices(): array
    {
        $cfg = (array) config('nexio.custom_plan');
        $out = [];
        foreach (array_keys(self::priceFields()) as $path) $out[$path] = (float) Arr::get($cfg, $path, 0);
        return $out;
    }

    /** Superadmin overrides (only known price paths). @return array<string,float> */
    public static function storedPrices(): array
    {
        $raw = DB::table('platform_settings')->where('key', self::SETTING_KEY)->value('value');
        $data = $raw ? json_decode((string) $raw, true) : [];
        $allowed = self::priceFields();
        $out = [];
        foreach ((array) $data as $path => $price) {
            if (isset($allowed[$path]) && is_numeric($price) && $price >= 0) $out[$path] = round((float) $price, 2);
        }
        return $out;
    }

    /** config('nexio.custom_plan') with the superadmin price overrides applied. */
    public static function effectiveConfig(): array
    {
        $cfg = (array) config('nexio.custom_plan');
        foreach (self::storedPrices() as $path => $price) Arr::set($cfg, $path, $price);
        return $cfg;
    }

    /** Public description of what can be configured, unit prices and the paid/free plugin catalogue. */
    public function options(): array
    {
        $c = $this->cfg;
        return [
            'currency' => $c['currency'], 'base' => $c['base'], 'yearly_months' => $c['months_billed_yearly'],
            'seat' => $c['seat'], 'storage_gb' => $c['storage_gb'], 'ai_minutes' => $c['ai_minutes'], 'contacts' => $c['contacts'],
            'modules' => $c['modules'], 'plugins' => $this->plugins(),
        ];
    }

    /** @return array<string, array{id:string,name:string,tagline:?string,price:float,pricing_model:string}> keyed by id */
    private function pluginIndex(): array
    {
        $rows = DB::select(
            "SELECT id, name, tagline, pricing_model, price_monthly, currency FROM plugins
              WHERE is_published = 1 AND visibility IN ('public','unlisted') AND is_deprecated = 0 ORDER BY name"
        );
        $out = [];
        foreach ($rows as $r) {
            $price = $r->pricing_model === 'paid' ? $this->toUsd((float) $r->price_monthly, (string) $r->currency) : 0.0;
            $out[(string) $r->id] = ['id' => (string) $r->id, 'name' => (string) $r->name, 'tagline' => $r->tagline,
                'pricing_model' => (string) $r->pricing_model, 'price' => $price];
        }
        return $out;
    }

    private function plugins(): array
    {
        try {
            return array_values($this->pluginIndex());
        } catch (ApiException) {
            return [];   // exchange rate unavailable: the builder still works without plugins
        }
    }

    private function toUsd(float $amount, string $currency): float
    {
        $currency = strtoupper($currency);
        if ($amount <= 0 || $currency === 'USD') return round($amount, 2);
        $q = (new CbuExchangeRateService())->quote(1, 'USD');   // 1 USD in UZS
        if ($currency !== 'UZS' || (float) $q['target_amount'] <= 0) throw new ApiException(422, 'Plugin narxini USDga aylantirib bo‘lmadi', 'unsupported_currency');
        return round($amount / (float) $q['target_amount'], 2);
    }

    /**
     * Validates the client selection and returns the normalized configuration with a server-side price breakdown.
     * @return array{config:array,monthly:float,yearly:float,lines:array,currency:string}
     */
    public function quote(array $in): array
    {
        $c = $this->cfg;
        $seats = $this->bounded($in['seats'] ?? null, 'seats', $c['seat']);
        $storage = $this->bounded($in['storage_gb'] ?? null, 'storage_gb', $c['storage_gb']);
        $ai = $this->bounded($in['ai_minutes'] ?? 0, 'ai_minutes', $c['ai_minutes']);
        $contacts = $this->bounded($in['contacts'] ?? null, 'contacts', $c['contacts']);
        if ($contacts % $c['contacts']['step'] !== 0) {
            throw new ApiException(422, 'Kontaktlar soni ' . $c['contacts']['step'] . ' ga karrali bo‘lishi kerak', 'validation_failed');
        }

        $modules = [];
        foreach ((array) ($in['modules'] ?? []) as $m) {
            $m = (string) $m;
            if (!isset($c['modules'][$m])) throw new ApiException(422, 'Noma’lum modul: ' . mb_substr($m, 0, 40), 'validation_failed');
            $modules[$m] = true;
        }
        foreach ($c['modules'] as $code => $def) if (!empty($def['always'])) $modules[$code] = true;
        $modules = array_keys($modules);

        $pluginIds = array_values(array_unique(array_map('strval', (array) ($in['plugins'] ?? []))));
        if (count($pluginIds) > 50) throw new ApiException(422, 'Juda ko‘p plugin tanlandi', 'validation_failed');
        $index = $pluginIds ? $this->pluginIndex() : [];
        $plugins = [];
        foreach ($pluginIds as $id) {
            if (!isset($index[$id])) throw new ApiException(422, 'Plugin topilmadi yoki mavjud emas', 'validation_failed');
            $plugins[] = $index[$id];
        }

        $lines = [['key' => 'base', 'label' => 'Asosiy to‘lov', 'amount' => (float) $c['base']]];
        $lines[] = ['key' => 'seats', 'label' => "Xodimlar: $seats", 'amount' => $seats * $c['seat']['price']];
        $lines[] = ['key' => 'storage_gb', 'label' => "Xotira: $storage GB", 'amount' => $storage * $c['storage_gb']['price']];
        if ($ai > 0) $lines[] = ['key' => 'ai_minutes', 'label' => "AI: $ai daqiqa", 'amount' => $ai * $c['ai_minutes']['price']];
        $lines[] = ['key' => 'contacts', 'label' => "Kontaktlar: $contacts", 'amount' => $contacts / 1000 * $c['contacts']['price_per_1000']];
        foreach ($modules as $m) {
            if ($c['modules'][$m]['price'] > 0) $lines[] = ['key' => "module:$m", 'label' => "Modul: $m", 'amount' => (float) $c['modules'][$m]['price']];
        }
        foreach ($plugins as $p) {
            if ($p['price'] > 0) $lines[] = ['key' => 'plugin:' . $p['id'], 'label' => 'Plugin: ' . $p['name'], 'amount' => $p['price']];
        }
        $lines = array_map(fn ($l) => [...$l, 'amount' => round($l['amount'], 2)], $lines);
        $monthly = round(array_sum(array_column($lines, 'amount')), 2);

        return [
            'config' => ['seats' => $seats, 'storage_gb' => $storage, 'ai_minutes' => $ai, 'contacts' => $contacts,
                'modules' => $modules, 'plugins' => array_column($plugins, 'id')],
            'monthly' => $monthly, 'yearly' => round($monthly * $c['months_billed_yearly'], 2),
            'lines' => $lines, 'currency' => (string) $c['currency'],
        ];
    }

    private function bounded(mixed $v, string $field, array $def): int
    {
        if ($v === null || $v === '' || !is_numeric($v) || (float) $v != floor((float) $v)) {
            throw new ApiException(422, "«{$field}» butun son bo‘lishi kerak", 'validation_failed');
        }
        $n = (int) $v;
        if ($n < $def['min'] || $n > $def['max']) {
            throw new ApiException(422, "«{$field}» {$def['min']} dan {$def['max']} gacha bo‘lishi kerak. Kattaroq hajm uchun Enterprise bilan bog‘laning.", 'validation_failed');
        }
        return $n;
    }

    /**
     * Stores the quoted configuration as a new private plan for the tenant and returns its code.
     * Earlier custom plans of the tenant that no subscription or invoice references are removed.
     */
    public function createForTenant(string $tenantId, array $quote): string
    {
        $cfg = $quote['config'];
        $id = H::newId('plan');
        $code = self::CODE_PREFIX . substr(preg_replace('/[^a-z0-9]/i', '', $tenantId), -8) . '_' . substr($id, -8);
        $c = $this->cfg;
        DB::transaction(function () use ($tenantId, $id, $code, $quote, $cfg, $c) {
            $this->pruneUnused($tenantId);
            DB::table('plans')->insert([
                'id' => $id, 'code' => $code, 'name' => 'Custom', 'tagline' => 'Sizning shaxsiy tarifingiz',
                'price_monthly' => $quote['monthly'], 'price_yearly' => $quote['yearly'], 'currency' => $quote['currency'],
                'seats' => $cfg['seats'], 'api_requests' => $cfg['seats'] * (int) $c['api_requests_per_seat'], 'ai_minutes' => $cfg['ai_minutes'],
                'storage_gb' => $cfg['storage_gb'], 'max_contacts' => $cfg['contacts'],
                'modules' => json_encode($cfg['modules']), 'is_public' => 0, 'position' => 1000,
                'owner_tenant_id' => $tenantId, 'custom_config' => json_encode($cfg),
            ]);
            $features = [["{$cfg['seats']} ta xodim", 1], ["{$cfg['storage_gb']} GB xotira", 1], ["{$cfg['contacts']} ta kontakt", 1]];
            if ($cfg['ai_minutes'] > 0) $features[] = ["{$cfg['ai_minutes']} AI daqiqa", 1];
            foreach ($features as $i => [$label, $inc]) {
                DB::table('plan_features')->insert(['id' => H::newId('pf'), 'plan_id' => $id, 'label' => $label, 'included' => $inc, 'position' => $i]);
            }
            foreach ($cfg['plugins'] as $pluginId) {
                DB::table('plugin_entitlements')->insert([
                    'id' => H::newId('pent'), 'plugin_id' => $pluginId, 'plan_id' => $id, 'can_install' => 1, 'can_use' => 1,
                    'trial_allowed' => 1, 'is_enabled' => 1, 'notes' => 'custom plan', 'created_at' => H::now(), 'updated_at' => H::now(),
                ]);
            }
        });
        return $code;
    }

    private function pruneUnused(string $tenantId): void
    {
        $ids = DB::table('plans')->where('owner_tenant_id', $tenantId)
            ->whereNotIn('id', DB::table('subscriptions')->select('plan_id'))
            ->whereNotIn('code', DB::table('subscriptions')->whereNotNull('pending_plan_code')->select('pending_plan_code'))
            ->pluck('id')->all();
        if (!$ids) return;
        // An open (unpaid) invoice may still point at the plan code: keep those plans.
        $open = DB::table('invoices')->where('tenant_id', $tenantId)->where('status', 'open')->pluck('line_items')->all();
        foreach ($ids as $k => $id) {
            $code = DB::table('plans')->where('id', $id)->value('code');
            foreach ($open as $li) if (str_contains((string) $li, '"' . $code . '"')) { unset($ids[$k]); break; }
        }
        if (!$ids) return;
        DB::table('plugin_entitlements')->whereIn('plan_id', $ids)->delete();
        DB::table('plan_features')->whereIn('plan_id', $ids)->delete();
        DB::table('plans')->whereIn('id', $ids)->delete();
    }

    /** Contact cap of the tenant's current plan, or null when unlimited. */
    public static function contactLimit(string $tenantId): ?int
    {
        $row = DB::table('subscriptions as s')->join('plans as p', 'p.id', '=', 's.plan_id')
            ->where('s.tenant_id', $tenantId)->whereIn('s.status', ['active', 'trialing', 'past_due', 'grace'])
            ->orderByDesc('s.created_at')->first(['p.max_contacts']);
        return $row && $row->max_contacts !== null ? (int) $row->max_contacts : null;
    }
}
