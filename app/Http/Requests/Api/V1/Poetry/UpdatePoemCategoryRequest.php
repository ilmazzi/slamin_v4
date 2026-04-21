<?php

namespace App\Http\Requests\Api\V1\Poetry;

use Domain\Poetry\Models\PoemCategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePoemCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        /** @var PoemCategory $poemCategory */
        $poemCategory = $this->route('poemCategory');

        return [
            'slug' => [
                'required',
                'string',
                'max:120',
                'alpha_dash',
                Rule::unique('poem_categories', 'slug')->ignore($poemCategory->id),
            ],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'translations' => ['required', 'array', 'min:1'],
            'translations.*.locale' => ['required', 'string', 'max:10', 'distinct'],
            'translations.*.name' => ['required', 'string', 'max:100'],
        ];
    }
}
