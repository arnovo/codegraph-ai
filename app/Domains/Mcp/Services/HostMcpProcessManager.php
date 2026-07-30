<?php

declare(strict_types=1);

namespace App\Domains\Mcp\Services;

use App\Domains\Mcp\Contracts\McpClientInterface;
use App\Domains\Mcp\Contracts\McpProcessManagerInterface;
use App\Domains\Mcp\DTO\McpServiceStatusData;
use DateTimeImmutable;

/**
 * MCP runs on the host machine (not Docker). Start/stop are manual;
 * status is derived from RPC health checks only.
 */
final class HostMcpProcessManager implements McpProcessManagerInterface
{
    public function __construct(
        private readonly McpClientInterface $mcpClient,
        private readonly string $uiUrl,
    ) {}

    public function start(): McpServiceStatusData
    {
        if ($this->mcpClient->isHealthy()) {
            return $this->status();
        }

        return new McpServiceStatusData(
            status: McpServiceStatusData::STATUS_STOPPED,
            checkedAt: new DateTimeImmutable,
            uiUrl: $this->uiUrl,
            message: 'Ejecuta en el host: codebase-memory-mcp --ui=true --port=9749',
        );
    }

    public function stop(): McpServiceStatusData
    {
        return new McpServiceStatusData(
            status: McpServiceStatusData::STATUS_STOPPED,
            checkedAt: new DateTimeImmutable,
            uiUrl: $this->uiUrl,
            message: 'MCP corre en el host — detén el proceso manualmente (Ctrl+C).',
        );
    }

    public function status(): McpServiceStatusData
    {
        $checkedAt = new DateTimeImmutable;

        if ($this->mcpClient->isHealthy()) {
            return new McpServiceStatusData(
                status: McpServiceStatusData::STATUS_RUNNING,
                checkedAt: $checkedAt,
                uiUrl: $this->uiUrl,
            );
        }

        return new McpServiceStatusData(
            status: McpServiceStatusData::STATUS_STOPPED,
            checkedAt: $checkedAt,
            uiUrl: $this->uiUrl,
            message: 'MCP no responde. Ejecuta: codebase-memory-mcp --ui=true --port=9749',
        );
    }
}
