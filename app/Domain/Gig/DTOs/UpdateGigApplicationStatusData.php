<?php

namespace Domain\Gig\DTOs;

use App\Http\Requests\Api\V1\Gig\UpdateGigApplicationStatusRequest;
use Domain\Gig\Enums\GigApplicationStatus;

class UpdateGigApplicationStatusData
{
    public function __construct(
        public readonly GigApplicationStatus $status,
    ) {
    }

    public static function fromRequest(UpdateGigApplicationStatusRequest $request): self
    {
        return new self(
            status: GigApplicationStatus::from($request->string('status')->toString()),
        );
    }
}
