<?php
namespace App\Http\Controllers\Api\V1;

use App\Support\ApiException;
use App\Support\Ctx;
use App\Support\H;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/** Real files in the Documents section: upload, download, delete. The client's filename never reaches the filesystem. */
class DocumentFilesController
{
    private const MAX_BYTES = 20 * 1024 * 1024;
    private const EXT = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'odt', 'ods', 'odp', 'rtf', 'txt', 'md', 'csv', 'png', 'jpg', 'jpeg', 'gif', 'webp', 'zip'];
    private const KINDS = ['proposal', 'contract', 'invoice', 'act', 'other'];

    private function disk(): string
    {
        return (string) config('filesystems.attachments_disk', 'local');
    }

    public function upload(Ctx $ctx)
    {
        $user = $ctx->auth();
        H::requirePermission($ctx, 'crm.write');
        H::requireTenant($ctx);
        $file = $ctx->request->file('file');
        if (!$file || !$file->isValid()) throw new ApiException(400, 'Fayl qabul qilinmadi', 'bad_request');
        $size = (int) $file->getSize();
        if ($size <= 0 || $size > self::MAX_BYTES) throw new ApiException(413, 'Fayl hajmi 20 MB dan oshmasligi kerak', 'payload_too_large');
        $ext = strtolower((string) $file->getClientOriginalExtension());
        if (!in_array($ext, self::EXT, true)) throw new ApiException(415, 'Bu fayl turi ruxsat etilmagan', 'unsupported_media_type');

        // Same storage budget as CRM attachments: files and documents share the plan's storage_gb.
        $limitGb = (float) (DB::table('subscriptions as s')->join('plans as p', 'p.id', '=', 's.plan_id')->where('s.tenant_id', $ctx->tenantId)->orderByDesc('s.created_at')->value('p.storage_gb') ?? 0);
        if ($limitGb > 0) {
            $used = (int) DB::table('attachments')->where('tenant_id', $ctx->tenantId)->sum('size') + (int) DB::table('documents')->where('tenant_id', $ctx->tenantId)->sum('size_kb') * 1024;
            if ($used + $size > $limitGb * 1024 ** 3) throw new ApiException(402, 'Tarifdagi xotira limiti tugadi', 'quota_exceeded');
        }

        $in = $ctx->request->input();
        $original = mb_substr(trim((string) preg_replace('/[\x00-\x1F\x7F\/\\\\]+/u', '_', $file->getClientOriginalName())), 0, 255) ?: "file.{$ext}";
        $id = H::newId('doc');
        $path = "tenants/{$ctx->tenantId}/documents/{$id}";
        Storage::disk($this->disk())->putFileAs(dirname($path), $file, basename($path));
        DB::table('documents')->insert([
            'id' => $id, 'tenant_id' => $ctx->tenantId,
            'name' => H::str($in['name'] ?? null, 'name', false, 250) ?: pathinfo($original, PATHINFO_FILENAME),
            'kind' => H::oneOf($in['kind'] ?? null, 'kind', self::KINDS, 'other'),
            'org_id' => H::tenantRef($ctx, $in['org_id'] ?? null, 'organizations', 'kompaniya'),
            'order_id' => H::tenantRef($ctx, $in['order_id'] ?? null, 'orders', 'buyurtma'),
            'version' => 1, 'size_kb' => max(1, (int) ceil($size / 1024)), 'status' => 'draft',
            'file_path' => $path, 'file_disk' => $this->disk(), 'mime' => $file->getMimeType(), 'original_name' => $original,
            'created_by' => $user['id'], 'created_at' => H::now(), 'updated_at' => H::now(),
        ]);
        H::audit($ctx, 'document.uploaded', 'document', $id, ['name' => $original, 'size' => $size]);
        return H::json(['id' => $id], 201);
    }

    public function download(Ctx $ctx)
    {
        $ctx->auth(); H::requireTenant($ctx);
        $d = DB::table('documents')->where('id', $ctx->params['id'])->where('tenant_id', $ctx->tenantId)->first();
        if (!$d || !$d->file_path || !Storage::disk((string) $d->file_disk)->exists($d->file_path)) throw new ApiException(404, 'Fayl topilmadi', 'not_found');
        return Storage::disk((string) $d->file_disk)->download($d->file_path, (string) ($d->original_name ?: $d->name), [
            'Content-Type' => 'application/octet-stream', 'X-Content-Type-Options' => 'nosniff', 'Cache-Control' => 'private, no-store',
        ]);
    }

    public function destroy(Ctx $ctx)
    {
        $ctx->auth(); H::requireTenant($ctx);
        H::requirePermission($ctx, 'crm.delete');
        $d = DB::table('documents')->where('id', $ctx->params['id'])->where('tenant_id', $ctx->tenantId)->first();
        if (!$d) throw new ApiException(404, 'Hujjat topilmadi', 'not_found');
        if ($d->file_path) Storage::disk((string) $d->file_disk)->delete($d->file_path);
        DB::table('documents')->where('id', $d->id)->delete();
        H::audit($ctx, 'document.deleted', 'document', $d->id, ['name' => $d->name]);
        return H::json(['ok' => true]);
    }
}
