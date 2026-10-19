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
];
