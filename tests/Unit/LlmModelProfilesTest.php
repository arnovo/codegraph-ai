<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Domains\Agent\Models\LlmModelProfile;
use App\Domains\Agent\Services\LlmModelCatalogService;
use App\Domains\Agent\Services\SyncLlmModelProfilesService;
use App\Support\LlmRetryPolicy;
use Illuminate\Support\Facades\Cache;
use RuntimeException;
use Tests\TestCase;

final class LlmModelProfilesTest extends TestCase
{
    public function test_sync_persists_drag_order(): void
    {
        Cache::flush();

        $sync = app(SyncLlmModelProfilesService::class);

        $saved = $sync->execute([
            [
                'model' => config('llm.model', 'model-a'),
                'label' => 'A',
                'enabled' => true,
                'use_env_credentials' => true,
            ],
            [
                'model' => 'model-b',
                'label' => 'B',
                'enabled' => true,
                'base_url' => 'https://api.example.com/v1',
                'api_key' => 'secret-b',
            ],
        ]);

        $models = array_column(array_map(fn ($p) => $p->toArray(), $saved), 'model');
        $this->assertSame(config('llm.model'), $models[0]);
        $this->assertSame('model-b', $models[1]);

        $envId = collect($saved)->first(fn ($p) => $p->useEnvCredentials)?->id;
        $customId = collect($saved)->first(fn ($p) => ! $p->useEnvCredentials)?->id;

        $reordered = $sync->execute([
            [
                'id' => $customId,
                'model' => 'model-b',
                'label' => 'B',
                'enabled' => true,
                'base_url' => 'https://api.example.com/v1',
            ],
            [
                'id' => $envId,
                'model' => config('llm.model'),
                'label' => 'A',
                'enabled' => true,
                'use_env_credentials' => true,
            ],
        ]);

        $reorderedModels = array_column(array_map(fn ($p) => $p->toArray(), $reordered), 'model');
        $this->assertSame('model-b', $reorderedModels[0]);
        $this->assertSame(config('llm.model'), $reorderedModels[1]);
    }

    public function test_catalog_builds_custom_client_config(): void
    {
        LlmModelProfile::query()->create([
            'id' => '00000000-0000-4000-8000-000000000001',
            'model' => 'gpt-test',
            'base_url' => 'https://custom.example/v1',
            'api_key' => 'key-123',
            'sort_order' => 0,
            'enabled' => true,
            'use_env_credentials' => false,
        ]);

        $catalog = app(LlmModelCatalogService::class);
        $configs = $catalog->orderedEnabledClientConfigs([
            'driver' => 'custom',
            'model' => 'env-model',
            'base_url' => 'https://env.example/v1',
            'api_key' => 'env-key',
        ]);

        $custom = collect($configs)->first(fn (array $config) => ($config['model'] ?? '') === 'gpt-test');
        $this->assertNotNull($custom);
        $this->assertSame('https://custom.example/v1', $custom['base_url']);
        $this->assertSame('key-123', $custom['api_key']);
    }

    public function test_retry_policy_detects_quota_errors(): void
    {
        $error = new RuntimeException('You exceeded your current quota, please check your plan');

        $this->assertTrue(LlmRetryPolicy::isRetryable($error));
    }

    public function test_sync_preserves_custom_profile_when_api_key_omitted(): void
    {
        $sync = app(SyncLlmModelProfilesService::class);

        $saved = $sync->execute([
            [
                'model' => config('llm.model', 'model-a'),
                'label' => 'Env',
                'enabled' => true,
                'use_env_credentials' => true,
            ],
            [
                'model' => 'model-b',
                'label' => 'Backup',
                'enabled' => true,
                'base_url' => 'https://api.example.com/v1',
                'api_key' => 'secret-b',
            ],
        ]);

        $envId = collect($saved)->first(fn ($p) => $p->useEnvCredentials)?->id;
        $customId = collect($saved)->first(fn ($p) => ! $p->useEnvCredentials)?->id;

        $resynced = $sync->execute([
            [
                'id' => $envId,
                'model' => config('llm.model'),
                'enabled' => true,
                'use_env_credentials' => true,
            ],
            [
                'id' => $customId,
                'model' => 'model-b',
                'label' => 'Backup',
                'enabled' => true,
                'base_url' => 'https://api.example.com/v1',
            ],
        ]);

        $this->assertCount(2, $resynced);
        $custom = LlmModelProfile::query()->find($customId);
        $this->assertNotNull($custom);
        $this->assertSame('secret-b', $custom->api_key);
    }

    public function test_sync_deletes_only_explicit_removed_ids(): void
    {
        $sync = app(SyncLlmModelProfilesService::class);

        $saved = $sync->execute([
            [
                'model' => config('llm.model'),
                'enabled' => true,
                'use_env_credentials' => true,
            ],
            [
                'model' => 'keep-me',
                'enabled' => true,
                'base_url' => 'https://keep.example/v1',
                'api_key' => 'keep-key',
            ],
            [
                'model' => 'drop-me',
                'enabled' => true,
                'base_url' => 'https://drop.example/v1',
                'api_key' => 'drop-key',
            ],
        ]);

        $keepId = collect($saved)->first(fn ($p) => $p->model === 'keep-me')?->id;
        $dropId = collect($saved)->first(fn ($p) => $p->model === 'drop-me')?->id;
        $envId = collect($saved)->first(fn ($p) => $p->useEnvCredentials)?->id;

        $this->assertNotNull($keepId);
        $this->assertNotNull($dropId);

        // Payload only includes env row — orphan custom profiles must survive.
        $partial = $sync->execute([
            [
                'id' => $envId,
                'model' => config('llm.model'),
                'enabled' => true,
                'use_env_credentials' => true,
            ],
        ]);

        $this->assertCount(3, $partial);
        $this->assertNotNull(LlmModelProfile::query()->find($keepId));
        $this->assertNotNull(LlmModelProfile::query()->find($dropId));

        $afterRemove = $sync->execute([
            [
                'id' => $envId,
                'model' => config('llm.model'),
                'enabled' => true,
                'use_env_credentials' => true,
            ],
            [
                'id' => $keepId,
                'model' => 'keep-me',
                'enabled' => true,
                'base_url' => 'https://keep.example/v1',
            ],
        ], [$dropId]);

        $this->assertCount(2, $afterRemove);
        $this->assertNotNull(LlmModelProfile::query()->find($keepId));
        $this->assertNull(LlmModelProfile::query()->find($dropId));
    }
}
