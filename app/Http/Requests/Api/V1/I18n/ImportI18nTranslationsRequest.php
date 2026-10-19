<?php

namespace App\Http\Requests\Api\V1\I18n;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class ImportI18nTranslationsRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user?->hasRole('admin') === true || $user?->hasRole('translator') === true;
    }

    public function rules(): array
    {
        return [
            'locale' => ['required', 'string', 'max:10', 'exists:i18n_languages,code'],
            'overwrite' => ['sometimes', 'boolean'],
            'create_keys' => ['sometimes', 'boolean'],
            'translations' => ['sometimes', 'array'],
            'file' => ['sometimes', 'file', 'max:10240'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if (! $this->hasFile('file') && ! is_array($this->input('translations'))) {
                $validator->errors()->add('translations', (string) __('i18n.panel.import_invalid'));
            }
        });
    }
}
