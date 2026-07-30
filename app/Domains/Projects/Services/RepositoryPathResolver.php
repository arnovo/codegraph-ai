<?php

declare(strict_types=1);

namespace App\Domains\Projects\Services;

final class RepositoryPathResolver
{
    public function resolveAccessiblePath(string $rootPath): string
    {
        $normalized = str_replace('\\', '/', $rootPath);

        if ($this->isAccessibleRepoPath($normalized)) {
            return $normalized;
        }

        $hostPath = rtrim(str_replace('\\', '/', (string) config('mcp.repos.host_path')), '/');
        $containerPath = rtrim(str_replace('\\', '/', (string) config('mcp.repos.container_path')), '/');

        if ($hostPath === '' || $containerPath === '' || $hostPath === $containerPath) {
            return $normalized;
        }

        if (! str_starts_with($normalized, $hostPath.'/') && $normalized !== $hostPath) {
            return $normalized;
        }

        $suffix = substr($normalized, strlen($hostPath));
        $mapped = $containerPath.$suffix;

        if ($this->isAccessibleRepoPath($mapped)) {
            return $mapped;
        }

        return $normalized;
    }

    private function isAccessibleRepoPath(string $path): bool
    {
        return is_dir($path) || is_file($path.'/.git') || is_dir($path.'/.git');
    }
}
