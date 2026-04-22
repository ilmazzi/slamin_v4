<?php

namespace Domain\Group\DTOs;

use App\Http\Requests\Api\V1\Group\StoreGroupAnnouncementRequest;

class StoreGroupAnnouncementData
{
    public function __construct(
        public readonly int $userId,
        public readonly string $title,
        public readonly string $content,
        public readonly bool $isPinned,
    ) {
    }

    public static function fromRequest(StoreGroupAnnouncementRequest $request): self
    {
        return new self(
            userId: (int) $request->user()->id,
            title: $request->validated('title'),
            content: $request->validated('content'),
            isPinned: (bool) $request->validated('is_pinned', false),
        );
    }
}
