<?php

namespace Domain\Moderation\DTOs;

use App\Http\Requests\Api\V1\Moderation\ListModerationEventsRequest;

class ListModerationEventsData
{
    public function __construct(
        public readonly int $perPage,
        public readonly ?string $status,
        public readonly ?string $query,
        public readonly ?int $eventCategoryId,
        public readonly ?int $festivalEventId,
    ) {
    }

    public static function fromRequest(ListModerationEventsRequest $request): self
    {
        return new self(
            perPage: (int) $request->validated('per_page', 10),
            status: $request->validated('status'),
            query: $request->validated('q'),
            eventCategoryId: $request->validated('event_category_id'),
            festivalEventId: $request->validated('festival_event_id'),
        );
    }
}
