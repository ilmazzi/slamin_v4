<?php

namespace App\Http\Requests\Api\V1\Article;

use Domain\Article\Models\ArticleCategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateArticleCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        /** @var ArticleCategory $articleCategory */
        $articleCategory = $this->route('articleCategory');

        return [
            'slug' => [
                'required',
                'string',
                'max:120',
                'alpha_dash',
                Rule::unique('article_categories', 'slug')->ignore($articleCategory->id),
            ],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'translations' => ['required', 'array', 'min:1'],
            'translations.*.locale' => ['required', 'string', 'max:10', 'distinct'],
            'translations.*.name' => ['required', 'string', 'max:100'],
        ];
    }
}
