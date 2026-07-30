<?php

declare(strict_types=1);

namespace App\Domains\Agent\Infrastructure\Llm;

use App\Domains\Agent\Contracts\LlmClientInterface;
use GuzzleHttp\Client;
use InvalidArgumentException;

final class LlmClientFactory
{
    /**
     * @param  array<string, mixed>  $config
     */
    public static function make(array $config): LlmClientInterface
    {
        $driver = (string) ($config['driver'] ?? 'openai');
        $client = new Client;

        return match ($driver) {
            'azure' => new AzureOpenAiLlmClient($client, $config),
            'openai', 'custom' => new OpenAiCompatibleLlmClient(
                httpClient: $client,
                apiKey: (string) ($config['api_key'] ?? ''),
                baseUrl: (string) ($config['base_url'] ?? 'https://api.openai.com/v1'),
                model: (string) ($config['model'] ?? 'gpt-4o-mini'),
                temperature: (float) ($config['temperature'] ?? 0.2),
                maxTokens: (int) ($config['max_tokens'] ?? 4096),
                timeout: (int) ($config['timeout'] ?? 120),
                stream: (bool) ($config['stream'] ?? true),
                provider: $driver,
            ),
            default => throw new InvalidArgumentException("Unsupported LLM driver: {$driver}"),
        };
    }
}
