<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class SyncLlmModelsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'models' => ['required', 'array'],
            'models.*.id' => ['nullable', 'uuid'],
            'models.*.model' => ['nullable', 'string', 'max:160'],
            'models.*.label' => ['nullable', 'string', 'max:160'],
            'models.*.enabled' => ['sometimes', 'boolean'],
            'models.*.use_env_credentials' => ['sometimes', 'boolean'],
            'models.*.base_url' => ['nullable', 'string', 'max:500'],
            'models.*.api_key' => ['nullable', 'string', 'max:500'],
            'removed_ids' => ['sometimes', 'array'],
            'removed_ids.*' => ['uuid'],
        ];
    }
}
