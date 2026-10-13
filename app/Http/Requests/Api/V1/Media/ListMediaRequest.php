<?php

namespace App\Http\Requests\Api\V1\Media;

use Domain\Media\Enums\MediaType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListMediaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'q' => ['nullable', 'string', 'max:191'],
            'type' => ['nullable', 'string', Rule::enum(MediaType::class)],
            'sort' => ['nullable', 'string', Rule::in(['newest', 'oldest', 'popular'])],
        ];
    }
}
