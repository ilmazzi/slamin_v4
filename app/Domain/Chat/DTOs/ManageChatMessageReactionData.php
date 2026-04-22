<?php

namespace Domain\Chat\DTOs;

use App\Http\Requests\Api\V1\Chat\ManageChatMessageReactionRequest;

class ManageChatMessageReactionData
{
    public function __construct(
        public readonly string $emoji,
    ) {
    }

    public static function fromRequest(ManageChatMessageReactionRequest $request): self
    {
        return new self(
            emoji: trim($request->string('emoji')->toString()),
        );
    }
}
