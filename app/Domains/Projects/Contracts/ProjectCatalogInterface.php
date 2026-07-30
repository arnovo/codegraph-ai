<?php

declare(strict_types=1);

namespace App\Domains\Projects\Contracts;

use App\Domains\Projects\DTO\ProjectSummaryData;

interface ProjectCatalogInterface
{
    /**
     * @return list<ProjectSummaryData>
     */
    public function list(): array;

    public function index(string $repoPath): ProjectSummaryData;

    public function cloneFromBitbucket(
        string $repositoryUrl,
        string $username,
        string $apiToken,
    ): ProjectSummaryData;

    public function delete(string $name): void;
}
