<?php
namespace App\Http\Controllers\Api\V1;

use App\Services\CustomPlanService;
use App\Support\ApiException;
use App\Support\Ctx;
use App\Support\H;
use Illuminate\Support\Facades\DB;

/** Superadmin: unit prices of the self-serve custom plan. Plans already bought keep the price they were created with. */
class PlatformPricingController
{
    public function show(Ctx $ctx)
    {
        H::requirePlatform($ctx);
        return H::json($this->payload());
    }

    /** Body: {prices: {"seat.price": 12, ...}}. Missing keys are left unchanged; a value equal to the default clears the override. */
    public function update(Ctx $ctx)
    {
        H::requirePlatform($ctx);
        $incoming = H::body($ctx)['prices'] ?? null;
        if (!is_array($incoming) || !$incoming) throw new ApiException(400, 'Narxlar yuborilmadi', 'bad_request');

        $fields = CustomPlanService::priceFields();
        $defaults = CustomPlanService::defaultPrices();
        $before = CustomPlanService::storedPrices();
        $next = $before;
        foreach ($incoming as $path => $price) {
            if (!is_string($path) || !isset($fields[$path])) throw new ApiException(422, 'Noma’lum narx maydoni', 'validation_failed');
            if (!is_numeric($price) || $price < 0 || $price > 100000) {
                throw new ApiException(422, "«{$fields[$path]}»: narx 0 dan 100 000 gacha bo‘lishi kerak", 'validation_failed');
            }
            $price = round((float) $price, 2);
            if (abs($price - $defaults[$path]) < 0.005) unset($next[$path]); else $next[$path] = $price;
        }

        DB::table('platform_settings')->updateOrInsert(
            ['key' => CustomPlanService::SETTING_KEY],
            ['value' => json_encode($next, JSON_UNESCAPED_UNICODE), 'updated_by' => $ctx->user['id'], 'updated_at' => H::now()]
        );
        H::audit($ctx, 'platform.custom_plan_prices_updated', 'platform_settings', CustomPlanService::SETTING_KEY, ['before' => $before, 'after' => $next]);
        return H::json($this->payload());
    }

    private function payload(): array
    {
        $defaults = CustomPlanService::defaultPrices();
        $stored = CustomPlanService::storedPrices();
        $rows = [];
        foreach (CustomPlanService::priceFields() as $path => $label) {
            $rows[] = ['key' => $path, 'label' => $label, 'price' => $stored[$path] ?? $defaults[$path],
                'default' => $defaults[$path], 'overridden' => isset($stored[$path])];
        }
        $svc = new CustomPlanService();
        return ['currency' => (string) config('nexio.custom_plan.currency'), 'data' => $rows, 'plugins' => $svc->options()['plugins']];
    }
}
