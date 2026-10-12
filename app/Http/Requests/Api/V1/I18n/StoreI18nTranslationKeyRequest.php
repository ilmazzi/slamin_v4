<?php

namespace App\Http\Requests\Api\V1\I18n;

use Illuminate\Foundation\Http\FormRequest;

class StoreI18nTranslationKeyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('admin') === true;
    }

    public function rules(): array
    {
        return [
            'key' => ['required', 'string', 'max:191', 'regex:/^[a-z0-9_]+(\.[a-z0-9_]+)+$/', 'unique:i18n_translation_keys,key'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'translations' => ['sometimes', 'array'],
            'translations.*' => ['string'],
        ];
    }
}
