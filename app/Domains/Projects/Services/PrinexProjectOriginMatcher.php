<?php

declare(strict_types=1);

namespace App\Domains\Projects\Services;

use App\Domains\Projects\DTO\ProjectSummaryData;

final class PrinexProjectOriginMatcher
{
    public function __construct(
        private readonly ProjectGitOriginReader $originReader,
    ) {}

    public function isPrinexProject(string $rootPath): bool
    {
        if (! (bool) config('mcp.projects_filter.prinex_only', true)) {
            return true;
        }

        $origin = $this->originReader->readOriginUrl($rootPath);
        if ($origin === null) {
            return false;
        }

        return $this->matchesPrinexOrigin($origin);
    }

    public function matchesPrinexOrigin(string $originUrl): bool
    {
        $normalized = strtolower($originUrl);
        $markers = config('mcp.projects_filter.origin_markers', []);

        if (! is_array($markers)) {
            return false;
        }

        foreach ($markers as $marker) {
            if (! is_string($marker) || $marker === '') {
                continue;
            }

            if (str_contains($normalized, strtolower($marker))) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  list<ProjectSummaryData>  $projects
     * @return list<ProjectSummaryData>
     */
    public function filter(array $projects): array
    {
        return array_values(array_filter(
            $projects,
            fn (ProjectSummaryData $project): bool => $this->isPrinexProject($project->rootPath),
        ));
    }
}
