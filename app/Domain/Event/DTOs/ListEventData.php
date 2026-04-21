<?php

namespace Domain\Event\DTOs;

use App\Http\Requests\Api\V1\Event\ListEventRequest;

class ListEventData
{
    public function __construct(
        public readonly int $perPage,
        public readonly ?int $eventCategoryId,
        public readonly ?int $festivalEventId,
    ) {
    }

    public static function fromRequest(ListEventRequest $request): self
    {
        return new self(
            perPage: (int) $request->validated('per_page', 10),
            eventCategoryId: $request->validated('event_category_id'),
            festivalEventId: $request->validated('festival_event_id'),
        );
    }
}
