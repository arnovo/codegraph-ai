<?php

declare(strict_types=1);

namespace App\Domains\Agent\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

final class LlmModelProfile extends Model
{
    use HasUuids;

    protected $fillable = [
        'model',
        'label',
        'sort_order',
        'enabled',
        'use_env_credentials',
        'base_url',
        'api_key',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'enabled' => 'boolean',
            'use_env_credentials' => 'boolean',
            'api_key' => 'encrypted',
        ];
    }
}
