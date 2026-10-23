<?php

namespace App\Http\Requests\Api\V1\Forum;

use Domain\Forum\Models\ForumCategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateForumCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        /** @var ForumCategory $forumCategory */
        $forumCategory = $this->route('forumCategory');

        return [
            'slug' => [
                'required',
                'string',
                'max:120',
                'alpha_dash',
                Rule::unique('forum_categories', 'slug')->ignore($forumCategory->id),
            ],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'translations' => ['required', 'array', 'min:1'],
            'translations.*.locale' => ['required', 'string', 'max:10', 'distinct'],
            'translations.*.name' => ['required', 'string', 'max:100'],
            'translations.*.description' => ['nullable', 'string', 'max:500'],
        ];
    }
}
