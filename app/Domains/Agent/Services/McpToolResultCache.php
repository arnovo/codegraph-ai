<?php

declare(strict_types=1);

namespace App\Domains\Agent\Services;

use Illuminate\Support\Facades\Cache;
use JsonException;

final class McpToolResultCache
{
    private const RESULT_PREFIX = 'mcp.tools.result';

    private const GEN_PREFIX = 'mcp.tools.gen';

    private const MAX_BYTES = 512_000;

    public function isEnabled(): bool
    {
        return (bool) config('mcp.tools_cache_enabled', true);
    }

    /**
     * @param  array<string, mixed>  $arguments
     * @return array{result: mixed, summary: string}|null
     */
    public function get(string $tool, array $arguments, ?string $project): ?array
    {
        if (! $this->isEnabled()) {
            return null;
        }

        $cached = Cache::get($this->cacheKey($tool, $arguments, $project));

        if (! is_array($cached) || ! array_key_exists('result', $cached) || ! isset($cached['summary'])) {
            return null;
        }

        return [
            'result' => $cached['result'],
            'summary' => (string) $cached['summary'],
        ];
    }

    /**
     * @param  array<string, mixed>  $arguments
     * @param  array{result: mixed, summary: string}  $payload
     */
    public function put(string $tool, array $arguments, ?string $project, array $payload): void
    {
        if (! $this->isEnabled()) {
            return;
        }

        try {
            $encoded = json_encode($payload['result'], JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return;
        }

        if (strlen($encoded) > self::MAX_BYTES) {
            return;
        }

        Cache::put(
            $this->cacheKey($tool, $arguments, $project),
            $payload,
            (int) config('mcp.tools_cache_ttl', 3600),
        );
    }

    public function invalidateProject(?string $projectName): void
    {
        if ($projectName === null || trim($projectName) === '') {
            return;
        }

        $genKey = $this->generationKey($projectName);
        Cache::put($genKey, (int) Cache::get($genKey, 0) + 1, 86400 * 30);
    }

    /**
     * @param  array<string, mixed>  $arguments
     */
    private function cacheKey(string $tool, array $arguments, ?string $project): string
    {
        $projectKey = $this->normalizeProject($project ?? (string) ($arguments['project'] ?? ''));
        $generation = (int) Cache::get($this->generationKey($projectKey), 0);

        try {
            $argsFingerprint = hash('sha256', $tool.'|'.$this->stableJson($arguments));
        } catch (JsonException) {
            $argsFingerprint = hash('sha256', $tool.'|'.serialize($arguments));
        }

        return self::RESULT_PREFIX.".{$projectKey}.{$generation}.{$argsFingerprint}";
    }

    private function generationKey(string $projectName): string
    {
        return self::GEN_PREFIX.'.'.$this->normalizeProject($projectName);
    }

    private function normalizeProject(string $project): string
    {
        $normalized = preg_replace('/[^a-zA-Z0-9._-]/', '_', $project) ?? '';

        return $normalized !== '' ? $normalized : '_';
    }

    /**
     * @param  array<string, mixed>  $data
     *
     * @throws JsonException
     */
    private function stableJson(array $data): string
    {
        $encoded = json_encode($this->sortKeysRecursive($data), JSON_THROW_ON_ERROR);

        return is_string($encoded) ? $encoded : '';
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function sortKeysRecursive(array $data): array
    {
        ksort($data);

        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = $this->sortKeysRecursive($value);
            }
        }

        return $data;
    }
}
