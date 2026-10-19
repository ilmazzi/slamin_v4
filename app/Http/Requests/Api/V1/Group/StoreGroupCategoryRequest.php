<?php

namespace App\Http\Requests\Api\V1\Group;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreGroupCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'slug' => ['required', 'string', 'max:120', 'alpha_dash', Rule::unique('group_categories', 'slug')],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'translations' => ['required', 'array', 'min:1'],
            'translations.*.locale' => ['required', 'string', 'max:10', 'distinct'],
            'translations.*.name' => ['required', 'string', 'max:100'],
        ];
    }
}
