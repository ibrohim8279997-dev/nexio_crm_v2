<?php
namespace Tests\Feature;

use App\Services\TelegramBot;
use App\Support\Speech;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

/** Telegram bot: voice (Whisper STT, Orpheus TTS), image reading (vision) and /image, all inside the company's AI allowance. */
class TelegramMediaTest extends PluginTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['telegram.bot_token' => '123:abc', 'ai.api_key' => 'groq-key', 'ai.image.api_key' => '']);
        DB::table('plans')->where('id', 'plan_business')->update(['ai_minutes' => 300]);   // 300 000 tokens a month
        DB::table('user_telegram_links')->insert(['id' => 'tgl_a', 'user_id' => 'usr_a', 'chat_id' => '7001', 'link_token' => 'tok_a', 'created_at' => '2026-01-01 00:00:00']);
    }

    private function fake(string $language = 'english'): void
    {
        Http::fake([
            'api.telegram.org/*/getFile*' => Http::response(['ok' => true, 'result' => ['file_path' => 'voice/file_1.oga', 'file_size' => 1000]]),
            'api.telegram.org/file/*' => Http::response('BINARYDATA'),
            'api.groq.com/openai/v1/audio/transcriptions' => Http::response(['text' => 'how many leads this month', 'language' => $language, 'duration' => 6.0]),
            'api.groq.com/openai/v1/audio/speech' => Http::response('RIFFwav'),
            'api.groq.com/openai/v1/chat/completions' => Http::response(['choices' => [['message' => ['content' => 'You have 3 new leads.']]], 'model' => 'm', 'usage' => ['total_tokens' => 120]]),
            'api.openai.com/v1/images/generations' => Http::response(['data' => [['b64_json' => base64_encode('PNGDATA')]]]),
            'api.telegram.org/*' => Http::response(['ok' => true]),
        ]);
    }

    private function texts(): array
    {
        return Http::recorded()->filter(fn ($p) => str_contains($p[0]->url(), '/sendMessage'))->map(fn ($p) => (string) $p[0]['text'])->values()->all();
    }

    private function sentTo(string $method): int
    {
        return Http::recorded()->filter(fn ($p) => str_contains($p[0]->url(), "/{$method}"))->count();
    }

    private function used(): int
    {
        return (int) DB::table('usage_records')->where('tenant_id', 'tnt_a')->where('metric', 'ai_tokens')->sum('quantity');
    }

    public function test_english_voice_note_is_transcribed_answered_in_text_and_spoken_back(): void
    {
        $this->fake();
        (new TelegramBot())->handleMessage(['chat' => ['id' => 7001], 'voice' => ['file_id' => 'f1', 'duration' => 6]]);

        $this->assertContains('🎙 how many leads this month', $this->texts());
        $this->assertContains('You have 3 new leads.', $this->texts());
        Http::assertSent(fn ($r) => str_ends_with($r->url(), '/audio/speech') && $r['model'] === 'canopylabs/orpheus-v1-english');
        $this->assertSame(1, $this->sentTo('sendVoice') + $this->sentTo('sendDocument'));   // OGG when ffmpeg exists, else the WAV as a file
        $this->assertGreaterThan(220, $this->used());   // audio (100) + chat (120) + speech are all metered
    }

    public function test_russian_voice_note_gets_a_text_answer_only(): void
    {
        $this->fake('russian');
        (new TelegramBot())->handleMessage(['chat' => ['id' => 7001], 'voice' => ['file_id' => 'f1', 'duration' => 6]]);
        $this->assertContains('You have 3 new leads.', $this->texts());
        Http::assertNotSent(fn ($r) => str_ends_with($r->url(), '/audio/speech'));
    }

    public function test_photo_is_read_by_the_vision_model(): void
    {
        $this->fake();
        (new TelegramBot())->handleMessage(['chat' => ['id' => 7001], 'photo' => [['file_id' => 's'], ['file_id' => 'l']], 'caption' => 'what is on this receipt?']);
        Http::assertSent(fn ($r) => str_ends_with($r->url(), '/chat/completions') && $r['model'] === 'qwen/qwen3.8-27b'
            && $r['messages'][1]['content'][0]['text'] === 'what is on this receipt?'
            && str_starts_with($r['messages'][1]['content'][1]['image_url']['url'], 'data:image/jpeg;base64,'));
        $this->assertContains('You have 3 new leads.', $this->texts());
    }

    public function test_media_needs_a_linked_account_and_an_ai_allowance(): void
    {
        $this->fake();
        (new TelegramBot())->handleMessage(['chat' => ['id' => 9999], 'voice' => ['file_id' => 'f1', 'duration' => 3]]);
        Http::assertNotSent(fn ($r) => str_contains($r->url(), 'groq.com'));
        $this->assertStringContainsString('ulang', implode(' ', $this->texts()));

        DB::table('plans')->where('id', 'plan_business')->update(['ai_minutes' => 0]);
        (new TelegramBot())->handleMessage(['chat' => ['id' => 7001], 'voice' => ['file_id' => 'f1', 'duration' => 3]]);
        Http::assertNotSent(fn ($r) => str_contains($r->url(), 'groq.com'));
        $this->assertStringContainsString('AI daqiqalari yo‘q', implode(' ', $this->texts()));
    }

    public function test_exhausted_allowance_blocks_new_requests(): void
    {
        $this->fake();
        DB::table('usage_records')->insert(['id' => 'use_1', 'tenant_id' => 'tnt_a', 'metric' => 'ai_tokens', 'quantity' => 300000, 'limit_value' => 300000, 'period' => gmdate('Y-m'), 'recorded_at' => '2026-01-01 00:00:00']);
        (new TelegramBot())->handleMessage(['chat' => ['id' => 7001], 'text' => 'stats?']);
        Http::assertNotSent(fn ($r) => str_contains($r->url(), 'groq.com'));
        $this->assertStringContainsString('AI limiti tugadi', implode(' ', $this->texts()));
    }

    public function test_image_command_is_off_without_a_provider_and_sends_a_photo_when_configured(): void
    {
        $this->fake();
        (new TelegramBot())->handleMessage(['chat' => ['id' => 7001], 'text' => '/image a laptop on a desk']);
        $this->assertStringContainsString('yoqilmagan', implode(' ', $this->texts()));
        $this->assertSame(0, $this->sentTo('sendPhoto'));

        config(['ai.image.api_key' => 'img-key']);
        (new TelegramBot())->handleMessage(['chat' => ['id' => 7001], 'text' => '/image a laptop on a desk']);
        Http::assertSent(fn ($r) => str_ends_with($r->url(), '/images/generations') && $r['prompt'] === 'a laptop on a desk');
        $this->assertSame(1, $this->sentTo('sendPhoto'));
        $this->assertGreaterThanOrEqual(2000, $this->used());
    }

    private function wav(string $pcm): string
    {
        return 'RIFF' . pack('V', 36 + strlen($pcm)) . 'WAVEfmt ' . pack('V', 16) . pack('vvVVvv', 1, 1, 24000, 48000, 2, 16) . 'data' . pack('V', strlen($pcm)) . $pcm;
    }

    public function test_speech_is_split_into_200_character_requests_and_merged(): void
    {
        $sentence = 'This sentence is about forty characters.';
        $chunks = Speech::chunks(str_repeat($sentence . ' ', 40));   // ~1650 characters
        $this->assertCount(Speech::MAX_CHUNKS, $chunks);
        foreach ($chunks as $c) $this->assertLessThanOrEqual(200, mb_strlen($c));
        $this->assertSame(['Short.'], Speech::chunks('**Short.**'));

        $merged = Speech::mergeWav([$this->wav('aaaa'), $this->wav('bbbb')]);
        $this->assertSame(44 + 8, strlen($merged));
        $this->assertSame('aaaabbbb', substr($merged, 44));
        $this->assertSame(8, unpack('V', substr($merged, 40, 4))[1]);
    }

    public function test_long_english_answer_is_spoken_in_several_valid_requests(): void
    {
        $this->fake();
        Http::fake([
            'api.groq.com/openai/v1/audio/speech' => Http::response($this->wav('1234')),
            'api.groq.com/openai/v1/chat/completions' => Http::response(['choices' => [['message' => ['content' => str_repeat('You have three new leads this month. ', 15)]]], 'model' => 'm', 'usage' => ['total_tokens' => 50]]),
            'api.groq.com/openai/v1/audio/transcriptions' => Http::response(['text' => 'report', 'language' => 'english', 'duration' => 3.0]),
            'api.telegram.org/*/getFile*' => Http::response(['ok' => true, 'result' => ['file_path' => 'v/1.oga', 'file_size' => 10]]),
            'api.telegram.org/file/*' => Http::response('OGG'),
            'api.telegram.org/*' => Http::response(['ok' => true]),
        ]);
        (new TelegramBot())->handleMessage(['chat' => ['id' => 7001], 'voice' => ['file_id' => 'f1', 'duration' => 3]]);
        $speech = Http::recorded()->filter(fn ($p) => str_ends_with($p[0]->url(), '/audio/speech'));
        $this->assertGreaterThan(1, $speech->count());
        $this->assertLessThanOrEqual(Speech::MAX_CHUNKS, $speech->count());
        foreach ($speech as $p) $this->assertLessThanOrEqual(200, mb_strlen((string) $p[0]['input']));
    }
}
