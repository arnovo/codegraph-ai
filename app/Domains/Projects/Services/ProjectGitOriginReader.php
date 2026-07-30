<?php

declare(strict_types=1);

namespace App\Domains\Projects\Services;

final class ProjectGitOriginReader
{
    public function __construct(
        private readonly RepositoryPathResolver $pathResolver,
    ) {}

    public function readOriginUrl(string $rootPath): ?string
    {
        $resolvedPath = $this->pathResolver->resolveAccessiblePath($rootPath);
        $gitDir = $this->resolveGitDir($resolvedPath);
        if ($gitDir === null) {
            return null;
        }

        $configPath = $gitDir.'/config';
        if (! is_readable($configPath)) {
            return null;
        }

        $contents = file_get_contents($configPath);
        if (! is_string($contents)) {
            return null;
        }

        return $this->parseOriginUrl($contents);
    }

    private function resolveGitDir(string $rootPath): ?string
    {
        $base = rtrim(str_replace('\\', '/', $rootPath), '/');
        $dotGit = $base.'/.git';

        if (is_dir($dotGit)) {
            return $dotGit;
        }

        if (! is_file($dotGit) || ! is_readable($dotGit)) {
            return null;
        }

        $contents = trim((string) file_get_contents($dotGit));
        if (! str_starts_with($contents, 'gitdir:')) {
            return null;
        }

        $gitDir = trim(substr($contents, 7));
        if ($gitDir === '') {
            return null;
        }

        if (! str_starts_with($gitDir, '/')) {
            $gitDir = $base.'/'.$gitDir;
        }

        return is_dir($gitDir) ? $gitDir : null;
    }

    private function parseOriginUrl(string $config): ?string
    {
        if (preg_match('/\[remote "origin"\][^\[]*?url\s*=\s*(\S+)/s', $config, $matches) !== 1) {
            return null;
        }

        return trim($matches[1]);
    }
}
