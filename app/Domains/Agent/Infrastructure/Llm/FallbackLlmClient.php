<?php

declare(strict_types=1);

namespace App\Domains\Agent\Infrastructure\Llm;

use App\Domains\Agent\Contracts\LlmClientInterface;
use App\Domains\Agent\DTO\LlmResponseData;
use App\Domains\Agent\Services\LlmModelCatalogService;
use App\Domains\Agent\Services\LlmTrafficLogger;
use App\Support\LlmRetryPolicy;
use RuntimeException;
use Throwable;

final class FallbackLlmClient implements LlmClientInterface
{
    /**
     * @param  array<string, mixed>  $config
     */
    public function __construct(
        private readonly LlmModelCatalogService $catalog,
        private readonly LlmTrafficLogger $logger,
        private readonly array $config,
    ) {}

    public function chat(array $messages, array $tools = []): LlmResponseData
    {
        return $this->attempt('chat', $messages, $tools, null);
    }

    public function chatStream(array $messages, array $tools, callable $onChunk): LlmResponseData
    {
        return $this->attempt('stream', $messages, $tools, $onChunk);
    }

    /**
     * @param  list<array<string, mixed>>  $messages
     * @param  list<array<string, mixed>>  $tools
     */
    private function attempt(string $mode, array $messages, array $tools, ?callable $onChunk): LlmResponseData
    {
        $configs = $this->catalog->orderedEnabledClientConfigs($this->config);

        if ($configs === []) {
            throw new RuntimeException('No hay modelos LLM configurados.');
        }

        $lastError = null;
        $primaryModel = (string) ($configs[0]['model'] ?? '');

        foreach ($configs as $index => $clientConfig) {
            $model = (string) ($clientConfig['model'] ?? '');
            $client = LlmClientFactory::make($clientConfig);
            $started = microtime(true);
            $this->logger->logRequest(
                $mode,
                $messages,
                $tools,
                $model,
                (string) ($clientConfig['driver'] ?? 'openai'),
            );

            try {
                $response = $onChunk !== null
                    ? $client->chatStream($messages, $tools, $onChunk)
                    : $client->chat($messages, $tools);

                $this->logger->logResponse($response, (microtime(true) - $started) * 1000);

                if ($index > 0) {
                    $this->logger->logFallbackSuccess($primaryModel, $model, $index);
                }

                return $response;
            } catch (Throwable $e) {
                $this->logger->logError($e->getMessage(), (microtime(true) - $started) * 1000);
                $lastError = $e;

                $nextConfig = $configs[$index + 1] ?? null;
                if ($nextConfig !== null && LlmRetryPolicy::isRetryable($e)) {
                    $this->logger->logFallbackAttempt(
                        $model,
                        (string) ($nextConfig['model'] ?? ''),
                        $e->getMessage(),
                    );

                    continue;
                }

                throw $e;
            }
        }

        throw $lastError ?? new RuntimeException('No se pudo completar la petición LLM.');
    }
}
