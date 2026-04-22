<?php

namespace App\Http\Requests\Api\V1\Gig;

use Domain\Gig\Enums\GigStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePoemTranslationGigRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'target_language' => ['sometimes', 'string', 'max:255'],
            'proposed_price' => ['sometimes', 'numeric', 'min:0'],
            'currency' => ['sometimes', 'string', 'size:3'],
            'deadline' => ['sometimes', 'nullable', 'date'],
            'special_requirements' => ['sometimes', 'nullable', 'string'],
            'status' => ['sometimes', 'string', Rule::in([
                GigStatus::Draft->value,
                GigStatus::Open->value,
                GigStatus::Filled->value,
                GigStatus::Closed->value,
                GigStatus::Cancelled->value,
            ])],
        ];
    }
}
