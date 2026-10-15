<?php

namespace App\Http\Requests\Api\V1\Gig;

use Domain\Gig\Enums\GigType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListOpenGigsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:100'],
            'type' => ['nullable', Rule::enum(GigType::class)],
            'sort' => ['nullable', Rule::in(['newest', 'oldest', 'deadline'])],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
