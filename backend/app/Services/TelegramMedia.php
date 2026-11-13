<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;

/**
 * Telegram files in and out. The bot token is part of every Telegram URL, so exceptions are never logged with
 * their message and URLs never leave this class.
 */
final class TelegramMedia
{
    private function api(string $method): string
    {
        return 'https://api.telegram.org/bot' . config('telegram.bot_token', '') . '/' . $method;
    }

    /** @return array{0: string, 1: string}|null [bytes, filename] or null when missing / too large */
    public function download(string $fileId, int $maxBytes): ?array
    {
        try {
            $info = Http::connectTimeout(3)->timeout(10)->get($this->api('getFile'), ['file_id' => $fileId]);
            $path = (string) $info->json('result.file_path');
            if ($path === '' || (int) $info->json('result.file_size', 0) > $maxBytes) return null;
            $file = Http::timeout(60)->get('https://api.telegram.org/file/bot' . config('telegram.bot_token') . '/' . $path);
            return $file->successful() && strlen($file->body()) <= $maxBytes ? [$file->body(), basename($path)] : null;
        } catch (\Throwable $e) {
            Log::warning('Telegram file download failed', ['exception' => $e::class]);
            return null;
        }
    }

    public function action(string $chatId, string $action): void
    {
        try { Http::connectTimeout(2)->timeout(4)->post($this->api('sendChatAction'), ['chat_id' => $chatId, 'action' => $action]); } catch (\Throwable) {}
    }

    public function sendVoice(string $chatId, string $ogg): bool
    {
        return $this->upload('sendVoice', 'voice', $chatId, $ogg, 'reply.ogg');
    }

    public function sendPhoto(string $chatId, string $image, ?string $caption = null): bool
    {
        return $this->upload('sendPhoto', 'photo', $chatId, $image, 'image.png', $caption);
    }

    public function sendDocument(string $chatId, string $bytes, string $name): bool
    {
        return $this->upload('sendDocument', 'document', $chatId, $bytes, $name);
    }

    private function upload(string $method, string $field, string $chatId, string $bytes, string $name, ?string $caption = null): bool
    {
        try {
            $res = Http::connectTimeout(3)->timeout(60)->attach($field, $bytes, $name)
                ->post($this->api($method), array_filter(['chat_id' => $chatId, 'caption' => $caption ? mb_substr($caption, 0, 1000) : null]));
            if ($res->failed()) Log::warning('Telegram upload failed', ['method' => $method, 'status' => $res->status()]);
            return $res->successful();
        } catch (\Throwable $e) {
            Log::warning('Telegram upload failed', ['method' => $method, 'exception' => $e::class]);
            return false;
        }
    }

    /** Telegram voice notes must be OGG/Opus; Groq TTS returns WAV. Needs ffmpeg on the server. */
    public function wavToOpus(string $wav): ?string
    {
        try {
            $p = new Process(['ffmpeg', '-v', 'error', '-f', 'wav', '-i', 'pipe:0', '-c:a', 'libopus', '-b:a', '32k', '-f', 'ogg', 'pipe:1']);
            $p->setInput($wav)->setTimeout(30)->run();
            return $p->isSuccessful() && $p->getOutput() !== '' ? $p->getOutput() : null;
        } catch (\Throwable) {
            return null;
        }
    }
}
