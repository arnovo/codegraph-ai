<?php

declare(strict_types=1);

namespace App\Domains\Mcp\Services;

use App\Domains\Mcp\Contracts\McpClientInterface;
use App\Domains\Mcp\Exceptions\McpUnavailableException;
use Illuminate\Support\Facades\Process;
use JsonException;

final class McpCliFallbackClient implements McpClientInterface
{
    public function __construct(
        private readonly string $binary,
    ) {}

    /**
     * @param  array<string, mixed>  $arguments
     */
    public function callTool(string $name, array $arguments = []): mixed
    {
        try {
            $jsonArgs = json_encode($arguments, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw McpUnavailableException::withMessage(
                sprintf('Failed to encode MCP arguments: %s', $exception->getMessage()),
            );
        }

        $result = Process::timeout(120)->run([
            $this->binary,
            'cli',
            $name,
            $jsonArgs,
        ]);

        if (! $result->successful()) {
            throw McpUnavailableException::withMessage(
                sprintf('MCP CLI failed: %s', trim($result->errorOutput() ?: $result->output())),
            );
        }

        $output = trim($result->output());

        if ($output === '') {
            return null;
        }

        try {
            return json_decode($output, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return $output;
        }
    }

    public function isHealthy(): bool
    {
        try {
            $this->callTool('list_projects');

            return true;
        } catch (McpUnavailableException) {
            return false;
        }
    }
}
