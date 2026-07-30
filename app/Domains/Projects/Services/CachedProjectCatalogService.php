<?php

declare(strict_types=1);

namespace App\Domains\Projects\Services;

use App\Domains\Agent\Services\McpToolResultCache;
use App\Domains\Projects\Contracts\ProjectCatalogInterface;
use App\Domains\Projects\DTO\ProjectSummaryData;
use Illuminate\Support\Facades\Cache;

final class CachedProjectCatalogService implements ProjectCatalogInterface
{
    private const CACHE_KEY = 'mcp.projects.list';

    private const CACHE_AT_KEY = 'mcp.projects.list.cached_at';

    public function __construct(
        private readonly ProjectCatalogService $inner,
        private readonly ListIndexedProjectsService $listProjects,
        private readonly McpToolResultCache $toolCache,
    ) {}

    public function list(): array
    {
        $cached = Cache::get(self::CACHE_KEY);

        if (is_array($cached)) {
            $this->refreshIfStale();

            return array_map(
                fn (array $row) => ProjectSummaryData::fromArray($row),
                $cached,
            );
        }

        return $this->fetchAndCache();
    }

    public function index(string $repoPath): ProjectSummaryData
    {
        $project = $this->inner->index($repoPath);
        $this->invalidate();
        $this->toolCache->invalidateProject($project->name);

        return $project;
    }

    public function cloneFromBitbucket(
        string $repositoryUrl,
        string $username,
        string $apiToken,
    ): ProjectSummaryData {
        $project = $this->inner->cloneFromBitbucket(
            repositoryUrl: $repositoryUrl,
            username: $username,
            apiToken: $apiToken,
        );
        $this->invalidate();
        $this->toolCache->invalidateProject($project->name);

        return $project;
    }

    public function delete(string $name): void
    {
        $this->inner->delete($name);
        $this->invalidate();
        $this->toolCache->invalidateProject($name);
    }

    /** @return list<ProjectSummaryData> */
    private function fetchAndCache(): array
    {
        $projects = $this->inner->list();
        $this->store($projects);

        return $projects;
    }

    private function refreshIfStale(): void
    {
        $cachedAt = (int) Cache::get(self::CACHE_AT_KEY, 0);
        $staleAfter = (int) config('mcp.projects_cache_stale_after', 30);

        if ($cachedAt > 0 && (time() - $cachedAt) <= $staleAfter) {
            return;
        }

        app()->terminating(function (): void {
            if (Cache::get('mcp.projects.list.refreshing')) {
                return;
            }

            Cache::put('mcp.projects.list.refreshing', true, 60);

            try {
                $projects = $this->listProjects->execute(deepStack: true);
                $this->store($projects);
            } finally {
                Cache::forget('mcp.projects.list.refreshing');
            }
        });
    }

    /** @param list<ProjectSummaryData> $projects */
    private function store(array $projects): void
    {
        Cache::put(
            self::CACHE_KEY,
            array_map(fn (ProjectSummaryData $p) => $p->toArray(), $projects),
            (int) config('mcp.projects_cache_ttl', 300),
        );
        Cache::put(self::CACHE_AT_KEY, time(), (int) config('mcp.projects_cache_ttl', 300));
    }

    private function invalidate(): void
    {
        Cache::forget(self::CACHE_KEY);
        Cache::forget(self::CACHE_AT_KEY);
    }
}
