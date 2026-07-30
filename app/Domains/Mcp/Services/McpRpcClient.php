<?php

declare(strict_types=1);

namespace App\Domains\Mcp\Services;

use App\Domains\Mcp\Contracts\McpClientInterface;
use App\Domains\Mcp\Exceptions\McpUnavailableException;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;
use JsonException;

final class McpRpcClient implements McpClientInterface
{
    private int $requestId = 0;

    public function __construct(
        private readonly Client $httpClient,
        private readonly string $rpcUrl,
        private readonly int $healthTimeout,
        private readonly ?McpCliFallbackClient $fallback = null,
    ) {}

    /**
     * @param  array<string, mixed>  $arguments
     */
    public function callTool(string $name, array $arguments = []): mixed
    {
        try {
            return $this->rpcCall($name, $arguments);
        } catch (McpUnavailableException $exception) {
            if ($this->fallback === null) {
                throw $exception;
            }

            Log::warning('MCP RPC failed, falling back to CLI', [
                'tool' => $name,
                'error' => $exception->getMessage(),
            ]);

            return $this->fallback->callTool($name, $arguments);
        }
    }

    public function isHealthy(): bool
    {
        try {
            $this->rpcCall('list_projects', [], $this->healthTimeout);

            return true;
        } catch (McpUnavailableException) {
            return false;
        }
    }

    /**
     * @param  array<string, mixed>  $arguments
     */
    private function rpcCall(string $name, array $arguments, ?int $timeout = null): mixed
    {
        $payload = [
            'jsonrpc' => '2.0',
            'id' => ++$this->requestId,
            'method' => 'tools/call',
            'params' => [
                'name' => $name,
                'arguments' => $arguments,
            ],
        ];

        $options = [
            'json' => $payload,
            'headers' => ['Content-Type' => 'application/json'],
        ];

        if ($timeout !== null) {
            $options['timeout'] = $timeout;
            $options['connect_timeout'] = $timeout;
        }

        try {
            $response = $this->httpClient->post($this->rpcUrl, $options);
            $body = (string) $response->getBody();
            $decoded = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
        } catch (GuzzleException|JsonException $exception) {
            throw McpUnavailableException::withMessage(
                sprintf('MCP RPC request failed: %s', $exception->getMessage()),
            );
        }

        if (isset($decoded['error'])) {
            $message = is_array($decoded['error'])
                ? (string) ($decoded['error']['message'] ?? 'Unknown MCP error')
                : 'Unknown MCP error';

            throw McpUnavailableException::withMessage($message);
        }

        return $this->extractResult($decoded['result'] ?? $decoded);
    }

    /**
     * @param  mixed  $result
     */
    private function extractResult(mixed $result): mixed
    {
        if (! is_array($result)) {
            return $result;
        }

        if (array_key_exists('content', $result)) {
            $content = $result['content'];

            if (is_array($content) && isset($content[0]['text'])) {
                $text = $content[0]['text'];

                try {
                    return json_decode($text, true, 512, JSON_THROW_ON_ERROR);
                } catch (JsonException) {
                    return $text;
                }
            }
        }

        return $result;
    }
}
