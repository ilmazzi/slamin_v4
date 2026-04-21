<?php

namespace App\Http\Requests\Api\V1\Poetry;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListPoemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
            'poem_category_id' => [
                'nullable',
                'integer',
                Rule::exists('poem_categories', 'id')->where('is_active', true),
            ],
            'poem_type_id' => [
                'nullable',
                'integer',
                Rule::exists('poem_types', 'id')->where('is_active', true),
            ],
        ];
    }
}
