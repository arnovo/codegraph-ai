<?php

declare(strict_types=1);

namespace App\Domains\Projects\Services;

use App\Domains\Mcp\Contracts\McpClientInterface;

final class DeleteIndexedProjectService
{
    public function __construct(
        private readonly McpClientInterface $mcpClient,
    ) {}

    public function execute(string $name): void
    {
        $this->mcpClient->callTool('delete_project', [
            'name' => $name,
        ]);
    }
}
