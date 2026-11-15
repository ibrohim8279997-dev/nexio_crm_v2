<?php
namespace App\Http\Controllers\Api\V1;

use App\Support\ApiException;
use App\Support\Ctx;
use App\Support\H;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Landing-page banners. Superadmin manages them (images only; the admin UI renders a PDF's first page to PNG before upload),
 * the public landing page reads the active ones. Files are streamed through the API, never from a guessable public path.
 */
class SiteBannersController
{
    private const MAX_BYTES = 5 * 1024 * 1024;
    private const MAX_SIDE = 6000;
    private const MAX_BANNERS = 10;
    private const MIME = ['image/png' => 'png', 'image/jpeg' => 'jpg', 'image/webp' => 'webp', 'image/gif' => 'gif'];

    private function disk(): string
    {
        return (string) config('filesystems.attachments_disk', 'local');
    }

    private function imageUrl(object $b): string
    {
        return "/api/v1/site/banners/{$b->id}/image?v=" . strtotime($b->updated_at . ' UTC');
    }

    // ── public ───────────────────────────────────────────────────────────────
    public function publicList(Ctx $ctx)
    {
        $rows = DB::table('site_banners')->where('is_active', 1)->orderBy('position')->orderBy('created_at')->get();
        return H::json(['data' => $rows->map(fn ($b) => [
            'id' => $b->id, 'image_url' => $this->imageUrl($b), 'link_url' => $b->link_url, 'alt' => $b->title,
            'width' => $b->width, 'height' => $b->height,
        ])->all()], 200, ['Cache-Control' => 'public, max-age=60']);
    }

    public function image(Ctx $ctx)
    {
        // Inactive banners are visible to platform admins only (their management page shows thumbnails of every banner).
        $q = DB::table('site_banners')->where('id', $ctx->params['id']);
        if (empty($ctx->user['is_platform_admin'])) $q->where('is_active', 1);
        $b = $q->first();
        if (!$b || !Storage::disk($b->disk)->exists($b->path)) throw new ApiException(404, 'Banner topilmadi', 'not_found');
        return Storage::disk($b->disk)->response($b->path, null, [
            'Content-Type' => $b->mime, 'X-Content-Type-Options' => 'nosniff', 'Cache-Control' => $b->is_active ? 'public, max-age=86400' : 'private, no-store',
            'Cross-Origin-Resource-Policy' => 'cross-origin',
        ]);
    }

    // ── superadmin ───────────────────────────────────────────────────────────
    public function index(Ctx $ctx)
    {
        H::requirePlatform($ctx);
        $rows = DB::table('site_banners')->orderBy('position')->orderBy('created_at')->get();
        return H::json(['data' => $rows->map(fn ($b) => [
            'id' => $b->id, 'title' => $b->title, 'link_url' => $b->link_url, 'is_active' => (bool) $b->is_active, 'position' => (int) $b->position,
            'size' => (int) $b->size, 'width' => $b->width, 'height' => $b->height, 'created_at' => $b->created_at,
            'image_url' => "/api/v1/site/banners/{$b->id}/image?v=" . strtotime($b->updated_at . ' UTC') . '&preview=1',
        ])->all(), 'max' => self::MAX_BANNERS]);
    }

    public function store(Ctx $ctx)
    {
        H::requirePlatform($ctx);
        if (DB::table('site_banners')->count() >= self::MAX_BANNERS) {
            throw new ApiException(422, 'Bannerlar soni ' . self::MAX_BANNERS . ' tadan oshmasligi kerak', 'validation_failed');
        }
        $file = $ctx->request->file('file');
        if (!$file || !$file->isValid()) throw new ApiException(400, 'Fayl qabul qilinmadi', 'bad_request');
        if ((int) $file->getSize() > self::MAX_BYTES) throw new ApiException(413, 'Rasm hajmi 5 MB dan oshmasligi kerak', 'payload_too_large');

        // Trust the bytes, not the client's filename or Content-Type.
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($file->getRealPath());
        $info = @getimagesize($file->getRealPath());
        if (!isset(self::MIME[$mime]) || !$info) {
            throw new ApiException(415, 'Faqat PNG, JPG, WEBP, GIF rasmlar (yoki PDF) qabul qilinadi', 'unsupported_media_type');
        }
        if ($info[0] > self::MAX_SIDE || $info[1] > self::MAX_SIDE) throw new ApiException(422, 'Rasm tomonlari ' . self::MAX_SIDE . ' pikseldan oshmasligi kerak', 'validation_failed');

        $input = $ctx->request->input();
        $id = H::newId('bnr');
        $path = "site/banners/{$id}";
        Storage::disk($this->disk())->putFileAs(dirname($path), $file, basename($path));
        DB::table('site_banners')->insert([
            'id' => $id, 'title' => H::str($input['title'] ?? null, 'title', false, 120), 'path' => $path, 'disk' => $this->disk(),
            'mime' => $mime, 'size' => (int) $file->getSize(), 'width' => $info[0], 'height' => $info[1],
            'link_url' => $this->link($input['link_url'] ?? null),
            'position' => (int) DB::table('site_banners')->max('position') + 1,
            'is_active' => filter_var($input['is_active'] ?? true, FILTER_VALIDATE_BOOLEAN) ? 1 : 0,
            'created_by' => $ctx->user['id'], 'created_at' => H::now(), 'updated_at' => H::now(),
        ]);
        H::audit($ctx, 'platform.banner_created', 'site_banner', $id);
        return H::json(['id' => $id], 201);
    }

    public function update(Ctx $ctx)
    {
        H::requirePlatform($ctx);
        $b = DB::table('site_banners')->where('id', $ctx->params['id'])->first();
        if (!$b) throw new ApiException(404, 'Banner topilmadi', 'not_found');
        $in = H::body($ctx);
        $set = [];
        if (array_key_exists('link_url', $in)) $set['link_url'] = $this->link($in['link_url']);
        if (array_key_exists('title', $in)) $set['title'] = H::str($in['title'], 'title', false, 120);
        if (array_key_exists('is_active', $in)) $set['is_active'] = $in['is_active'] ? 1 : 0;
        if (!$set) throw new ApiException(400, 'O‘zgarish yuborilmadi', 'bad_request');
        DB::table('site_banners')->where('id', $b->id)->update($set + ['updated_at' => H::now()]);
        H::audit($ctx, 'platform.banner_updated', 'site_banner', $b->id, array_keys($set));
        return H::json(['ok' => true]);
    }

    /** Body: {ids: [...]} in the desired display order. */
    public function reorder(Ctx $ctx)
    {
        H::requirePlatform($ctx);
        $ids = H::body($ctx)['ids'] ?? null;
        if (!is_array($ids) || count($ids) > self::MAX_BANNERS) throw new ApiException(400, 'Noto‘g‘ri tartib', 'bad_request');
        DB::transaction(function () use ($ids) {
            foreach (array_values($ids) as $i => $id) DB::table('site_banners')->where('id', (string) $id)->update(['position' => $i + 1]);
        });
        H::audit($ctx, 'platform.banner_reordered', 'site_banner');
        return H::json(['ok' => true]);
    }

    public function destroy(Ctx $ctx)
    {
        H::requirePlatform($ctx);
        $b = DB::table('site_banners')->where('id', $ctx->params['id'])->first();
        if (!$b) throw new ApiException(404, 'Banner topilmadi', 'not_found');
        Storage::disk($b->disk)->delete($b->path);
        DB::table('site_banners')->where('id', $b->id)->delete();
        H::audit($ctx, 'platform.banner_deleted', 'site_banner', $b->id);
        return H::json(['ok' => true]);
    }

    /** Empty → null (the banner is then not clickable). Otherwise an http(s) URL or a path on this site; never javascript:/data:. */
    private function link(mixed $v): ?string
    {
        $s = H::str($v, 'link_url', false, 500);
        if ($s === null) return null;
        if (preg_match('#^/(?!/)[^\s]*$#', $s)) return $s;
        if (filter_var($s, FILTER_VALIDATE_URL) && in_array(strtolower((string) parse_url($s, PHP_URL_SCHEME)), ['http', 'https'], true)) return $s;
        throw new ApiException(422, 'Havola http:// yoki https:// bilan boshlanishi kerak (yoki / bilan boshlanadigan sayt manzili)', 'validation_failed');
    }
}
