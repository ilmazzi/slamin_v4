<?php

namespace App\Http\Requests\Api\V1\Help;

use Domain\I18n\Actions\ResolveSupportedLocalesAction;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreHelpArticleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'help_category_id' => ['required', 'integer', Rule::exists('help_categories', 'id')],
            'slug' => ['required', 'string', 'max:160', 'alpha_dash', Rule::unique('help_articles', 'slug')],
            'is_published' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'translations' => ['required', 'array', 'min:1'],
            'translations.*.locale' => [
                'required',
                'string',
                'max:10',
                'distinct',
                Rule::in(app(ResolveSupportedLocalesAction::class)->execute(false)),
            ],
            'translations.*.title' => ['required', 'string', 'max:180'],
            'translations.*.body' => ['required', 'string', 'max:20000'],
        ];
    }
}
