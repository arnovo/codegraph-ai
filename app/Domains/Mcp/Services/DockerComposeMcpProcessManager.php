<?php

declare(strict_types=1);

namespace App\Domains\Mcp\Services;

use App\Domains\Mcp\Contracts\McpClientInterface;
use App\Domains\Mcp\Contracts\McpProcessManagerInterface;
use App\Domains\Mcp\DTO\McpServiceStatusData;
use DateTimeImmutable;
use Illuminate\Support\Facades\Process;

final class DockerComposeMcpProcessManager implements McpProcessManagerInterface
{
    public function __construct(
        private readonly McpClientInterface $mcpClient,
        private readonly string $workingDirectory,
        private readonly string $composeFile,
        private readonly string $serviceName,
        private readonly string $uiUrl,
    ) {}

    public function start(): McpServiceStatusData
    {
        $this->runCompose(['up', '-d', $this->serviceName]);

        return $this->status();
    }

    public function stop(): McpServiceStatusData
    {
        $this->runCompose(['stop', $this->serviceName]);

        return new McpServiceStatusData(
            status: McpServiceStatusData::STATUS_STOPPED,
            checkedAt: new DateTimeImmutable,
            uiUrl: $this->uiUrl,
            message: 'Servicio MCP detenido.',
        );
    }

    public function status(): McpServiceStatusData
    {
        $checkedAt = new DateTimeImmutable;

        $psResult = Process::path($this->workingDirectory)->run([
            'docker', 'compose',
            '-f', $this->composeFile,
            'ps', '--status', 'running', '--services',
        ]);

        if (! $psResult->successful()) {
            return new McpServiceStatusData(
                status: McpServiceStatusData::STATUS_UNKNOWN,
                checkedAt: $checkedAt,
                uiUrl: $this->uiUrl,
                message: 'No se pudo comprobar el estado del servicio MCP.',
            );
        }

        $runningServices = array_filter(array_map('trim', explode("\n", trim($psResult->output()))));
        $isRunning = in_array($this->serviceName, $runningServices, true);

        if (! $isRunning) {
            return new McpServiceStatusData(
                status: McpServiceStatusData::STATUS_STOPPED,
                checkedAt: $checkedAt,
                uiUrl: $this->uiUrl,
                message: 'El servicio MCP no está en ejecución.',
            );
        }

        if (! $this->mcpClient->isHealthy()) {
            return new McpServiceStatusData(
                status: McpServiceStatusData::STATUS_UNHEALTHY,
                checkedAt: $checkedAt,
                uiUrl: $this->uiUrl,
                message: 'MCP en ejecución pero no responde al health check.',
            );
        }

        return new McpServiceStatusData(
            status: McpServiceStatusData::STATUS_RUNNING,
            checkedAt: $checkedAt,
            uiUrl: $this->uiUrl,
        );
    }

    /**
     * @param  list<string>  $args
     */
    private function runCompose(array $args): void
    {
        $command = array_merge(
            ['docker', 'compose', '-f', $this->composeFile],
            $args,
        );

        $result = Process::path($this->workingDirectory)->timeout(120)->run($command);

        if (! $result->successful()) {
            throw new \RuntimeException(
                trim($result->errorOutput() ?: $result->output()) ?: 'docker compose command failed',
            );
        }
    }
}
