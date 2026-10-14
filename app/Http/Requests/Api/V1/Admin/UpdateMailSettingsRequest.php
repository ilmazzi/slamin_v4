<?php

namespace App\Http\Requests\Api\V1\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMailSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() === true;
    }

    public function rules(): array
    {
        return [
            'enabled' => ['required', 'boolean'],
            'host' => ['required_if:enabled,true', 'nullable', 'string', 'max:255'],
            'port' => ['required_if:enabled,true', 'integer', 'min:1', 'max:65535'],
            'scheme' => ['nullable', Rule::in(['smtp', 'smtps'])],
            'username' => ['nullable', 'string', 'max:255'],
            'password' => ['nullable', 'string', 'max:1024'],
            'from_address' => ['required_if:enabled,true', 'nullable', 'email', 'max:255'],
            'from_name' => ['nullable', 'string', 'max:255'],
        ];
    }
}
