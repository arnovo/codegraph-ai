<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class CloneRepositoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'repository_url' => ['required', 'string', 'max:2048'],
            'username' => ['required', 'string', 'max:255'],
            'api_token' => ['required', 'string', 'max:512'],
        ];
    }
}
