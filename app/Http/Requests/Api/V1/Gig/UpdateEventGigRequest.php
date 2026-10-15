<?php

namespace App\Http\Requests\Api\V1\Gig;

use Domain\Gig\Enums\GigStatus;
use Domain\Gig\Enums\GigType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEventGigRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'type' => ['sometimes', 'string', Rule::in([
                GigType::PoetArtist->value,
                GigType::McHost->value,
                GigType::TechnicalSupport->value,
                GigType::Volunteer->value,
            ])],
            'quantity' => ['sometimes', 'integer', 'min:1'],
            'language' => ['sometimes', 'nullable', 'string', 'max:255'],
            'hospitality_required' => ['sometimes', 'boolean'],
            'description' => ['sometimes', 'nullable', 'string'],
            'proposed_price' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'currency' => ['sometimes', 'nullable', 'string', 'size:3'],
            'deadline' => ['sometimes', 'nullable', 'date'],
            'special_requirements' => ['sometimes', 'nullable', 'string'],
            'cachet_enabled' => ['sometimes', 'boolean'],
            'travel_expenses_enabled' => ['sometimes', 'boolean'],
            'travel_expenses_max' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'accommodation_enabled' => ['sometimes', 'boolean'],
            'accommodation_details' => ['sometimes', 'nullable', 'string'],
            'status' => ['sometimes', 'string', Rule::in([
                GigStatus::Draft->value,
                GigStatus::Open->value,
                GigStatus::Closed->value,
                GigStatus::Cancelled->value,
            ])],
        ];
    }
}
