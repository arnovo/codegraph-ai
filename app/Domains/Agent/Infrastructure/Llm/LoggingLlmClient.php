<?php

declare(strict_types=1);

namespace App\Domains\Agent\Infrastructure\Llm;

use App\Domains\Agent\Contracts\LlmClientInterface;
use App\Domains\Agent\DTO\LlmResponseData;
use App\Domains\Agent\Services\LlmTrafficLogger;
use Throwable;

final class LoggingLlmClient implements LlmClientInterface
{
    public function __construct(
        private readonly LlmClientInterface $inner,
        private readonly LlmTrafficLogger $logger,
        private readonly string $model,
        private readonly string $provider,
    ) {}

    public function chat(array $messages, array $tools = []): LlmResponseData
    {
        $started = microtime(true);
        $this->logger->logRequest('chat', $messages, $tools, $this->model, $this->provider);

        try {
            $response = $this->inner->chat($messages, $tools);
            $this->logger->logResponse($response, (microtime(true) - $started) * 1000);

            return $response;
        } catch (Throwable $e) {
            $this->logger->logError($e->getMessage(), (microtime(true) - $started) * 1000);
            throw $e;
        }
    }

    public function chatStream(array $messages, array $tools, callable $onChunk): LlmResponseData
    {
        $started = microtime(true);
        $this->logger->logRequest('stream', $messages, $tools, $this->model, $this->provider);

        try {
            $response = $this->inner->chatStream($messages, $tools, $onChunk);
            $this->logger->logResponse($response, (microtime(true) - $started) * 1000);

            return $response;
        } catch (Throwable $e) {
            $this->logger->logError($e->getMessage(), (microtime(true) - $started) * 1000);
            throw $e;
        }
    }
}
