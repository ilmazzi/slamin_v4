<?php

namespace Domain\Gig\DTOs;

use App\Http\Requests\Api\V1\Gig\UpdateGigAnnouncementRequest;
use Domain\Gig\Enums\GigAnnouncementStatus;

class UpdateGigAnnouncementData
{
    public function __construct(
        public readonly ?string $message,
        public readonly bool $messageProvided,
        public readonly ?GigAnnouncementStatus $status,
    ) {
    }

    public static function fromRequest(UpdateGigAnnouncementRequest $request): self
    {
        return new self(
            message: $request->filled('message') ? $request->string('message')->toString() : null,
            messageProvided: $request->exists('message'),
            status: $request->filled('status') ? GigAnnouncementStatus::from($request->string('status')->toString()) : null,
        );
    }
}
