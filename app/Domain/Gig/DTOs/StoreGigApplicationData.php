<?php

namespace Domain\Gig\DTOs;

use App\Http\Requests\Api\V1\Gig\StoreGigApplicationRequest;

class StoreGigApplicationData
{
    public function __construct(
        public readonly ?string $message,
    ) {
    }

    public static function fromRequest(StoreGigApplicationRequest $request): self
    {
        return new self(
            message: $request->filled('message') ? $request->string('message')->toString() : null,
        );
    }
}
