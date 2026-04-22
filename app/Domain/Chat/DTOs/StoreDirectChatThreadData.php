<?php

namespace Domain\Chat\DTOs;

use App\Http\Requests\Api\V1\Chat\StoreDirectChatThreadRequest;

class StoreDirectChatThreadData
{
    public function __construct(
        public readonly int $targetUserId,
    ) {
    }

    public static function fromRequest(StoreDirectChatThreadRequest $request): self
    {
        return new self(
            targetUserId: (int) $request->integer('user_id'),
        );
    }
}
