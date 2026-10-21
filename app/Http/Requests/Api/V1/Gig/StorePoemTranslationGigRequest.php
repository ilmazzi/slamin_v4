<?php

namespace App\Http\Requests\Api\V1\Gig;

use Domain\Gig\Enums\GigStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePoemTranslationGigRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'target_language' => ['required', 'string', 'max:255'],
            'proposed_price' => ['required', 'numeric', 'min:0'],
            'currency' => ['nullable', 'string', 'size:3'],
            'deadline' => ['nullable', 'date'],
            'special_requirements' => ['nullable', 'string'],
            'status' => ['nullable', 'string', Rule::in([
                GigStatus::Draft->value,
                GigStatus::Open->value,
            ])],
        ];
    }
}
