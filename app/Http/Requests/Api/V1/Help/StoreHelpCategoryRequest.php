<?php

namespace App\Http\Requests\Api\V1\Help;

use Domain\I18n\Actions\ResolveSupportedLocalesAction;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreHelpCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'slug' => ['required', 'string', 'max:120', 'alpha_dash', Rule::unique('help_categories', 'slug')],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'translations' => ['required', 'array', 'min:1'],
            'translations.*.locale' => [
                'required',
                'string',
                'max:10',
                'distinct',
                Rule::in(app(ResolveSupportedLocalesAction::class)->execute(false)),
            ],
            'translations.*.name' => ['required', 'string', 'max:100'],
        ];
    }
}
