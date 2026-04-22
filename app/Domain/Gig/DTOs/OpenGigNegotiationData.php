<?php

namespace Domain\Gig\DTOs;

use App\Http\Requests\Api\V1\Gig\OpenGigNegotiationRequest;

class OpenGigNegotiationData
{
    public function __construct(
        public readonly ?string $price,
        public readonly ?string $currency,
        public readonly ?string $deadline,
        public readonly ?string $note,
    ) {
    }

    public static function fromRequest(OpenGigNegotiationRequest $request): self
    {
        return new self(
            price: $request->filled('price') ? (string) $request->input('price') : null,
            currency: $request->filled('currency') ? strtoupper($request->string('currency')->toString()) : null,
            deadline: $request->filled('deadline') ? $request->string('deadline')->toString() : null,
            note: $request->filled('note') ? $request->string('note')->toString() : null,
        );
    }
}
