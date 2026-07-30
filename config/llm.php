<?php

declare(strict_types=1);

return [
    'driver' => env('LLM_DRIVER', 'openai'),
    'api_key' => env('LLM_API_KEY'),
    'base_url' => env('LLM_BASE_URL', 'https://api.openai.com/v1'),
    'model' => env('LLM_MODEL', 'gpt-4o-mini'),
    'temperature' => (float) env('LLM_TEMPERATURE', 0.2),
    'max_tokens' => (int) env('LLM_MAX_TOKENS', 4096),
    'timeout' => (int) env('LLM_TIMEOUT_SECONDS', 120),
    'max_tool_iterations' => (int) env('LLM_MAX_TOOL_ITERATIONS', 3),
    'stream' => filter_var(env('LLM_STREAM', true), FILTER_VALIDATE_BOOL),
    'log_traffic' => filter_var(env('LLM_LOG_TRAFFIC', true), FILTER_VALIDATE_BOOL),
    'azure' => [
        'api_key' => env('LLM_AZURE_API_KEY'),
        'endpoint' => env('LLM_AZURE_ENDPOINT'),
        'deployment' => env('LLM_AZURE_DEPLOYMENT'),
        'api_version' => env('LLM_AZURE_API_VERSION', '2024-08-01-preview'),
    ],
];
