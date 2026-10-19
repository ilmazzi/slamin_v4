<?php

namespace App\Http\Requests\Api\V1\Admin;

use Illuminate\Foundation\Http\FormRequest;

class SyncFeaturedArticlesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'article_ids' => ['present', 'array', 'max:20'],
            'article_ids.*' => ['integer', 'distinct', 'exists:articles,id'],
        ];
    }
}
