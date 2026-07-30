<?php

declare(strict_types=1);

namespace App\Domains\Agent\Services;

use App\Domains\Agent\DTO\LlmResponseData;
use App\Support\LlmErrorFormatter;
use App\Support\LogSanitizer;
use Illuminate\Support\Facades\Log;

final class LlmTrafficLogger
{
    /**
     * @param  list<array<string, mixed>>  $messages
     * @param  list<array<string, mixed>>  $tools
     */
    public function logRequest(string $mode, array $messages, array $tools, string $model, string $provider): void
    {
        if (! config('llm.log_traffic', true)) {
            return;
        }

        Log::channel('llm')->info('llm.request', [
            'mode' => $mode,
            'provider' => $provider,
            'model' => $model,
            'message_count' => count($messages),
            'tool_count' => count($tools),
            'messages' => LogSanitizer::sanitizeMessages($messages),
            'tools' => array_column(array_column($tools, 'function'), 'name'),
        ]);
    }

    public function logResponse(LlmResponseData $response, float $durationMs): void
    {
        if (! config('llm.log_traffic', true)) {
            return;
        }

        Log::channel('llm')->info('llm.response', [
            'provider' => $response->provider,
            'model' => $response->model,
            'finish_reason' => $response->finishReason,
            'tool_calls' => count($response->toolCalls),
            'content_preview' => LogSanitizer::truncate(LogSanitizer::redact((string) ($response->content ?? '')), 400),
            'duration_ms' => round($durationMs, 1),
            'usage' => $response->usage,
        ]);
    }

    public function logError(string $message, float $durationMs): void
    {
        if (! config('llm.log_traffic', true)) {
            return;
        }

        Log::channel('llm')->error('llm.error', [
            'message' => LogSanitizer::redact($message),
            'duration_ms' => round($durationMs, 1),
            'driver' => config('llm.driver'),
            'model' => config('llm.model'),
            'base_url' => config('llm.base_url'),
            'api_key' => LlmErrorFormatter::truncateSecret((string) config('llm.api_key')),
        ]);
    }

    public function logFallbackAttempt(string $fromModel, string $toModel, string $reason): void
    {
        if (! config('llm.log_traffic', true)) {
            return;
        }

        Log::channel('llm')->warning('llm.fallback', [
            'from_model' => $fromModel,
            'to_model' => $toModel,
            'reason' => LogSanitizer::truncate(LogSanitizer::redact($reason), 280),
        ]);
    }

    public function logFallbackSuccess(string $primaryModel, string $usedModel, int $attemptIndex): void
    {
        if (! config('llm.log_traffic', true)) {
            return;
        }

        Log::channel('llm')->info('llm.fallback_success', [
            'primary_model' => $primaryModel,
            'used_model' => $usedModel,
            'attempt_index' => $attemptIndex,
        ]);
    }
}
