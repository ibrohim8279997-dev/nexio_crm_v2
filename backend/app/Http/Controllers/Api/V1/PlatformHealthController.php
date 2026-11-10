<?php
namespace App\Http\Controllers\Api\V1;

use App\Services\HealthChecks;
use App\Support\ApiException;
use App\Support\Ctx;
use App\Support\H;

/** Operator-facing health report. Platform admins only; never exposed to tenants or API keys. */
class PlatformHealthController
{
    public function show(Ctx $ctx)
    {
        $user = $ctx->auth();
        if ($ctx->role === 'api_key' || empty($user['is_platform_admin'])) throw new ApiException(403, 'Faqat platforma administratori uchun', 'forbidden');
        $r = HealthChecks::run();
        return H::json($r, $r['status'] === 'ok' ? 200 : 503);
    }
}
