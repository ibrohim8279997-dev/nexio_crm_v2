<?php
namespace App\Http\Controllers\Api\V1;

use App\Support\ApiException;
use App\Support\Ctx;
use App\Support\H;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Cross-cutting CRM building blocks attached to any record: custom fields, notes, file attachments,
 * contact↔company links, task time entries and task dependencies. Every query is tenant-scoped and
 * every referenced record is re-verified against the caller's tenant (client ids are never trusted).
 */
class CrmExtrasController
{
    /** entity => [table, write permission] */
    private const ENTITIES = [
        'lead' => ['leads', 'crm.write'], 'contact' => ['contacts', 'crm.write'], 'organization' => ['organizations', 'crm.write'],
        'deal' => ['deals', 'deals.write'], 'project' => ['projects', 'projects.write'], 'order' => ['orders', 'orders.write'],
        'task' => ['tasks', 'tasks.write'],
    ];
    private const FIELD_ENTITIES = ['lead', 'contact', 'organization', 'deal', 'project', 'order'];
    private const FIELD_TYPES = ['text', 'number', 'date', 'select', 'boolean'];
    private const MAX_UPLOAD = 10 * 1024 * 1024;
    private const ALLOWED_EXT = ['pdf', 'png', 'jpg', 'jpeg', 'gif', 'webp', 'txt', 'csv', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'zip', 'odt', 'ods'];

    /** Confirms the record exists in this tenant and returns [table, writePermission]. */
    private function entity(Ctx $ctx, string $type, string $id, bool $write): array
    {
        $ctx->auth();
        H::requireTenant($ctx);
        if (!isset(self::ENTITIES[$type])) throw new ApiException(400, 'Недопустимый тип записи', 'bad_request');
        [$table, $perm] = self::ENTITIES[$type];
        if ($write) H::requirePermission($ctx, $perm);
        if (!DB::table($table)->where('id', $id)->where('tenant_id', $ctx->tenantId)->exists()) throw new ApiException(404, 'Запись не найдена', 'not_found');
        return [$table, $perm];
    }

    // ── custom fields ────────────────────────────────────────────────────────
    public function listFieldDefs(Ctx $ctx)
    {
        $ctx->auth(); H::requireTenant($ctx);
        $q = DB::table('custom_field_defs')->where('tenant_id', $ctx->tenantId);
        if ($e = $ctx->query('entity')) $q->where('entity', (string) $e);
        return H::json(['data' => $q->orderBy('entity')->orderBy('position')->get()->map(fn ($d) => $this->defView($d))->all()]);
    }

    private function defView(object $d): array
    {
        return [...(array) $d, 'options' => H::parseJson($d->options, []), 'is_required' => (bool) $d->is_required];
    }

    public function createFieldDef(Ctx $ctx)
    {
        $ctx->auth(); H::requireTenant($ctx); H::requirePermission($ctx, 'settings.write');
        $b = H::body($ctx);
        $entity = H::oneOf($b['entity'] ?? null, 'entity', self::FIELD_ENTITIES);
        $label = H::str($b['label'] ?? null, 'label', true, 120);
        $type = H::oneOf($b['type'] ?? null, 'type', self::FIELD_TYPES, 'text');
        $key = strtolower(trim((string) preg_replace('/[^a-z0-9]+/i', '_', (string) ($b['key'] ?? $label)), '_'));
        if ($key === '') $key = 'field_' . substr(bin2hex(random_bytes(3)), 0, 6);
        $key = mb_substr($key, 0, 60);
        $options = [];
        if ($type === 'select') {
            $options = array_values(array_filter(array_map(fn ($o) => trim((string) $o), is_array($b['options'] ?? null) ? $b['options'] : []), fn ($o) => $o !== ''));
            if (count($options) < 1) throw new ApiException(400, 'Для списка укажите хотя бы один вариант', 'bad_request');
        }
        if (DB::table('custom_field_defs')->where('tenant_id', $ctx->tenantId)->where('entity', $entity)->where('key', $key)->exists()) {
            throw new ApiException(409, 'Поле с таким ключом уже существует', 'conflict');
        }
        $id = H::newId('cfd');
        $pos = (int) DB::table('custom_field_defs')->where('tenant_id', $ctx->tenantId)->where('entity', $entity)->max('position') + 1;
        DB::table('custom_field_defs')->insert([
            'id' => $id, 'tenant_id' => $ctx->tenantId, 'entity' => $entity, 'key' => $key, 'label' => $label, 'type' => $type,
            'options' => json_encode($options, JSON_UNESCAPED_UNICODE), 'is_required' => !empty($b['is_required']) ? 1 : 0,
            'position' => $pos, 'created_at' => H::now(),
        ]);
        H::audit($ctx, 'custom_field.created', 'custom_field', $id, ['entity' => $entity, 'key' => $key]);
        return H::json(['id' => $id], 201);
    }

    public function updateFieldDef(Ctx $ctx)
    {
        $ctx->auth(); H::requireTenant($ctx); H::requirePermission($ctx, 'settings.write');
        $def = DB::table('custom_field_defs')->where('id', $ctx->params['id'])->where('tenant_id', $ctx->tenantId)->first();
        if (!$def) throw new ApiException(404, 'Поле не найдено', 'not_found');
        $b = H::body($ctx);
        $upd = [];
        if (array_key_exists('label', $b)) $upd['label'] = H::str($b['label'], 'label', true, 120);
        if (array_key_exists('is_required', $b)) $upd['is_required'] = !empty($b['is_required']) ? 1 : 0;
        if (array_key_exists('position', $b)) $upd['position'] = (int) H::num($b['position'], 'position', false, 0, 10000);
        if ($def->type === 'select' && is_array($b['options'] ?? null)) {
            $upd['options'] = json_encode(array_values(array_filter(array_map(fn ($o) => trim((string) $o), $b['options']), fn ($o) => $o !== '')), JSON_UNESCAPED_UNICODE);
        }
        if ($upd) DB::table('custom_field_defs')->where('id', $def->id)->update($upd);
        H::audit($ctx, 'custom_field.updated', 'custom_field', $def->id);
        return H::json(['ok' => true]);
    }

    public function deleteFieldDef(Ctx $ctx)
    {
        $ctx->auth(); H::requireTenant($ctx); H::requirePermission($ctx, 'settings.write');
        $def = DB::table('custom_field_defs')->where('id', $ctx->params['id'])->where('tenant_id', $ctx->tenantId)->first();
        if (!$def) throw new ApiException(404, 'Поле не найдено', 'not_found');
        DB::transaction(function () use ($def, $ctx) {
            DB::table('custom_field_values')->where('field_id', $def->id)->where('tenant_id', $ctx->tenantId)->delete();
            DB::table('custom_field_defs')->where('id', $def->id)->delete();
        });
        H::audit($ctx, 'custom_field.deleted', 'custom_field', $def->id, ['entity' => $def->entity, 'key' => $def->key]);
        return H::json(['ok' => true]);
    }

    public function getFieldValues(Ctx $ctx)
    {
        $entity = (string) $ctx->params['entity'];
        if (!in_array($entity, self::FIELD_ENTITIES, true)) throw new ApiException(400, 'Недопустимый тип записи', 'bad_request');
        $this->entity($ctx, $entity, (string) $ctx->params['id'], false);
        $defs = DB::table('custom_field_defs')->where('tenant_id', $ctx->tenantId)->where('entity', $entity)->orderBy('position')->get();
        $vals = DB::table('custom_field_values')->where('tenant_id', $ctx->tenantId)->where('entity', $entity)->where('entity_id', $ctx->params['id'])->pluck('value', 'field_id')->all();
        return H::json(['defs' => $defs->map(fn ($d) => $this->defView($d))->all(), 'values' => (object) $vals]);
    }

    public function setFieldValues(Ctx $ctx)
    {
        $entity = (string) $ctx->params['entity']; $entityId = (string) $ctx->params['id'];
        if (!in_array($entity, self::FIELD_ENTITIES, true)) throw new ApiException(400, 'Недопустимый тип записи', 'bad_request');
        $this->entity($ctx, $entity, $entityId, true);
        $incoming = H::body($ctx)['values'] ?? [];
        if (!is_array($incoming)) throw new ApiException(400, 'Некорректные значения', 'bad_request');
        $defs = DB::table('custom_field_defs')->where('tenant_id', $ctx->tenantId)->where('entity', $entity)->get()->keyBy('id');
        foreach (array_keys($incoming) as $fid) {
            if (!isset($defs[$fid])) throw new ApiException(422, 'Неизвестное поле', 'invalid_reference');
        }
        $existing = DB::table('custom_field_values')->where('tenant_id', $ctx->tenantId)->where('entity', $entity)->where('entity_id', $entityId)->pluck('value', 'field_id')->all();
        DB::transaction(function () use ($defs, $incoming, $existing, $ctx, $entity, $entityId) {
            foreach ($defs as $fid => $def) {
                $has = array_key_exists($fid, $incoming);
                $raw = $has ? $incoming[$fid] : ($existing[$fid] ?? null);
                $val = $this->normalizeValue($def, $raw);
                if ($val === null && $def->is_required) throw new ApiException(422, "Поле «{$def->label}» обязательно", 'bad_request');
                if (!$has) continue;
                if ($val === null) {
                    DB::table('custom_field_values')->where('field_id', $fid)->where('entity_id', $entityId)->delete();
                } else {
                    DB::table('custom_field_values')->updateOrInsert(
                        ['field_id' => $fid, 'entity_id' => $entityId],
                        ['id' => H::newId('cfv'), 'tenant_id' => $ctx->tenantId, 'entity' => $entity, 'value' => $val, 'updated_at' => H::now()]
                    );
                }
            }
        });
        H::audit($ctx, 'custom_field.values_saved', $entity, $entityId);
        return H::json(['ok' => true]);
    }

    private function normalizeValue(object $def, mixed $raw): ?string
    {
        if ($raw === null || $raw === '' || $raw === []) return null;
        $s = is_bool($raw) ? ($raw ? '1' : '0') : trim((string) $raw);
        if ($s === '') return null;
        switch ($def->type) {
            case 'number':
                if (!is_numeric($s)) throw new ApiException(422, "Поле «{$def->label}» должно быть числом", 'bad_request');
                return $s;
            case 'date':
                if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $s)) throw new ApiException(422, "Поле «{$def->label}»: дата в формате ГГГГ-ММ-ДД", 'bad_request');
                return $s;
            case 'boolean':
                return in_array(strtolower($s), ['1', 'true', 'yes', 'on'], true) ? '1' : '0';
            case 'select':
                if (!in_array($s, H::parseJson($def->options, []), true)) throw new ApiException(422, "Поле «{$def->label}»: недопустимый вариант", 'bad_request');
                return $s;
            default:
                if (mb_strlen($s) > 1000) throw new ApiException(422, "Поле «{$def->label}» длиннее 1000 символов", 'bad_request');
                return $s;
        }
    }

    // ── notes ────────────────────────────────────────────────────────────────
    public function listNotes(Ctx $ctx)
    {
        $this->entity($ctx, (string) $ctx->query('entity_type'), (string) $ctx->query('entity_id'), false);
        $rows = DB::table('notes')->where('tenant_id', $ctx->tenantId)->where('entity_type', $ctx->query('entity_type'))->where('entity_id', $ctx->query('entity_id'))
            ->orderByDesc('created_at')->limit(200)->get();
        return H::json(['data' => $rows]);
    }

    public function createNote(Ctx $ctx)
    {
        $b = H::body($ctx);
        $type = (string) ($b['entity_type'] ?? ''); $eid = (string) ($b['entity_id'] ?? '');
        $this->entity($ctx, $type, $eid, true);
        $user = $ctx->auth();
        $id = H::newId('note');
        DB::table('notes')->insert([
            'id' => $id, 'tenant_id' => $ctx->tenantId, 'entity_type' => $type, 'entity_id' => $eid,
            'body' => H::str($b['body'] ?? null, 'body', true, 5000), 'author_id' => $user['id'], 'author_name' => $user['name'], 'created_at' => H::now(),
        ]);
        return H::json(['id' => $id], 201);
    }

    public function deleteNote(Ctx $ctx)
    {
        $user = $ctx->auth(); H::requireTenant($ctx);
        $note = DB::table('notes')->where('id', $ctx->params['id'])->where('tenant_id', $ctx->tenantId)->first();
        if (!$note) throw new ApiException(404, 'Заметка не найдена', 'not_found');
        // Authors remove their own notes; otherwise the matching write permission of the record is required.
        if ($note->author_id !== $user['id']) H::requirePermission($ctx, self::ENTITIES[$note->entity_type][1] ?? 'crm.delete');
        DB::table('notes')->where('id', $note->id)->delete();
        return H::json(['ok' => true]);
    }

    // ── attachments ──────────────────────────────────────────────────────────
    public function listAttachments(Ctx $ctx)
    {
        $this->entity($ctx, (string) $ctx->query('entity_type'), (string) $ctx->query('entity_id'), false);
        $rows = DB::table('attachments')->where('tenant_id', $ctx->tenantId)->where('entity_type', $ctx->query('entity_type'))->where('entity_id', $ctx->query('entity_id'))
            ->orderByDesc('created_at')->get(['id', 'name', 'mime', 'size', 'uploaded_by_name', 'created_at']);
        return H::json(['data' => $rows]);
    }

    public function uploadAttachment(Ctx $ctx)
    {
        $type = (string) $ctx->request->input('entity_type'); $eid = (string) $ctx->request->input('entity_id');
        $this->entity($ctx, $type, $eid, true);
        $user = $ctx->auth();
        $file = $ctx->request->file('file');
        if (!$file || !$file->isValid()) throw new ApiException(400, 'Файл не получен', 'bad_request');
        $size = (int) $file->getSize();
        if ($size <= 0 || $size > self::MAX_UPLOAD) throw new ApiException(413, 'Размер файла — до 10 МБ', 'payload_too_large');
        $ext = strtolower((string) $file->getClientOriginalExtension());
        if (!in_array($ext, self::ALLOWED_EXT, true)) throw new ApiException(415, 'Этот тип файла не разрешён', 'unsupported_media_type');

        $limitGb = (float) (DB::table('subscriptions as s')->join('plans as p', 'p.id', '=', 's.plan_id')->where('s.tenant_id', $ctx->tenantId)->orderByDesc('s.created_at')->value('p.storage_gb') ?? 0);
        if ($limitGb > 0) {
            $used = (int) DB::table('attachments')->where('tenant_id', $ctx->tenantId)->sum('size');
            if ($used + $size > $limitGb * 1024 ** 3) throw new ApiException(402, 'Лимит хранилища тарифа исчерпан', 'quota_exceeded');
        }

        $id = H::newId('att');
        $path = "tenants/{$ctx->tenantId}/attachments/{$id}";   // built from ids only: the client filename never reaches the filesystem
        Storage::disk('local')->putFileAs(dirname($path), $file, basename($path));
        $name = mb_substr(trim((string) preg_replace('/[\x00-\x1F\x7F\/\\\\]+/u', '_', $file->getClientOriginalName())), 0, 255) ?: "file.{$ext}";
        DB::table('attachments')->insert([
            'id' => $id, 'tenant_id' => $ctx->tenantId, 'entity_type' => $type, 'entity_id' => $eid, 'name' => $name,
            'mime' => $file->getMimeType(), 'size' => $size, 'path' => $path,
            'uploaded_by' => $user['id'], 'uploaded_by_name' => $user['name'], 'created_at' => H::now(),
        ]);
        H::audit($ctx, 'attachment.uploaded', $type, $eid, ['name' => $name, 'size' => $size]);
        return H::json(['id' => $id], 201);
    }

    public function downloadAttachment(Ctx $ctx)
    {
        $ctx->auth(); H::requireTenant($ctx);
        $a = DB::table('attachments')->where('id', $ctx->params['id'])->where('tenant_id', $ctx->tenantId)->first();
        if (!$a || !Storage::disk('local')->exists($a->path)) throw new ApiException(404, 'Файл не найден', 'not_found');
        return Storage::disk('local')->download($a->path, $a->name, ['Content-Type' => 'application/octet-stream', 'X-Content-Type-Options' => 'nosniff', 'Cache-Control' => 'private, no-store']);
    }

    public function deleteAttachment(Ctx $ctx)
    {
        $user = $ctx->auth(); H::requireTenant($ctx);
        $a = DB::table('attachments')->where('id', $ctx->params['id'])->where('tenant_id', $ctx->tenantId)->first();
        if (!$a) throw new ApiException(404, 'Файл не найден', 'not_found');
        if ($a->uploaded_by !== $user['id']) H::requirePermission($ctx, self::ENTITIES[$a->entity_type][1] ?? 'crm.delete');
        Storage::disk('local')->delete($a->path);
        DB::table('attachments')->where('id', $a->id)->delete();
        H::audit($ctx, 'attachment.deleted', $a->entity_type, $a->entity_id, ['name' => $a->name]);
        return H::json(['ok' => true]);
    }

    // ── contact ↔ organization (many-to-many with role) ──────────────────────
    public function linkContactOrg(Ctx $ctx)
    {
        $this->entity($ctx, 'contact', (string) $ctx->params['id'], true);
        $b = H::body($ctx);
        $orgId = H::tenantRef($ctx, $b['org_id'] ?? null, 'organizations', 'kompaniya');
        if (!$orgId) throw new ApiException(400, 'Выберите компанию', 'bad_request');
        $cid = (string) $ctx->params['id'];
        $primary = !empty($b['is_primary']);
        DB::transaction(function () use ($ctx, $cid, $orgId, $b, $primary) {
            if ($primary) DB::table('contact_organizations')->where('contact_id', $cid)->where('tenant_id', $ctx->tenantId)->update(['is_primary' => 0]);
            DB::table('contact_organizations')->updateOrInsert(
                ['contact_id' => $cid, 'org_id' => $orgId],
                ['id' => H::newId('cor'), 'tenant_id' => $ctx->tenantId, 'role' => H::str($b['role'] ?? null, 'role', false, 120), 'is_primary' => $primary ? 1 : 0, 'created_at' => H::now()]
            );
            if ($primary) DB::table('contacts')->where('id', $cid)->where('tenant_id', $ctx->tenantId)->update(['org_id' => $orgId, 'updated_at' => H::now()]);
        });
        H::audit($ctx, 'contact.org_linked', 'contact', $cid, ['org_id' => $orgId]);
        return H::json(['ok' => true], 201);
    }

    public function unlinkContactOrg(Ctx $ctx)
    {
        $this->entity($ctx, 'contact', (string) $ctx->params['id'], true);
        DB::table('contact_organizations')->where('tenant_id', $ctx->tenantId)->where('contact_id', $ctx->params['id'])->where('org_id', $ctx->params['org'])->delete();
        H::audit($ctx, 'contact.org_unlinked', 'contact', (string) $ctx->params['id'], ['org_id' => $ctx->params['org']]);
        return H::json(['ok' => true]);
    }

    // ── task time entries ────────────────────────────────────────────────────
    public function listTime(Ctx $ctx)
    {
        $this->entity($ctx, 'task', (string) $ctx->params['id'], false);
        $rows = DB::table('time_entries')->where('tenant_id', $ctx->tenantId)->where('task_id', $ctx->params['id'])->orderByDesc('entry_date')->orderByDesc('created_at')->get();
        return H::json(['data' => $rows, 'total_minutes' => (int) $rows->sum('minutes')]);
    }

    public function addTime(Ctx $ctx)
    {
        $this->entity($ctx, 'task', (string) $ctx->params['id'], true);
        $user = $ctx->auth();
        $b = H::body($ctx);
        $minutes = (int) H::num($b['minutes'] ?? null, 'minutes', true);
        // H::num does not enforce its bounds, so the range is checked here.
        if ($minutes < 1 || $minutes > 1440) throw new ApiException(400, 'Время: от 1 до 1440 минут за запись', 'bad_request');
        $date = H::str($b['entry_date'] ?? null, 'entry_date', false, 10) ?? gmdate('Y-m-d');
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) throw new ApiException(400, 'Дата в формате ГГГГ-ММ-ДД', 'bad_request');
        $id = H::newId('tme');
        DB::table('time_entries')->insert([
            'id' => $id, 'tenant_id' => $ctx->tenantId, 'task_id' => $ctx->params['id'], 'user_id' => $user['id'], 'user_name' => $user['name'],
            'minutes' => $minutes, 'note' => H::str($b['note'] ?? null, 'note', false, 500), 'entry_date' => $date, 'created_at' => H::now(),
        ]);
        return H::json(['id' => $id], 201);
    }

    public function deleteTime(Ctx $ctx)
    {
        $user = $ctx->auth(); H::requireTenant($ctx);
        $e = DB::table('time_entries')->where('id', $ctx->params['id'])->where('tenant_id', $ctx->tenantId)->first();
        if (!$e) throw new ApiException(404, 'Запись не найдена', 'not_found');
        if ($e->user_id !== $user['id']) H::requirePermission($ctx, 'team.write');
        DB::table('time_entries')->where('id', $e->id)->delete();
        return H::json(['ok' => true]);
    }

    // ── task dependencies ────────────────────────────────────────────────────
    public function listDeps(Ctx $ctx)
    {
        $this->entity($ctx, 'task', (string) $ctx->params['id'], false);
        $t = $ctx->tenantId; $id = $ctx->params['id'];
        $blockedBy = DB::select('SELECT d.id AS link_id, k.id, k.title, k.status FROM task_dependencies d JOIN tasks k ON k.id = d.depends_on_id WHERE d.tenant_id = ? AND d.task_id = ?', [$t, $id]);
        $blocks = DB::select('SELECT d.id AS link_id, k.id, k.title, k.status FROM task_dependencies d JOIN tasks k ON k.id = d.task_id WHERE d.tenant_id = ? AND d.depends_on_id = ?', [$t, $id]);
        return H::json(['blocked_by' => $blockedBy, 'blocks' => $blocks]);
    }

    public function addDep(Ctx $ctx)
    {
        $this->entity($ctx, 'task', (string) $ctx->params['id'], true);
        $t = $ctx->tenantId; $task = (string) $ctx->params['id'];
        $dep = H::tenantRef($ctx, H::body($ctx)['depends_on_id'] ?? null, 'tasks', 'vazifa');
        if (!$dep) throw new ApiException(400, 'Выберите задачу', 'bad_request');
        if ($dep === $task) throw new ApiException(422, 'Задача не может зависеть от самой себя', 'invalid_reference');
        // Cycle guard: walking from the new blocker through its own blockers must never reach this task.
        $seen = []; $queue = [$dep];
        while ($queue) {
            $cur = array_shift($queue);
            if ($cur === $task) throw new ApiException(422, 'Такая зависимость создаст цикл', 'dependency_cycle');
            if (isset($seen[$cur])) continue;
            $seen[$cur] = true;
            foreach (DB::table('task_dependencies')->where('tenant_id', $t)->where('task_id', $cur)->pluck('depends_on_id') as $next) $queue[] = $next;
        }
        DB::table('task_dependencies')->insertOrIgnore(['id' => H::newId('tdp'), 'tenant_id' => $t, 'task_id' => $task, 'depends_on_id' => $dep, 'created_at' => H::now()]);
        return H::json(['ok' => true], 201);
    }

    public function removeDep(Ctx $ctx)
    {
        $this->entity($ctx, 'task', (string) $ctx->params['id'], true);
        DB::table('task_dependencies')->where('tenant_id', $ctx->tenantId)->where('id', $ctx->params['link'])->where('task_id', $ctx->params['id'])->delete();
        return H::json(['ok' => true]);
    }
}
