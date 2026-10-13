<?php

namespace App\Http\Requests\Api\V1\I18n;

use Illuminate\Foundation\Http\FormRequest;

class UpsertI18nTranslationRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user?->hasRole('admin') === true || $user?->hasRole('translator') === true;
    }

    public function rules(): array
    {
        return [
            'key' => ['required', 'string', 'max:191', 'exists:i18n_translation_keys,key'],
            'locale' => ['required', 'string', 'max:10', 'exists:i18n_languages,code'],
            'value' => ['required', 'string'],
        ];
    }
}
