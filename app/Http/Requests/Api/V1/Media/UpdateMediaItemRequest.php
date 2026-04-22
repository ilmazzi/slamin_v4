<?php

namespace App\Http\Requests\Api\V1\Media;

use Domain\Media\Enums\MediaVisibility;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMediaItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string'],
            'visibility' => ['sometimes', Rule::in(array_column(MediaVisibility::cases(), 'value'))],
        ];
    }
}
