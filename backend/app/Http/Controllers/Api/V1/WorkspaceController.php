<?php
namespace App\Http\Controllers\Api\V1;

use App\Support\Ctx;
use App\Support\H;
use App\Support\Rbac;
use Illuminate\Support\Facades\DB;

/** Workspace-level overviews: all files, resource usage vs plan limits, and the role/permission matrix. */
class WorkspaceController
{
    private const LINKS = [
        'lead' => '/app/leads/', 'contact' => '/app/contacts/', 'organization' => '/app/companies/', 'deal' => '/app/deals/',
        'project' => '/app/projects/', 'order' => '/app/orders/', 'task' => '/app/tasks',
    ];

    private function planLimits(string $tenantId): object
    {
        return DB::table('subscriptions as s')->join('plans as p', 'p.id', '=', 's.plan_id')->where('s.tenant_id', $tenantId)->orderByDesc('s.created_at')
            ->first(['p.name', 'p.seats', 'p.storage_gb', 'p.ai_minutes', 'p.api_requests']) ?? (object) ['name' => null, 'seats' => 0, 'storage_gb' => 0, 'ai_minutes' => 0, 'api_requests' => 0];
    }

    /** Every file attached anywhere in the company, with the record it belongs to. */
    public function files(Ctx $ctx)
    {
        $ctx->auth(); H::requireTenant($ctx);
        $t = $ctx->tenantId;
        [$limit, $page, $offset] = H::paginate($ctx);
        $where = ['tenant_id = ?']; $binds = [$t];
        if ($q = trim((string) $ctx->query('q'))) { $where[] = 'name LIKE ?'; $binds[] = H::like($q); }
        if ($type = $ctx->query('entity_type')) { $where[] = 'entity_type = ?'; $binds[] = (string) $type; }
        $w = implode(' AND ', $where);
        $rows = DB::select("SELECT id, entity_type, entity_id, name, mime, size, uploaded_by_name, created_at FROM attachments WHERE {$w} ORDER BY created_at DESC LIMIT ? OFFSET ?", [...$binds, $limit, $offset]);
        $total = (int) (DB::selectOne("SELECT COUNT(*) AS c FROM attachments WHERE {$w}", $binds)->c ?? 0);

        // Resolve "what is this attached to" in one query per record type (never one per file).
        $labels = [];
        foreach (collect($rows)->groupBy('entity_type') as $type => $group) {
            $ids = $group->pluck('entity_id')->unique()->values()->all();
            [$table, $cols] = match ($type) {
                'lead' => ['leads', ['title']], 'contact' => ['contacts', ['first_name', 'last_name']], 'organization' => ['organizations', ['name']],
                'deal' => ['deals', ['title']], 'project' => ['projects', ['name']], 'order' => ['orders', ['number']], 'task' => ['tasks', ['title']], default => [null, []],
            };
            if (!$table) continue;
            foreach (DB::table($table)->where('tenant_id', $t)->whereIn('id', $ids)->get(['id', ...$cols]) as $r) {
                $labels[$type . ':' . $r->id] = trim(implode(' ', array_map(fn ($c) => (string) $r->{$c}, $cols)));
            }
        }
        $used = (int) DB::table('attachments')->where('tenant_id', $t)->sum('size');
        $limitGb = (float) $this->planLimits($t)->storage_gb;
        return H::json([
            'data' => array_map(fn ($r) => [
                ...(array) $r, 'size' => (int) $r->size, 'entity_label' => $labels[$r->entity_type . ':' . $r->entity_id] ?? null,
                'entity_link' => isset(self::LINKS[$r->entity_type]) ? self::LINKS[$r->entity_type] . ($r->entity_type === 'task' ? '' : $r->entity_id) : null,
            ], $rows),
            'total' => $total, 'page' => $page, 'limit' => $limit,
            'storage' => ['used_bytes' => $used, 'limit_bytes' => (int) round($limitGb * 1024 ** 3)],
        ]);
    }

    /** Resource consumption against the current plan. */
    public function usage(Ctx $ctx)
    {
        $ctx->auth(); H::requireTenant($ctx); H::requirePermission($ctx, 'billing.read');
        $t = $ctx->tenantId;
        $plan = $this->planLimits($t);
        $count = fn (string $table, ?callable $f = null) => (int) ($f ? $f(DB::table($table)->where('tenant_id', $t)) : DB::table($table)->where('tenant_id', $t))->count();
        $monthStart = gmdate('Y-m-01 00:00:00');
        $aiSeconds = (int) DB::table('ai_calls')->where('tenant_id', $t)->where('created_at', '>=', $monthStart)->sum('duration_sec');
        $api = (int) DB::table('usage_records')->where('tenant_id', $t)->where('metric', 'api_requests')->where('period', gmdate('Y-m'))->sum('quantity');
        $storageBytes = (int) DB::table('attachments')->where('tenant_id', $t)->sum('size');
        return H::json([
            'plan' => $plan->name,
            'limits' => [
                'seats' => ['used' => $count('tenant_memberships', fn ($q) => $q->where('status', 'active')), 'limit' => (int) $plan->seats, 'unit' => ''],
                'storage' => ['used' => round($storageBytes / 1024 ** 3, 3), 'limit' => (float) $plan->storage_gb, 'unit' => 'ГБ'],
                'ai_minutes' => ['used' => round($aiSeconds / 60, 1), 'limit' => (int) $plan->ai_minutes, 'unit' => 'мин'],
                'api_requests' => ['used' => $api, 'limit' => (int) $plan->api_requests, 'unit' => ''],
            ],
            'records' => [
                'contacts' => $count('contacts'), 'organizations' => $count('organizations'), 'leads' => $count('leads'),
                'deals' => $count('deals'), 'orders' => $count('orders'), 'projects' => $count('projects'), 'tasks' => $count('tasks'), 'files' => $count('attachments'),
            ],
        ]);
    }

    /** Role → permission matrix with how many active members hold each role. */
    public function roles(Ctx $ctx)
    {
        $ctx->auth(); H::requireTenant($ctx); H::requirePermission($ctx, 'team.read');
        $counts = DB::table('tenant_memberships')->where('tenant_id', $ctx->tenantId)->where('status', 'active')->selectRaw('role, COUNT(*) AS c')->groupBy('role')->pluck('c', 'role')->all();
        return H::json([
            'permissions' => Rbac::PERMISSIONS,
            'roles' => array_map(fn ($r) => [
                'role' => $r, 'members' => (int) ($counts[$r] ?? 0),
                'permissions' => array_values(array_filter(Rbac::PERMISSIONS, fn ($p) => Rbac::can($r, $p))),
            ], Rbac::ROLES),
            'editable' => false,
        ]);
    }
}
