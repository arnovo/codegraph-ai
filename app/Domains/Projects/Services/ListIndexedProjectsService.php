<?php

declare(strict_types=1);

namespace App\Domains\Projects\Services;

use App\Domains\Mcp\Contracts\McpClientInterface;
use App\Domains\Mcp\DTO\McpProjectData;
use App\Domains\Projects\DTO\ProjectSummaryData;

final class ListIndexedProjectsService
{
    public function __construct(
        private readonly McpClientInterface $mcpClient,
        private readonly ProjectStackResolver $stackResolver,
        private readonly PrinexProjectOriginMatcher $prinexMatcher,
    ) {}

    /**
     * @return list<ProjectSummaryData>
     */
    public function execute(bool $deepStack = false): array
    {
        $result = $this->mcpClient->callTool('list_projects');

        if (! is_array($result)) {
            return [];
        }

        $projects = array_is_list($result) ? $result : ($result['projects'] ?? []);

        return $this->prinexMatcher->filter(array_map(
            fn (array $item) => $this->mapProject($item, $deepStack),
            array_filter($projects, is_array(...)),
        ));
    }

    /**
     * @param  array<string, mixed>  $item
     */
    private function mapProject(array $item, bool $deepStack): ProjectSummaryData
    {
        $dto = McpProjectData::fromArray($item);

        return new ProjectSummaryData(
            name: $dto->name,
            rootPath: $dto->rootPath,
            nodes: $dto->nodes,
            edges: $dto->edges,
            sizeBytes: $dto->sizeBytes,
            displayName: $this->stackResolver->displayName($dto->name, $dto->rootPath),
            primaryStack: $this->stackResolver->resolve($dto->name, $dto->rootPath, $deepStack),
        );
    }
}
