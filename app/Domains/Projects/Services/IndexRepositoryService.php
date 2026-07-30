<?php

declare(strict_types=1);

namespace App\Domains\Projects\Services;

use App\Domains\Mcp\Contracts\McpClientInterface;
use App\Domains\Mcp\DTO\McpProjectData;
use App\Domains\Projects\DTO\ProjectSummaryData;
use InvalidArgumentException;

final class IndexRepositoryService
{
    public function __construct(
        private readonly McpClientInterface $mcpClient,
        private readonly string $reposContainerPath,
        private readonly ProjectStackResolver $stackResolver,
    ) {}

    public function execute(string $repoPath): ProjectSummaryData
    {
        $normalizedPath = $this->assertPathAllowed($repoPath);

        $result = $this->mcpClient->callTool('index_repository', [
            'repo_path' => $normalizedPath,
        ]);

        if (is_array($result)) {
            $dto = McpProjectData::fromArray($result);

            return $this->summarize($dto->name, $dto->rootPath, $dto->nodes, $dto->edges, $dto->sizeBytes);
        }

        return $this->summarize(basename($normalizedPath), $normalizedPath, 0, 0, 0);
    }

    private function summarize(string $name, string $rootPath, int $nodes, int $edges, int $sizeBytes): ProjectSummaryData
    {
        return new ProjectSummaryData(
            name: $name,
            rootPath: $rootPath,
            nodes: $nodes,
            edges: $edges,
            sizeBytes: $sizeBytes,
            displayName: $this->stackResolver->displayName($name, $rootPath),
            primaryStack: $this->stackResolver->resolve($name, $rootPath, true),
        );
    }

    private function assertPathAllowed(string $repoPath): string
    {
        $base = rtrim($this->reposContainerPath, '/');
        $path = str_replace('\\', '/', $repoPath);
        $realBase = $base;
        $candidate = str_starts_with($path, $base)
            ? $path
            : $base.'/'.ltrim($path, '/');

        $normalized = preg_replace('#/+#', '/', $candidate) ?? $candidate;

        if (! str_starts_with($normalized, $realBase.'/') && $normalized !== $realBase) {
            throw new InvalidArgumentException(
                sprintf('repo_path must be under %s', $realBase),
            );
        }

        return $normalized;
    }
}
