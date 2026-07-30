<?php

declare(strict_types=1);

namespace App\Domains\Agent\Infrastructure\Llm;

use App\Domains\Agent\Contracts\LlmClientInterface;
use App\Domains\Agent\DTO\LlmResponseData;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use JsonException;
use RuntimeException;

final class OpenAiCompatibleLlmClient implements LlmClientInterface
{
    public function __construct(
        private readonly Client $httpClient,
        private readonly string $apiKey,
        private readonly string $baseUrl,
        private readonly string $model,
        private readonly float $temperature,
        private readonly int $maxTokens,
        private readonly int $timeout,
        private readonly bool $stream,
        private readonly string $provider = 'openai',
    ) {}

    public function chat(array $messages, array $tools = []): LlmResponseData
    {
        return $this->request($messages, $tools, null);
    }

    public function chatStream(array $messages, array $tools, callable $onChunk): LlmResponseData
    {
        return $this->request($messages, $tools, $onChunk);
    }

    /**
     * @param  list<array<string, mixed>>  $messages
     * @param  list<array<string, mixed>>  $tools
     */
    private function request(array $messages, array $tools, ?callable $onChunk): LlmResponseData
    {
        $payload = [
            'model' => $this->model,
            'messages' => $messages,
            'temperature' => $this->temperature,
            'max_tokens' => $this->maxTokens,
            'stream' => $onChunk !== null && $this->stream,
        ];

        if ($tools !== []) {
            $payload['tools'] = $tools;
            $payload['tool_choice'] = 'auto';
        }

        $options = [
            'json' => $payload,
            'headers' => array_filter([
                'Authorization' => $this->apiKey !== '' ? 'Bearer '.$this->apiKey : null,
                'Content-Type' => 'application/json',
            ]),
            'timeout' => $this->timeout,
        ];

        $url = rtrim($this->baseUrl, '/').'/chat/completions';

        try {
            if ($onChunk !== null && $this->stream) {
                return $this->streamResponse($url, $options, $onChunk);
            }

            $response = $this->httpClient->post($url, $options);
            $body = json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR);

            return $this->parseCompletion($body);
        } catch (GuzzleException|JsonException $e) {
            throw new RuntimeException('LLM request failed: '.$this->formatRequestError($e), 0, $e);
        }
    }

    private function formatRequestError(GuzzleException|JsonException $e): string
    {
        if ($e instanceof GuzzleException && method_exists($e, 'getResponse') && $e->getResponse() !== null) {
            $body = (string) $e->getResponse()->getBody();
            try {
                $json = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
                $error = $json['error'] ?? (is_array($json[0] ?? null) ? ($json[0]['error'] ?? null) : null);
                if (is_array($error) && isset($error['message'])) {
                    return (string) $error['message'];
                }
            } catch (JsonException) {
                /* fall through */
            }
        }

        return $e->getMessage();
    }

    /**
     * @param  array<string, mixed>  $options
     */
    private function streamResponse(string $url, array $options, callable $onChunk): LlmResponseData
    {
        $options['stream'] = true;
        $response = $this->httpClient->post($url, $options);
        $stream = $response->getBody();
        $buffer = '';
        $fullContent = '';
        $toolCalls = [];

        while (! $stream->eof()) {
            $buffer .= $stream->read(1024);
            while (($pos = strpos($buffer, "\n")) !== false) {
                $line = trim(substr($buffer, 0, $pos));
                $buffer = substr($buffer, $pos + 1);

                if ($line === '' || ! str_starts_with($line, 'data:')) {
                    continue;
                }

                $data = trim(substr($line, 5));
                if ($data === '[DONE]') {
                    break 2;
                }

                try {
                    $chunk = json_decode($data, true, 512, JSON_THROW_ON_ERROR);
                } catch (JsonException) {
                    continue;
                }

                $delta = $chunk['choices'][0]['delta'] ?? [];
                if (isset($delta['content'])) {
                    $fullContent .= $delta['content'];
                    $onChunk($delta['content']);
                }

                if (isset($delta['tool_calls'])) {
                    foreach ($delta['tool_calls'] as $tc) {
                        $idx = $tc['index'] ?? 0;
                        if (! isset($toolCalls[$idx])) {
                            $toolCalls[$idx] = ['id' => $tc['id'] ?? '', 'type' => 'function', 'function' => ['name' => '', 'arguments' => '']];
                        }
                        if (isset($tc['id']) && $tc['id'] !== '') {
                            $toolCalls[$idx]['id'] = $tc['id'];
                        }
                        if (isset($tc['function']['name'])) {
                            $toolCalls[$idx]['function']['name'] .= $tc['function']['name'];
                        }
                        if (isset($tc['function']['arguments'])) {
                            $toolCalls[$idx]['function']['arguments'] .= $tc['function']['arguments'];
                        }
                    }
                }
            }
        }

        return new LlmResponseData(
            content: $fullContent !== '' ? $fullContent : null,
            toolCalls: array_values(array_map(function (array $toolCall): array {
                if (($toolCall['id'] ?? '') === '') {
                    $toolCall['id'] = 'call_'.bin2hex(random_bytes(8));
                }

                return $toolCall;
            }, $toolCalls)),
            model: $this->model,
            provider: $this->provider,
            finishReason: $toolCalls !== [] ? 'tool_calls' : 'stop',
        );
    }

    /** @param  array<string, mixed>  $body */
    private function parseCompletion(array $body): LlmResponseData
    {
        $choice = $body['choices'][0] ?? [];
        $message = $choice['message'] ?? [];

        return new LlmResponseData(
            content: $message['content'] ?? null,
            toolCalls: $message['tool_calls'] ?? [],
            model: (string) ($body['model'] ?? $this->model),
            provider: $this->provider,
            usage: $body['usage'] ?? [],
            finishReason: $choice['finish_reason'] ?? null,
        );
    }
}
