<?php

namespace App\Http\Requests\Api\V1\I18n;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreI18nLanguageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('admin') === true;
    }

    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:10', 'regex:/^[a-z]{2,3}(-[a-z0-9]{2,8})?$/', Rule::unique('i18n_languages', 'code')],
            'name' => ['required', 'string', 'max:100'],
            'flag' => ['nullable', 'string', 'max:16'],
            'is_active' => ['sometimes', 'boolean'],
            'is_default' => ['sometimes', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('code')) {
            $this->merge([
                'code' => strtolower(trim((string) $this->input('code'))),
            ]);
        }
    }
}
