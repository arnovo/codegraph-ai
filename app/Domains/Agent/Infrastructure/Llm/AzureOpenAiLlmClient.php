<?php

declare(strict_types=1);

namespace App\Domains\Agent\Infrastructure\Llm;

use App\Domains\Agent\Contracts\LlmClientInterface;
use App\Domains\Agent\DTO\LlmResponseData;
use GuzzleHttp\Client;

final class AzureOpenAiLlmClient implements LlmClientInterface
{
    private readonly OpenAiCompatibleLlmClient $inner;

    /**
     * @param  array<string, mixed>  $config
     */
    public function __construct(Client $httpClient, array $config)
    {
        $endpoint = rtrim((string) ($config['azure']['endpoint'] ?? ''), '/');
        $deployment = (string) ($config['azure']['deployment'] ?? '');
        $apiVersion = (string) ($config['azure']['api_version'] ?? '2024-08-01-preview');

        $baseUrl = sprintf(
            '%s/openai/deployments/%s',
            $endpoint,
            $deployment,
        );

        $this->inner = new OpenAiCompatibleLlmClient(
            httpClient: $httpClient,
            apiKey: (string) ($config['azure']['api_key'] ?? ''),
            baseUrl: $baseUrl.'?api-version='.$apiVersion,
            model: $deployment,
            temperature: (float) ($config['temperature'] ?? 0.2),
            maxTokens: (int) ($config['max_tokens'] ?? 4096),
            timeout: (int) ($config['timeout'] ?? 120),
            stream: (bool) ($config['stream'] ?? true),
            provider: 'azure',
        );
    }

    public function chat(array $messages, array $tools = []): LlmResponseData
    {
        return $this->inner->chat($messages, $tools);
    }

    public function chatStream(array $messages, array $tools, callable $onChunk): LlmResponseData
    {
        return $this->inner->chatStream($messages, $tools, $onChunk);
    }
}
