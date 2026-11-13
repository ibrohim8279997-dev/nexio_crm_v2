<?php

namespace App\Services;

use App\Support\ApiException;
use Illuminate\Support\Facades\Http;

/** Image creation through any OpenAI-compatible /images/generations endpoint (Groq has no image model). */
final class ImageGenerator
{
    public static function enabled(): bool
    {
        return (string) config('ai.image.api_key') !== '';
    }

    /** @return string PNG/JPEG bytes */
    public function generate(string $prompt): string
    {
        $cfg = (array) config('ai.image');
        $res = Http::withToken((string) $cfg['api_key'])->acceptJson()->timeout(90)
            ->post($cfg['base_url'] . '/images/generations', ['model' => $cfg['model'], 'prompt' => mb_substr($prompt, 0, 1000), 'n' => 1, 'size' => $cfg['size']]);
        if ($res->failed()) throw new ApiException(502, 'Rasm yaratish xizmati javob bermadi', 'image_provider_error', ['status' => $res->status()]);

        $b64 = $res->json('data.0.b64_json');
        if (is_string($b64) && $b64 !== '') return (string) base64_decode($b64, true);
        $url = $res->json('data.0.url');
        if (is_string($url) && str_starts_with($url, 'https://')) {
            $img = Http::timeout(60)->get($url);
            if ($img->successful() && $img->body() !== '') return $img->body();
        }
        throw new ApiException(502, 'Rasm xizmati rasm qaytarmadi', 'image_invalid_response');
    }
}
