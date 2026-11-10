<?php
namespace App\Http\Controllers\Api\V1;

use App\Services\NotificationDispatcher;
use App\Services\WebhookService;
use App\Support\ApiException;
use App\Support\Ctx;
use App\Support\H;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Embeddable lead-capture web forms.
 *
 * Management is authenticated and tenant-scoped. The public endpoints are keyed by a public form
 * key (safe to publish), restricted to the form's allowed origins, rate-limited per IP and
 * protected by a honeypot field. A form can only ever create a lead for its own tenant.
 */
class LeadFormController
{
    private function guard(Ctx $ctx, bool $write): void
    {
        $ctx->auth(); H::requireTenant($ctx);
        if ($ctx->role === 'api_key') throw new ApiException(403, 'API-ключом нельзя управлять формами', 'forbidden');
        H::requirePermission($ctx, $write ? 'integrations.write' : 'settings.read');
    }

    private function origins(mixed $raw): array
    {
        $list = [];
        foreach ((array) $raw as $o) {
            $o = rtrim(trim((string) $o), '/');
            if ($o === '*') { $list[] = '*'; continue; }
            $p = parse_url($o);
            if (!$p || !in_array($p['scheme'] ?? '', ['http', 'https'], true) || empty($p['host']) || isset($p['path'])) {
                throw new ApiException(422, "Origin «{$o}» noto‘g‘ri (masalan https://example.uz)", 'bad_request');
            }
            $list[] = strtolower(($p['scheme']) . '://' . $p['host'] . (isset($p['port']) ? ':' . $p['port'] : ''));
        }
        return array_values(array_unique($list));
    }

    private function present(object $f): array
    {
        return [
            'id' => $f->id, 'name' => $f->name, 'public_key' => $f->public_key, 'allowed_origins' => H::parseJson($f->allowed_origins, []),
            'success_message' => $f->success_message, 'redirect_url' => $f->redirect_url, 'is_active' => (bool) $f->is_active,
            'submissions' => (int) $f->submissions, 'created_at' => $f->created_at,
            'embed' => '<script src="' . rtrim((string) config('app.url'), '/') . '/nexio-widget.js" data-key="' . $f->public_key . '" async></script>',
        ];
    }

    // ── management ───────────────────────────────────────────────────────────
    public function list(Ctx $ctx)
    {
        $this->guard($ctx, false);
        $rows = DB::table('lead_forms')->where('tenant_id', $ctx->tenantId)->orderByDesc('created_at')->get();
        return H::json(['data' => $rows->map(fn ($f) => $this->present($f))->all()]);
    }

    public function create(Ctx $ctx)
    {
        $this->guard($ctx, true);
        $user = $ctx->auth();
        $b = H::body($ctx);
        if (DB::table('lead_forms')->where('tenant_id', $ctx->tenantId)->count() >= 20) throw new ApiException(402, 'Formalar limiti (20)', 'quota_exceeded');
        $id = H::newId('lfm');
        DB::table('lead_forms')->insert([
            'id' => $id, 'tenant_id' => $ctx->tenantId, 'public_key' => 'lfk_' . bin2hex(random_bytes(10)),
            'name' => H::str($b['name'] ?? null, 'name', true, 160), 'allowed_origins' => json_encode($this->origins($b['allowed_origins'] ?? [])),
            'success_message' => H::str($b['success_message'] ?? null, 'success_message', false, 300) ?? 'Rahmat! Tez orada siz bilan bog‘lanamiz.',
            'redirect_url' => H::str($b['redirect_url'] ?? null, 'redirect_url', false, 500),
            'owner_id' => H::memberRef($ctx, $b['owner_id'] ?? null, 'mas’ul xodim') ?? $user['id'],
            'is_active' => 1, 'created_by' => $user['id'], 'created_at' => H::now(),
        ]);
        H::audit($ctx, 'lead_form.created', 'lead_form', $id);
        return H::json($this->present(DB::table('lead_forms')->where('id', $id)->first()), 201);
    }

    public function update(Ctx $ctx)
    {
        $this->guard($ctx, true);
        $f = DB::table('lead_forms')->where('id', $ctx->params['id'])->where('tenant_id', $ctx->tenantId)->first();
        if (!$f) throw new ApiException(404, 'Forma topilmadi', 'not_found');
        $b = H::body($ctx);
        $upd = [];
        if (array_key_exists('name', $b)) $upd['name'] = H::str($b['name'], 'name', true, 160);
        if (array_key_exists('allowed_origins', $b)) $upd['allowed_origins'] = json_encode($this->origins($b['allowed_origins']));
        if (array_key_exists('success_message', $b)) $upd['success_message'] = H::str($b['success_message'], 'success_message', false, 300);
        if (array_key_exists('redirect_url', $b)) $upd['redirect_url'] = H::str($b['redirect_url'], 'redirect_url', false, 500);
        if (array_key_exists('is_active', $b)) $upd['is_active'] = $b['is_active'] ? 1 : 0;
        if ($upd) DB::table('lead_forms')->where('id', $f->id)->update($upd);
        H::audit($ctx, 'lead_form.updated', 'lead_form', $f->id);
        return H::json($this->present(DB::table('lead_forms')->where('id', $f->id)->first()));
    }

    public function delete(Ctx $ctx)
    {
        $this->guard($ctx, true);
        $n = DB::table('lead_forms')->where('id', $ctx->params['id'])->where('tenant_id', $ctx->tenantId)->delete();
        if (!$n) throw new ApiException(404, 'Forma topilmadi', 'not_found');
        H::audit($ctx, 'lead_form.deleted', 'lead_form', (string) $ctx->params['id']);
        return H::json(['ok' => true]);
    }

    // ── public surface ───────────────────────────────────────────────────────
    private function allowOrigin(Request $request, object $form): ?string
    {
        $allowed = H::parseJson($form->allowed_origins, []);
        if (in_array('*', $allowed, true)) return '*';
        $origin = rtrim(strtolower((string) $request->header('Origin', '')), '/');
        return $origin !== '' && in_array($origin, $allowed, true) ? $origin : null;
    }

    private function cors($response, ?string $origin)
    {
        if ($origin !== null) {
            $response->headers->set('Access-Control-Allow-Origin', $origin);
            $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, OPTIONS');
            $response->headers->set('Access-Control-Allow-Headers', 'Content-Type');
            $response->headers->set('Vary', 'Origin');
        }
        return $response;
    }

    private function publicForm(string $key): ?object
    {
        return DB::table('lead_forms')->where('public_key', $key)->where('is_active', 1)->first() ?: null;
    }

    public function publicConfig(Request $request, string $key)
    {
        $f = $this->publicForm($key);
        if (!$f) return H::json(['error' => ['code' => 'not_found', 'message' => 'Forma topilmadi']], 404);
        $origin = $this->allowOrigin($request, $f);
        // Browsers read this cross-origin: without an allowed origin the widget simply cannot load.
        return $this->cors(H::json(['name' => $f->name, 'success_message' => $f->success_message, 'redirect_url' => $f->redirect_url]), $origin);
    }

    public function preflight(Request $request, string $key)
    {
        $f = $this->publicForm($key);
        $origin = $f ? $this->allowOrigin($request, $f) : null;
        return $this->cors(response('', 204), $origin);
    }

    public function submit(Request $request, string $key)
    {
        $f = $this->publicForm($key);
        if (!$f) return H::json(['error' => ['code' => 'not_found', 'message' => 'Forma topilmadi']], 404);
        $origin = $this->allowOrigin($request, $f);
        if ($origin === null) return H::json(['error' => ['code' => 'forbidden_origin', 'message' => 'Bu sayt uchun ruxsat yo‘q']], 403);

        $bucket = 'rl:form:' . $f->id . ':' . $request->ip() . ':' . gmdate('YmdHi');
        $n = (int) Cache::get($bucket, 0);
        if ($n >= 5) return $this->cors(H::json(['error' => ['code' => 'rate_limited', 'message' => 'Juda ko‘p so‘rov, keyinroq urinib ko‘ring']], 429), $origin);
        Cache::put($bucket, $n + 1, 90);

        // Honeypot: bots fill the hidden field; pretend success and store nothing.
        if ((string) $request->input('website', '') !== '') return $this->cors(H::json(['ok' => true], 201), $origin);

        $name = mb_substr(trim((string) $request->input('name', '')), 0, 160);
        $email = mb_substr(trim((string) $request->input('email', '')), 0, 190);
        $phone = mb_substr(trim((string) $request->input('phone', '')), 0, 40);
        $message = mb_substr(trim((string) $request->input('message', '')), 0, 2000);
        if ($name === '' || ($email === '' && $phone === '')) {
            return $this->cors(H::json(['error' => ['code' => 'bad_request', 'message' => 'Ism va telefon yoki email majburiy']], 422), $origin);
        }
        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->cors(H::json(['error' => ['code' => 'bad_request', 'message' => 'Email noto‘g‘ri']], 422), $origin);
        }
        if ($phone !== '' && !preg_match('/^[0-9+()\-\s]{7,40}$/', $phone)) {
            return $this->cors(H::json(['error' => ['code' => 'bad_request', 'message' => 'Telefon noto‘g‘ri']], 422), $origin);
        }

        $leadId = H::newId('lead');
        DB::transaction(function () use ($f, $leadId, $name, $email, $phone, $message) {
            DB::table('leads')->insert([
                'id' => $leadId, 'tenant_id' => $f->tenant_id, 'title' => 'Sayt formasi: ' . $name,
                'contact_name' => $name, 'org_id' => null, 'email' => $email ?: null, 'phone' => $phone ?: null,
                'source' => 'web_form', 'status' => 'new', 'score' => 40, 'value' => 0, 'currency' => 'UZS',
                'owner_id' => $f->owner_id, 'notes' => $message ?: null, 'created_at' => H::now(), 'updated_at' => H::now(),
            ]);
            DB::table('activities')->insert([
                'id' => H::newId('act'), 'tenant_id' => $f->tenant_id, 'kind' => 'system',
                'subject' => 'Veb-forma orqali lid: «' . $f->name . '»', 'entity_type' => 'lead', 'entity_id' => $leadId,
                'actor_id' => null, 'actor_name' => 'web-form', 'created_at' => H::now(),
            ]);
            DB::table('lead_forms')->where('id', $f->id)->increment('submissions');
        });
        if ($f->owner_id) (new NotificationDispatcher())->notifyMember((string) $f->tenant_id, (string) $f->owner_id, 'info', 'Yangi lid (veb-forma)', $name, '/app/leads');
        WebhookService::dispatch((string) $f->tenant_id, 'lead.created', ['id' => $leadId, 'title' => 'Sayt formasi: ' . $name, 'email' => $email ?: null, 'phone' => $phone ?: null, 'source' => 'web_form']);

        return $this->cors(H::json(['ok' => true, 'message' => $f->success_message, 'redirect_url' => $f->redirect_url], 201), $origin);
    }
}
