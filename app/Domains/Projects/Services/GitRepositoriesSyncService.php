<?php

declare(strict_types=1);

namespace App\Domains\Projects\Services;

use App\Domains\Projects\Exceptions\RepositoryCloneException;
use Illuminate\Support\Facades\Process;
use InvalidArgumentException;
use RuntimeException;

final class GitRepositoriesSyncService
{
    public function __construct(
        private readonly BitbucketRepositoryUrlParser $urlParser,
        private readonly string $reposBasePath,
        private readonly ?string $username,
        private readonly ?string $token,
        private readonly int $cloneTimeoutSeconds,
    ) {}

    /**
     * @param  list<string>  $repositoryUrls
     * @return list<array{url: string, path: string, action: string}>
     */
    public function sync(array $repositoryUrls): array
    {
        $results = [];

        foreach ($repositoryUrls as $repositoryUrl) {
            $trimmed = trim($repositoryUrl);
            if ($trimmed === '') {
                continue;
            }

            $normalized = $this->normalizeUrl($trimmed);
            $directoryName = $this->urlParser->directoryName($normalized);
            $target = rtrim(str_replace('\\', '/', $this->reposBasePath), '/').'/'.$directoryName;

            if (is_dir($target.'/.git')) {
                $this->pullRepository($target);
                $results[] = ['url' => $normalized, 'path' => $target, 'action' => 'pull'];

                continue;
            }

            $cloneUrl = $this->buildAuthenticatedUrl($normalized);
            $this->cloneRepository($cloneUrl, $target);
            $results[] = ['url' => $normalized, 'path' => $target, 'action' => 'clone'];
        }

        return $results;
    }

    private function normalizeUrl(string $url): string
    {
        if (str_contains($url, 'bitbucket.org')) {
            return $this->urlParser->normalizeHttpsUrl($url);
        }

        if (str_starts_with($url, 'git@')) {
            throw new InvalidArgumentException('Usa URLs HTTPS para sincronización automática.');
        }

        if (! str_starts_with($url, 'http://') && ! str_starts_with($url, 'https://')) {
            $url = 'https://'.$url;
        }

        return $url;
    }

    private function buildAuthenticatedUrl(string $normalizedUrl): string
    {
        $username = trim((string) $this->username);
        $token = trim((string) $this->token);

        if ($username === '' || $token === '') {
            return $normalizedUrl;
        }

        if (str_contains($normalizedUrl, 'bitbucket.org')) {
            return $this->urlParser->buildAuthenticatedCloneUrl($normalizedUrl, $username, $token);
        }

        $encodedUser = rawurlencode($username);
        $encodedToken = rawurlencode($token);
        $authenticated = preg_replace(
            '#^https://#',
            'https://'.$encodedUser.':'.$encodedToken.'@',
            $normalizedUrl,
            1,
        );

        if (! is_string($authenticated) || $authenticated === '') {
            throw new InvalidArgumentException('No se pudo construir la URL autenticada.');
        }

        return $authenticated;
    }

    private function cloneRepository(string $cloneUrl, string $target): void
    {
        $parent = dirname($target);
        if (! is_dir($parent) && ! mkdir($parent, 0755, true) && ! is_dir($parent)) {
            throw new RepositoryCloneException('No se pudo crear el directorio de repos.');
        }

        $result = Process::timeout($this->cloneTimeoutSeconds)->run([
            'git',
            'clone',
            '--depth',
            '1',
            $cloneUrl,
            $target,
        ]);

        if (! $result->successful()) {
            throw new RepositoryCloneException(
                trim($result->errorOutput() ?: $result->output()) ?: 'git clone failed',
            );
        }
    }

    private function pullRepository(string $target): void
    {
        $identity = config('projects.git.identity', []);
        $env = [];

        if (is_array($identity)) {
            $name = trim((string) ($identity['name'] ?? ''));
            $email = trim((string) ($identity['email'] ?? ''));

            if ($name !== '') {
                $env['GIT_AUTHOR_NAME'] = $name;
                $env['GIT_COMMITTER_NAME'] = $name;
            }

            if ($email !== '') {
                $env['GIT_AUTHOR_EMAIL'] = $email;
                $env['GIT_COMMITTER_EMAIL'] = $email;
            }
        }

        $result = Process::timeout($this->cloneTimeoutSeconds)
            ->path($target)
            ->env($env)
            ->run(['git', 'pull', '--ff-only']);

        if (! $result->successful()) {
            throw new RuntimeException(
                trim($result->errorOutput() ?: $result->output()) ?: 'git pull failed',
            );
        }
    }
}
