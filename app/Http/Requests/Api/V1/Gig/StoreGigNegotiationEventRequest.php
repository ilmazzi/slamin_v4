<?php

namespace App\Http\Requests\Api\V1\Gig;

use Domain\Gig\Enums\GigNegotiationEventType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreGigNegotiationEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'event_type' => ['required', 'string', Rule::in([
                GigNegotiationEventType::OwnerOffer->value,
                GigNegotiationEventType::TranslatorOffer->value,
                GigNegotiationEventType::CounterOffer->value,
                GigNegotiationEventType::Note->value,
            ])],
            'price' => ['nullable', 'numeric', 'min:0'],
            'currency' => ['nullable', 'string', 'size:3'],
            'deadline' => ['nullable', 'date'],
            'note' => ['nullable', 'string'],
        ];
    }
}
