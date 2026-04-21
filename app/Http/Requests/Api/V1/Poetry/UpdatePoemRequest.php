<?php

namespace App\Http\Requests\Api\V1\Poetry;

use Domain\Poetry\Enums\PoemStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePoemRequest extends FormRequest
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
            'description' => ['nullable', 'string'],
            'image' => ['nullable', 'image', 'max:5120'],
            'remove_image' => ['nullable', 'boolean'],
            'language' => ['nullable', 'string', 'max:10'],
            'status' => ['nullable', Rule::in([PoemStatus::Draft->value, PoemStatus::Published->value])],
            'is_public' => ['nullable', 'boolean'],
            'published_at' => ['nullable', 'date'],

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
