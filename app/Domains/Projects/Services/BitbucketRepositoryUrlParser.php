<?php

declare(strict_types=1);

namespace App\Domains\Projects\Services;

use InvalidArgumentException;

final class BitbucketRepositoryUrlParser
{
    /**
     * @return non-empty-string
     */
    public function normalizeHttpsUrl(string $url): string
    {
        $trimmed = trim($url);

        if (str_starts_with($trimmed, 'git@')) {
            throw new InvalidArgumentException(
                'Usa una URL HTTPS de Bitbucket con usuario y token de aplicación.',
            );
        }

        if (! str_starts_with($trimmed, 'http://') && ! str_starts_with($trimmed, 'https://')) {
            $trimmed = 'https://'.$trimmed;
        }

        $parts = parse_url($trimmed);
        $host = strtolower((string) ($parts['host'] ?? ''));

        if ($host === '' || ! in_array($host, config('projects.git.allowed_hosts', []), true)) {
            throw new InvalidArgumentException(
                'Solo se permiten repositorios de bitbucket.org.',
            );
        }

        $path = trim((string) ($parts['path'] ?? ''), '/');

        if (! preg_match('#^[^/]+/[^/]+$#', $path)) {
            throw new InvalidArgumentException(
                'La URL debe tener el formato https://bitbucket.org/workspace/repositorio',
            );
        }

        $path = preg_replace('/\.git$/', '', $path) ?? $path;

        return 'https://'.$host.'/'.$path.'.git';
    }

    /**
     * @return non-empty-string
     */
    public function directoryName(string $normalizedGitUrl): string
    {
        $path = (string) parse_url($normalizedGitUrl, PHP_URL_PATH);
        $name = basename(rtrim($path, '/'));

        if ($name === '' || $name === '.') {
            throw new InvalidArgumentException('No se pudo determinar el nombre del repositorio.');
        }

        return preg_replace('/\.git$/', '', $name) ?? $name;
    }

    /**
     * @return non-empty-string
     */
    public function buildAuthenticatedCloneUrl(
        string $normalizedGitUrl,
        string $username,
        string $apiToken,
    ): string {
        $encodedUser = rawurlencode($username);
        $encodedToken = rawurlencode($apiToken);

        $authenticated = preg_replace(
            '#^https://#',
            'https://'.$encodedUser.':'.$encodedToken.'@',
            $normalizedGitUrl,
            1,
        );

        if (! is_string($authenticated) || $authenticated === '') {
            throw new InvalidArgumentException('No se pudo construir la URL de clonado.');
        }

        return $authenticated;
    }
}
