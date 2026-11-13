<?php

return [
    'provider' => env('AI_PROVIDER', 'groq'),
    // GROQ_API_KEY is the canonical name; AI_API_KEY is kept as a compatible
    // alias so deployments do not silently run without a provider token.
    'api_key' => env('GROQ_API_KEY', env('AI_API_KEY', '')),
    'model' => env('GROQ_MODEL', 'openai/gpt-oss-120b'),
    'base_url' => rtrim(env('GROQ_BASE_URL', 'https://api.groq.com/openai/v1'), '/'),
    'timeout' => (int) env('AI_TIMEOUT_SECONDS', 30),
    'max_tokens' => (int) env('AI_MAX_TOKENS', 2048),
    'ca_bundle' => env('AI_CA_BUNDLE'),

    // Telegram bot media. Models are Groq's; ids can change (previews), so each is overridable.
    'stt_model' => env('GROQ_STT_MODEL', 'whisper-large-v3-turbo'),
    // Groq's TTS covers English (and Saudi Arabic) only: other languages are answered in text.
    'tts_model' => env('GROQ_TTS_MODEL', 'canopylabs/orpheus-v1-english'),
    'tts_voice' => env('GROQ_TTS_VOICE', 'hannah'),
    'vision_model' => env('GROQ_VISION_MODEL', 'qwen/qwen3.8-27b'),
    'voice_replies' => (bool) env('TELEGRAM_VOICE_REPLIES', true),

    // Image creation is not offered by Groq. Any OpenAI-compatible /images/generations endpoint works
    // (OpenAI, Together, ...). Leave IMAGE_API_KEY empty to keep /image switched off.
    'image' => [
        'base_url' => rtrim(env('IMAGE_API_BASE_URL', 'https://api.openai.com/v1'), '/'),
        'api_key' => env('IMAGE_API_KEY', ''),
        'model' => env('IMAGE_MODEL', 'gpt-image-1'),
        'size' => env('IMAGE_SIZE', '1024x1024'),
    ],
];
