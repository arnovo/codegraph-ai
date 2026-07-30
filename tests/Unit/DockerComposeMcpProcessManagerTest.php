<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Domains\Mcp\Contracts\McpClientInterface;
use App\Domains\Mcp\DTO\McpServiceStatusData;
use App\Domains\Mcp\Services\DockerComposeMcpProcessManager;
use Illuminate\Support\Facades\Process;
use Tests\TestCase;

final class DockerComposeMcpProcessManagerTest extends TestCase
{
    public function test_it_starts_mcp_service_via_docker_compose(): void
    {
        Process::fake([
            '*' => Process::sequence()
                ->push(Process::result())
                ->push(Process::result(output: "mcp\n")),
        ]);

        $manager = $this->makeManager(isHealthy: true);

        $status = $manager->start();

        $this->assertSame(McpServiceStatusData::STATUS_RUNNING, $status->status);

        Process::assertRan(function ($process) {
            $command = implode(' ', $process->command);

            return str_contains($command, 'up -d mcp') || str_contains($command, 'ps --status running');
        });
    }

    public function test_it_reports_stopped_when_service_not_running(): void
    {
        Process::fake([
            '*' => Process::result(output: "app\n"),
        ]);

        $manager = $this->makeManager(isHealthy: false);

        $status = $manager->status();

        $this->assertSame(McpServiceStatusData::STATUS_STOPPED, $status->status);
    }

    private function makeManager(bool $isHealthy): DockerComposeMcpProcessManager
    {
        return new DockerComposeMcpProcessManager(
            mcpClient: new class($isHealthy) implements McpClientInterface
            {
                public function __construct(private readonly bool $healthy) {}

                public function isHealthy(): bool
                {
                    return $this->healthy;
                }

                public function callTool(string $name, array $arguments = []): mixed
                {
                    return [];
                }
            },
            workingDirectory: base_path(),
            composeFile: 'docker-compose.test.yml',
            serviceName: 'mcp',
            uiUrl: 'http://localhost:9749',
        );
    }
}
