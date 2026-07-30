<?php

declare(strict_types=1);

namespace App\Domains\Mcp\Contracts;

interface McpClientInterface
{
    /**
     * @param  array<string, mixed>  $arguments
     */
    public function callTool(string $name, array $arguments = []): mixed;

    public function isHealthy(): bool;
}
