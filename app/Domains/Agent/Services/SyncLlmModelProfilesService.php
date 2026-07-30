<?php

declare(strict_types=1);

namespace App\Domains\Agent\Services;

use App\Domains\Agent\DTO\LlmModelProfileData;
use App\Domains\Agent\Models\LlmModelProfile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class SyncLlmModelProfilesService
{
    public function __construct(
        private readonly LlmModelCatalogService $catalog,
    ) {}

    /**
     * @param  list<array{
     *     id?: string|null,
     *     model?: string,
     *     label?: string|null,
     *     enabled?: bool,
     *     use_env_credentials?: bool,
     *     base_url?: string|null,
     *     api_key?: string|null
     * }>  $models
     * @param  list<string>  $removedIds
     * @return list<LlmModelProfileData>
     */
    public function execute(array $models, array $removedIds = []): array
    {
        return DB::transaction(function () use ($models, $removedIds): array {
            $keptIds = [];
            $env = $this->catalog->envSnapshot();

            foreach (array_values($models) as $index => $row) {
                $id = trim((string) ($row['id'] ?? ''));
                $profile = $id !== ''
                    ? LlmModelProfile::query()->find($id)
                    : null;

                $usesEnv = (bool) ($profile?->use_env_credentials ?? ($row['use_env_credentials'] ?? false));

                if ($usesEnv) {
                    if ($profile === null) {
                        $profile = LlmModelProfile::query()->where('use_env_credentials', true)->first()
                            ?? new LlmModelProfile(['id' => (string) Str::uuid(), 'use_env_credentials' => true]);
                    }

                    $profile->fill([
                        'model' => $env['model'] !== '' ? $env['model'] : (string) ($row['model'] ?? ''),
                        'label' => isset($row['label']) && $row['label'] !== '' ? (string) $row['label'] : 'Principal (.env)',
                        'sort_order' => $index,
                        'enabled' => (bool) ($row['enabled'] ?? true),
                        'use_env_credentials' => true,
                        'base_url' => null,
                        'api_key' => null,
                    ]);
                    $profile->save();
                    $keptIds[] = $profile->id;

                    continue;
                }

                $model = trim((string) ($row['model'] ?? ''));
                $baseUrl = trim((string) ($row['base_url'] ?? ''));

                if ($model === '' || $baseUrl === '') {
                    if ($profile !== null && ! $profile->use_env_credentials) {
                        $profile->sort_order = $index;
                        $profile->enabled = (bool) ($row['enabled'] ?? $profile->enabled);
                        if (isset($row['label']) && $row['label'] !== '') {
                            $profile->label = (string) $row['label'];
                        }
                        $profile->save();
                        $keptIds[] = $profile->id;
                    }

                    continue;
                }

                if ($profile === null || $profile->use_env_credentials) {
                    $profile = new LlmModelProfile(['id' => (string) Str::uuid()]);
                }

                $profile->fill([
                    'model' => $model,
                    'label' => isset($row['label']) && $row['label'] !== '' ? (string) $row['label'] : null,
                    'sort_order' => $index,
                    'enabled' => (bool) ($row['enabled'] ?? true),
                    'use_env_credentials' => false,
                    'base_url' => $baseUrl,
                ]);

                $apiKey = trim((string) ($row['api_key'] ?? ''));
                if ($apiKey !== '') {
                    $profile->api_key = $apiKey;
                }

                $profile->save();
                $keptIds[] = $profile->id;
            }

            $this->catalog->ensureEnvProfile();

            $envProfileId = LlmModelProfile::query()->where('use_env_credentials', true)->value('id');
            if ($envProfileId !== null) {
                $keptIds[] = $envProfileId;
            }

            $keptIds = array_values(array_unique($keptIds));

            if ($removedIds !== []) {
                LlmModelProfile::query()
                    ->whereIn('id', $removedIds)
                    ->where('use_env_credentials', false)
                    ->delete();
            }

            return $this->catalog->listAll();
        });
    }
}
