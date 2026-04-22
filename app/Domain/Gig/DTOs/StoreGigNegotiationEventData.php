<?php

namespace Domain\Gig\DTOs;

use App\Http\Requests\Api\V1\Gig\StoreGigNegotiationEventRequest;
use Domain\Gig\Enums\GigNegotiationEventType;

class StoreGigNegotiationEventData
{
    public function __construct(
        public readonly GigNegotiationEventType $eventType,
        public readonly ?string $price,
        public readonly ?string $currency,
        public readonly ?string $deadline,
        public readonly ?string $note,
    ) {
    }

    public static function fromRequest(StoreGigNegotiationEventRequest $request): self
    {
        return new self(
            eventType: GigNegotiationEventType::from($request->string('event_type')->toString()),
            price: $request->filled('price') ? (string) $request->input('price') : null,
            currency: $request->filled('currency') ? strtoupper($request->string('currency')->toString()) : null,
            deadline: $request->filled('deadline') ? $request->string('deadline')->toString() : null,
            note: $request->filled('note') ? $request->string('note')->toString() : null,
        );
    }
}
