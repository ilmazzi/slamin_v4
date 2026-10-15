<?php

namespace App\Http\Requests\Api\V1\ContentTranslation;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpsertContentTranslationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string'],
            'summary' => ['nullable', 'string', 'max:5000'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $translatable = $this->route('poem') ?? $this->route('article');
                $original = $translatable instanceof Model
                    ? strtolower((string) $translatable->getAttribute('language'))
                    : '';

                if ($original !== '' && $original === $this->route('locale')) {
                    $validator->errors()->add('locale', __('content_translations.same_as_original'));
                }
            },
        ];
    }
}
