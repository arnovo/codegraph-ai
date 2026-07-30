<?php

declare(strict_types=1);

namespace App\Domains\Agent\Services;

use App\Domains\Agent\DTO\LlmModelProfileData;
use App\Domains\Agent\Models\LlmModelProfile;
use App\Support\LlmErrorFormatter;
use Illuminate\Support\Str;

final class LlmModelCatalogService
{
    /** @return list<LlmModelProfileData> */
    public function listAll(): array
    {
        $this->ensureEnvProfile();

        return LlmModelProfile::query()
            ->orderBy('sort_order')
            ->orderBy('created_at')
            ->get()
            ->map(fn (LlmModelProfile $profile) => $this->toData($profile))
            ->all();
    }

    /** @return list<LlmModelProfileData> */
    public function orderedEnabled(): array
    {
        return array_values(array_filter(
            $this->listAll(),
            fn (LlmModelProfileData $profile) => $profile->enabled,
        ));
    }

    /**
     * @param  array<string, mixed>  $defaults
     * @return list<array<string, mixed>>
     */
    public function orderedEnabledClientConfigs(array $defaults): array
    {
        $this->ensureEnvProfile();

        $profiles = LlmModelProfile::query()
            ->where('enabled', true)
            ->orderBy('sort_order')
            ->orderBy('created_at')
            ->get();

        if ($profiles->isEmpty()) {
            return [$defaults];
        }

        return $profiles
            ->map(fn (LlmModelProfile $profile) => $this->clientConfig($profile, $defaults))
            ->all();
    }

    /**
     * @param  array<string, mixed>  $defaults
     * @return array<string, mixed>
     */
    public function clientConfig(LlmModelProfile $profile, array $defaults): array
    {
        if ($profile->use_env_credentials) {
            return $defaults;
        }

        return [
            ...$defaults,
            'model' => $profile->model,
            'base_url' => filled($profile->base_url) ? $profile->base_url : ($defaults['base_url'] ?? ''),
            'api_key' => filled($profile->api_key) ? $profile->api_key : ($defaults['api_key'] ?? ''),
        ];
    }

    /** @return array<string, mixed> */
    public function envSnapshot(): array
    {
        return [
            'model' => (string) config('llm.model', ''),
            'base_url' => (string) config('llm.base_url', ''),
            'api_key_preview' => LlmErrorFormatter::truncateSecret((string) config('llm.api_key', '')),
            'driver' => (string) config('llm.driver', 'openai'),
        ];
    }

    public function ensureEnvProfile(): void
    {
        $envProfile = LlmModelProfile::query()->where('use_env_credentials', true)->first();
        $env = $this->envSnapshot();

        if ($envProfile === null) {
            if (trim($env['model']) === '') {
                return;
            }

            LlmModelProfile::query()->create([
                'id' => (string) Str::uuid(),
                'model' => $env['model'],
                'label' => 'Principal (.env)',
                'sort_order' => 0,
                'enabled' => true,
                'use_env_credentials' => true,
            ]);

            return;
        }

        $envProfile->update([
            'model' => $env['model'] !== '' ? $env['model'] : $envProfile->model,
        ]);
    }

    public function labelForModel(string $model): ?string
    {
        $model = trim($model);
        if ($model === '') {
            return null;
        }

        foreach ($this->orderedEnabled() as $profile) {
            if ($profile->model === $model) {
                return filled($profile->label) ? $profile->label : null;
            }
        }

        return null;
    }

    private function toData(LlmModelProfile $profile): LlmModelProfileData
    {
        if ($profile->use_env_credentials) {
            $env = $this->envSnapshot();

            return new LlmModelProfileData(
                id: $profile->id,
                model: $env['model'] !== '' ? $env['model'] : $profile->model,
                label: $profile->label ?? 'Principal (.env)',
                sortOrder: $profile->sort_order,
                enabled: $profile->enabled,
                useEnvCredentials: true,
                baseUrl: $env['base_url'] !== '' ? $env['base_url'] : null,
                apiKeyPreview: $env['api_key_preview'],
                apiKeySet: $env['api_key_preview'] !== '(vacía)',
            );
        }

        return new LlmModelProfileData(
            id: $profile->id,
            model: $profile->model,
            label: $profile->label,
            sortOrder: $profile->sort_order,
            enabled: $profile->enabled,
            useEnvCredentials: false,
            baseUrl: $profile->base_url,
            apiKeyPreview: filled($profile->api_key)
                ? LlmErrorFormatter::truncateSecret((string) $profile->api_key)
                : null,
            apiKeySet: filled($profile->api_key),
        );
    }
}
