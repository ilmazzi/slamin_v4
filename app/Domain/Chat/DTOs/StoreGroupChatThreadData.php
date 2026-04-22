<?php

namespace Domain\Chat\DTOs;

use App\Http\Requests\Api\V1\Chat\StoreGroupChatThreadRequest;

class StoreGroupChatThreadData
{
    /**
     * @param  list<int>  $userIds
     */
    public function __construct(
        public readonly string $title,
        public readonly array $userIds,
    ) {
    }

    public static function fromRequest(StoreGroupChatThreadRequest $request): self
    {
        return new self(
            title: trim($request->string('title')->toString()),
            userIds: array_map('intval', $request->input('user_ids', [])),
        );
    }
}
