<?php

namespace Domain\Gig\DTOs;

use App\Http\Requests\Api\V1\Gig\ListOpenGigsRequest;
use Domain\Gig\Enums\GigType;

class ListOpenGigsData
{
    public function __construct(
        public readonly ?string $search,
        public readonly ?GigType $type,
        public readonly string $sort,
        public readonly int $perPage,
        public readonly ?int $eventId = null,
    ) {}

    public static function fromRequest(ListOpenGigsRequest $request): self
    {
        $search = trim((string) $request->validated('q', ''));
        $type = $request->validated('type');

        return new self(
            search: $search === '' ? null : $search,
            type: $type ? GigType::from($type) : null,
            sort: (string) ($request->validated('sort') ?? 'newest'),
            perPage: (int) ($request->validated('per_page') ?? 10),
            eventId: $request->validated('event_id') !== null ? (int) $request->validated('event_id') : null,
        );
    }
}
