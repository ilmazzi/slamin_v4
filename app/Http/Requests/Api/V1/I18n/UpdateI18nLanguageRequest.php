<?php

namespace App\Http\Requests\Api\V1\I18n;

use Illuminate\Foundation\Http\FormRequest;

class UpdateI18nLanguageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('admin') === true;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:100'],
            'flag' => ['sometimes', 'nullable', 'string', 'max:16'],
            'is_active' => ['sometimes', 'boolean'],
            'is_default' => ['sometimes', 'boolean'],
            'sort_order' => ['sometimes', 'integer', 'min:0'],
        ];
    }
}
