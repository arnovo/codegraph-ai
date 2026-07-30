<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('llm_model_profiles', function (Blueprint $table) {
            $table->boolean('use_env_credentials')->default(false)->after('enabled');
            $table->string('base_url')->nullable()->after('use_env_credentials');
            $table->text('api_key')->nullable()->after('base_url');
        });

        $firstId = DB::table('llm_model_profiles')->orderBy('sort_order')->orderBy('created_at')->value('id');
        if ($firstId !== null) {
            DB::table('llm_model_profiles')->where('id', $firstId)->update(['use_env_credentials' => true]);
        }
    }

    public function down(): void
    {
        Schema::table('llm_model_profiles', function (Blueprint $table) {
            $table->dropColumn(['use_env_credentials', 'base_url', 'api_key']);
        });
    }
};
