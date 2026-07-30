<?php

declare(strict_types=1);

namespace App\Domains\Projects\Services;

use App\Domains\Projects\Contracts\ProjectCatalogInterface;
use App\Domains\Projects\DTO\ProjectSummaryData;

final class ProjectCatalogService implements ProjectCatalogInterface
{
    public function __construct(
        private readonly ListIndexedProjectsService $listProjects,
        private readonly IndexRepositoryService $indexRepository,
        private readonly CloneAndIndexRepositoryService $cloneAndIndexRepository,
        private readonly DeleteIndexedProjectService $deleteProject,
    ) {}

    public function list(): array
    {
        return $this->listProjects->execute();
    }

    public function index(string $repoPath): ProjectSummaryData
    {
        return $this->indexRepository->execute($repoPath);
    }

    public function cloneFromBitbucket(
        string $repositoryUrl,
        string $username,
        string $apiToken,
    ): ProjectSummaryData {
        return $this->cloneAndIndexRepository->execute(
            repositoryUrl: $repositoryUrl,
            username: $username,
            apiToken: $apiToken,
        );
    }

    public function delete(string $name): void
    {
        $this->deleteProject->execute($name);
    }
}
