<?php

namespace App\Http\Requests\Api\V1\Gig;

use Domain\Gig\Enums\GigStatus;
use Domain\Gig\Enums\GigType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEventGigRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'type' => ['required', 'string', Rule::in([
                GigType::PoetArtist->value,
                GigType::McHost->value,
                GigType::TechnicalSupport->value,
                GigType::Volunteer->value,
            ])],
            'quantity' => ['required', 'integer', 'min:1'],
            'language' => ['nullable', 'string', 'max:255'],
            'hospitality_required' => ['nullable', 'boolean'],
            'description' => ['nullable', 'string'],
            'proposed_price' => ['nullable', 'numeric', 'min:0'],
            'currency' => ['nullable', 'string', 'size:3'],
            'deadline' => ['nullable', 'date'],
            'special_requirements' => ['nullable', 'string'],
            'cachet_enabled' => ['nullable', 'boolean'],
            'travel_expenses_enabled' => ['nullable', 'boolean'],
            'travel_expenses_max' => ['nullable', 'numeric', 'min:0'],
            'accommodation_enabled' => ['nullable', 'boolean'],
            'accommodation_details' => ['nullable', 'string'],
            'status' => ['nullable', 'string', Rule::in([
                GigStatus::Draft->value,
                GigStatus::Open->value,
                GigStatus::Closed->value,
                GigStatus::Cancelled->value,
            ])],
        ];
    }
}
