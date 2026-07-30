<?php

declare(strict_types=1);

namespace App\Domains\Projects\Services;

use App\Domains\Projects\DTO\ProjectSummaryData;

final class CloneAndIndexRepositoryService
{
    public function __construct(
        private readonly BitbucketRepositoryUrlParser $urlParser,
        private readonly CloneRepositoryService $cloneRepository,
        private readonly IndexRepositoryService $indexRepository,
    ) {}

    public function execute(string $repositoryUrl, string $username, string $apiToken): ProjectSummaryData
    {
        $normalizedUrl = $this->urlParser->normalizeHttpsUrl($repositoryUrl);
        $directoryName = $this->urlParser->directoryName($normalizedUrl);
        $cloneUrl = $this->urlParser->buildAuthenticatedCloneUrl(
            normalizedGitUrl: $normalizedUrl,
            username: $username,
            apiToken: $apiToken,
        );

        $localPath = $this->cloneRepository->execute(
            authenticatedCloneUrl: $cloneUrl,
            directoryName: $directoryName,
        );

        return $this->indexRepository->execute($localPath);
    }
}
