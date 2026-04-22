<?php

namespace Domain\Gig\DTOs;

use App\Http\Requests\Api\V1\Gig\StoreGigAnnouncementRequest;
use Domain\Gig\Enums\GigAnnouncementStatus;

class StoreGigAnnouncementData
{
    public function __construct(
        public readonly string $message,
        public readonly GigAnnouncementStatus $status,
    ) {
    }

    public static function fromRequest(StoreGigAnnouncementRequest $request): self
    {
        return new self(
            message: $request->string('message')->toString(),
            status: GigAnnouncementStatus::from($request->input('status', GigAnnouncementStatus::Draft->value)),
        );
    }
}
