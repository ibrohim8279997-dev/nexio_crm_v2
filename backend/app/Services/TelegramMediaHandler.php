<?php

namespace App\Services;

use App\Support\Speech;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;

/**
 * Voice messages, photos and /image for a linked Telegram user. Everything passes through the same checks as text:
 * linked account, hourly throttle, the company's AI allowance. Files and transcripts are not stored or logged.
 */
final class TelegramMediaHandler
{
    private const MAX_VOICE_SECONDS = 120;
    private const MAX_AUDIO_BYTES = 5 * 1024 * 1024;
    private const MAX_IMAGE_BYTES = 5 * 1024 * 1024;
    private const IMAGES_PER_HOUR = 5;

    public function __construct(
        private NotificationDispatcher $sender,
        private ?TelegramAssistant $assistant = null,
        private ?TelegramMedia $media = null,
        private ?GroqAi $ai = null,
    ) {
        $this->assistant ??= new TelegramAssistant();
        $this->media ??= new TelegramMedia();
        $this->ai ??= new GroqAi();
    }

    public static function isVoice(array $m): bool
    {
        return isset($m['voice']) || isset($m['audio']);
    }

    public static function isImage(array $m): bool
    {
        return isset($m['photo']) || str_starts_with((string) ($m['document']['mime_type'] ?? ''), 'image/');
    }

    private function notLinked(string $chatId): void
    {
        $this->sender->telegram($chatId, 'Avval CRM ichidan «Telegram’ni ulash» tugmasini bosing.');
    }

    public function voice(string $chatId, array $message): void
    {
        $who = $this->assistant->identify($chatId);
        if (!$who) { $this->notLinked($chatId); return; }
        $voice = $message['voice'] ?? $message['audio'];
        if ((int) ($voice['duration'] ?? 0) > self::MAX_VOICE_SECONDS) { $this->sender->telegram($chatId, 'Ovozli xabar juda uzun (2 daqiqagacha). / Голосовое слишком длинное (до 2 минут).'); return; }
        if ($deny = AiQuota::deny($who['tenant'])) { $this->sender->telegram($chatId, $deny); return; }

        try {
            $this->media->action($chatId, 'typing');
            $file = $this->media->download((string) ($voice['file_id'] ?? ''), self::MAX_AUDIO_BYTES);
            if (!$file) { $this->sender->telegram($chatId, 'Ovozli xabarni olib bo‘lmadi.'); return; }
            $heard = $this->ai->transcribe($file[0], $file[1] ?: 'voice.ogg');
            AiQuota::record($who['tenant'], AiQuota::forAudio($heard['duration'] ?: (float) ($voice['duration'] ?? 0)));
            if ($heard['text'] === '') { $this->sender->telegram($chatId, 'Ovozni tushunib bo‘lmadi. Qayta yuboring. / Не удалось разобрать речь.'); return; }

            $this->sender->telegram($chatId, '🎙 ' . mb_substr($heard['text'], 0, 1000));   // show what was understood
            $answer = $this->assistant->ask($who, $heard['text']);
            $this->sender->telegram($chatId, $answer['text']);
            if ($answer['ok'] && $heard['language'] === 'english') $this->speak($chatId, $who, $answer['text']);
        } catch (\Throwable $e) {
            Log::warning('Telegram voice failed', ['exception' => $e::class]);
            $this->sender->telegram($chatId, 'Ovozli xabarni qayta ishlab bo‘lmadi. Keyinroq urinib ko‘ring.');
        }
    }

    /** Voice reply. Groq's TTS speaks English only, so Russian/Uzbek answers stay text. */
    private function speak(string $chatId, array $who, string $text): void
    {
        if (!config('ai.voice_replies') || AiQuota::deny($who['tenant'])) return;
        try {
            $this->media->action($chatId, 'record_voice');
            // Orpheus accepts 200 characters per request: speak the first few sentences (the full answer is in the text message).
            $chunks = Speech::chunks($text);
            if (!$chunks) return;
            $wav = Speech::mergeWav(array_map(fn ($c) => $this->ai->speak($c), $chunks));
            AiQuota::record($who['tenant'], AiQuota::forChars(array_sum(array_map('mb_strlen', $chunks))));
            $ogg = $this->media->wavToOpus($wav);
            $ogg ? $this->media->sendVoice($chatId, $ogg) : $this->media->sendDocument($chatId, $wav, 'reply.wav');   // no ffmpeg: send the WAV as a file
        } catch (\Throwable $e) {
            Log::warning('Telegram speech failed', ['exception' => $e::class]);   // the text answer was already sent
        }
    }

    public function image(string $chatId, array $message): void
    {
        $who = $this->assistant->identify($chatId);
        if (!$who) { $this->notLinked($chatId); return; }
        if ($deny = AiQuota::deny($who['tenant'])) { $this->sender->telegram($chatId, $deny); return; }

        // Largest photo size last; for an image sent as a document the mime type is the one Telegram reports.
        $photo = isset($message['photo']) ? end($message['photo']) : $message['document'];
        $mime = isset($message['photo']) ? 'image/jpeg' : (string) $message['document']['mime_type'];
        try {
            $this->media->action($chatId, 'typing');
            $file = $this->media->download((string) ($photo['file_id'] ?? ''), self::MAX_IMAGE_BYTES);
            if (!$file) { $this->sender->telegram($chatId, 'Rasm juda katta yoki yuklab bo‘lmadi (5 MB gacha). / Изображение слишком большое (до 5 МБ).'); return; }
            $result = $this->assistant->describeImage($who, trim((string) ($message['caption'] ?? '')), 'data:' . $mime . ';base64,' . base64_encode($file[0]));
            $this->sender->telegram($chatId, $result['text']);
        } catch (\Throwable $e) {
            Log::warning('Telegram image failed', ['exception' => $e::class]);
            $this->sender->telegram($chatId, 'Rasmni qayta ishlab bo‘lmadi.');
        }
    }

    /** /image <description>: create a picture with the configured image provider. */
    public function generate(string $chatId, string $prompt): void
    {
        $who = $this->assistant->identify($chatId);
        if (!$who) { $this->notLinked($chatId); return; }
        if (!ImageGenerator::enabled()) { $this->sender->telegram($chatId, 'Rasm yaratish yoqilmagan (administrator IMAGE_API_KEY ni sozlashi kerak). / Генерация изображений не настроена.'); return; }
        if (trim($prompt) === '') { $this->sender->telegram($chatId, 'Misol: /image ofis stolidagi laptop. / Пример: /image ноутбук на офисном столе'); return; }
        if ($deny = AiQuota::deny($who['tenant'])) { $this->sender->telegram($chatId, $deny); return; }
        $key = "tg-img:{$who['user']->id}";
        if (RateLimiter::tooManyAttempts($key, self::IMAGES_PER_HOUR) || $this->assistant->throttled($who['user'], 3)) {
            $this->sender->telegram($chatId, 'Soatlik rasm limiti tugadi. / Часовой лимит картинок исчерпан.');
            return;
        }
        RateLimiter::hit($key, 3600);
        try {
            $this->media->action($chatId, 'upload_photo');
            $bytes = (new ImageGenerator())->generate($prompt);
            AiQuota::record($who['tenant'], AiQuota::IMAGE_TOKENS);
            if (!$this->media->sendPhoto($chatId, $bytes, mb_substr($prompt, 0, 200))) $this->sender->telegram($chatId, 'Rasmni yuborib bo‘lmadi.');
        } catch (\Throwable $e) {
            Log::warning('Telegram image generation failed', ['exception' => $e::class]);
            $this->sender->telegram($chatId, 'Rasm yaratib bo‘lmadi. Keyinroq urinib ko‘ring.');
        }
    }
}
