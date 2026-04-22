<?php

namespace App\Http\Requests\Api\V1\Media;

use Domain\Media\Enums\MediaVisibility;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreVideoMediaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'visibility' => ['required', Rule::in(array_column(MediaVisibility::cases(), 'value'))],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['string', 'min:2', 'max:30'],
            'file' => ['required', 'file'],
        ];
    }
}
