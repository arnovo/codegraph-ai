<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class SendChatMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'message' => ['required', 'string', 'max:10000'],
            'active_project_name' => ['nullable', 'string', 'max:255'],
            'agent_profile' => ['nullable', 'string', 'max:64'],
        ];
    }
}
