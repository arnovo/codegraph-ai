<?php

declare(strict_types=1);

namespace App\Domains\Projects\Services;

use App\Domains\Mcp\Contracts\McpClientInterface;

final class ProjectStackResolver
{
    public function __construct(
        private readonly McpClientInterface $mcpClient,
    ) {}

    public function resolve(string $projectName, string $rootPath, bool $useMcp = false): string
    {
        $fromFilesystem = $this->detectFromFilesystem($rootPath);
        if ($fromFilesystem !== null) {
            return $fromFilesystem;
        }

        if ($useMcp) {
            $fromMcp = $this->detectFromArchitecture($projectName);
            if ($fromMcp !== null) {
                return $fromMcp;
            }
        }

        return $this->detectFromHeuristics($projectName, $rootPath);
    }

    public function displayName(string $projectName, string $rootPath): string
    {
        $basename = basename(rtrim($rootPath, '/'));

        if ($basename !== '' && $basename !== '.' && $basename !== '/') {
            return $basename;
        }

        return $projectName;
    }

    private function detectFromFilesystem(string $rootPath): ?string
    {
        if (! is_dir($rootPath) || ! is_readable($rootPath)) {
            return null;
        }

        if (is_file($rootPath.'/pubspec.yaml')) {
            return 'Flutter';
        }

        if (is_file($rootPath.'/composer.json')) {
            return $this->stackFromComposer($rootPath.'/composer.json');
        }

        if (is_file($rootPath.'/package.json')) {
            return $this->stackFromPackageJson($rootPath.'/package.json');
        }

        if (is_file($rootPath.'/pyproject.toml') || is_file($rootPath.'/requirements.txt')) {
            return 'Python';
        }

        if (is_file($rootPath.'/go.mod')) {
            return 'Go';
        }

        if (is_file($rootPath.'/Gemfile')) {
            return 'Ruby';
        }

        if (is_file($rootPath.'/Cargo.toml')) {
            return 'Rust';
        }

        return null;
    }

    private function stackFromComposer(string $path): string
    {
        $contents = @file_get_contents($path);
        if (! is_string($contents)) {
            return 'PHP';
        }

        if (str_contains($contents, 'laravel/framework')) {
            return 'Laravel';
        }

        if (str_contains($contents, 'symfony/framework-bundle')) {
            return 'Symfony';
        }

        return 'PHP';
    }

    private function stackFromPackageJson(string $path): string
    {
        $contents = @file_get_contents($path);
        if (! is_string($contents)) {
            return 'Node.js';
        }

        try {
            /** @var array<string, mixed> $json */
            $json = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return 'Node.js';
        }

        $deps = json_encode($json['dependencies'] ?? []).' '.json_encode($json['devDependencies'] ?? []);

        if (str_contains($deps, 'react-native')) {
            return 'React Native';
        }

        if (str_contains($deps, 'next')) {
            return 'Next.js';
        }

        if (str_contains($deps, 'vue')) {
            return 'Vue';
        }

        if (str_contains($deps, 'react')) {
            return 'React';
        }

        return 'Node.js';
    }

    private function detectFromHeuristics(string $projectName, string $rootPath): string
    {
        $haystack = strtolower($projectName.' '.$rootPath);

        if (str_contains($haystack, 'flutter') || str_contains($haystack, 'dart')) {
            return 'Flutter';
        }

        if (str_contains($haystack, 'react-native') || str_contains($haystack, '-rn') || str_contains($haystack, 'workflow-app-rn')) {
            return 'React Native';
        }

        if (str_contains($haystack, 'python') || str_contains($haystack, 'codebase-mcp')) {
            return 'Python';
        }

        if (str_contains($haystack, 'laravel')) {
            return 'Laravel';
        }

        if (str_contains($haystack, '-ws') || str_contains($haystack, 'wsprinex') || str_contains($haystack, '/ws/')) {
            return 'PHP';
        }

        if (str_contains($haystack, 'vue') || str_contains($haystack, 'vite')) {
            return 'Vue';
        }

        if (str_contains($haystack, 'next')) {
            return 'Next.js';
        }

        if (str_contains($haystack, 'node') || str_contains($haystack, 'nestjs')) {
            return 'Node.js';
        }

        return '—';
    }

    private function detectFromArchitecture(string $projectName): ?string
    {
        try {
            $result = $this->mcpClient->callTool('get_architecture', ['project' => $projectName]);
        } catch (\Throwable) {
            return null;
        }

        if (! is_array($result)) {
            return null;
        }

        $languages = $result['languages'] ?? [];
        if (! is_array($languages) || $languages === []) {
            return null;
        }

        usort($languages, fn (array $a, array $b): int => ((int) ($b['file_count'] ?? 0)) <=> ((int) ($a['file_count'] ?? 0)));

        $labels = [];
        foreach (array_slice($languages, 0, 2) as $language) {
            if (! is_array($language)) {
                continue;
            }

            $label = $this->mapLanguageLabel((string) ($language['language'] ?? ''));
            if ($label !== null && ! in_array($label, $labels, true)) {
                $labels[] = $label;
            }
        }

        return $labels !== [] ? implode(' · ', $labels) : null;
    }

    private function mapLanguageLabel(string $language): ?string
    {
        return match (strtolower($language)) {
            'dart' => 'Flutter',
            'php' => 'PHP',
            'javascript' => 'JavaScript',
            'typescript' => 'TypeScript',
            'python' => 'Python',
            'swift' => 'Swift',
            'kotlin' => 'Kotlin',
            'java' => 'Java',
            'go' => 'Go',
            'ruby' => 'Ruby',
            'rust' => 'Rust',
            'c#' => '.NET',
            default => $language !== '' ? $language : null,
        };
    }
}
