<?php

namespace Domain\Group\DTOs;

use App\Http\Requests\Api\V1\Group\UpdateGroupAnnouncementRequest;

class UpdateGroupAnnouncementData extends StoreGroupAnnouncementData
{
    public static function fromRequest(UpdateGroupAnnouncementRequest $request): self
    {
        return new self(
            userId: (int) $request->user()->id,
            title: $request->validated('title'),
            content: $request->validated('content'),
            isPinned: (bool) $request->validated('is_pinned', false),
        );
    }
}
