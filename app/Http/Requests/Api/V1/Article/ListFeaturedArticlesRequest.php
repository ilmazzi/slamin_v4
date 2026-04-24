<?php

namespace App\Http\Requests\Api\V1\Article;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListFeaturedArticlesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
            'article_category_id' => [
                'nullable',
                'integer',
                Rule::exists('article_categories', 'id')->where('is_active', true),
            ],
        ];
    }
}
