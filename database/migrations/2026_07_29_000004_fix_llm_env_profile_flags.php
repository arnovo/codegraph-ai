<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        // Migration 000003 could mark a custom profile as .env if it was first in sort order.
        DB::table('llm_model_profiles')
            ->where('use_env_credentials', true)
            ->where(function ($query): void {
                $query->whereNotNull('base_url')->where('base_url', '!=', '');
            })
            ->update(['use_env_credentials' => false]);

        DB::table('llm_model_profiles')
            ->where('use_env_credentials', true)
            ->whereNotNull('api_key')
            ->where('api_key', '!=', '')
            ->update(['use_env_credentials' => false]);

        $envExists = DB::table('llm_model_profiles')->where('use_env_credentials', true)->exists();
        if ($envExists) {
            return;
        }

        $model = (string) config('llm.model', '');
        if ($model === '') {
            return;
        }

        DB::table('llm_model_profiles')->insert([
            'id' => (string) Str::uuid(),
            'model' => $model,
            'label' => 'Principal (.env)',
            'sort_order' => 0,
            'enabled' => true,
            'use_env_credentials' => true,
            'base_url' => null,
            'api_key' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        // Non-destructive data repair; no rollback.
    }
};
