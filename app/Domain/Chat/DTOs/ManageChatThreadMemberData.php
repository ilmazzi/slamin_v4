<?php

namespace Domain\Chat\DTOs;

use App\Http\Requests\Api\V1\Chat\ManageChatThreadMemberRequest;

class ManageChatThreadMemberData
{
    public function __construct(
        public readonly int $userId,
    ) {
    }

    public static function fromRequest(ManageChatThreadMemberRequest $request): self
    {
        return new self(
            userId: (int) $request->integer('user_id'),
        );
    }
}
