<?php

namespace Domain\Group\DTOs;

use App\Http\Requests\Api\V1\Group\UpdateGroupChatThreadsRequest;
use Domain\Group\Enums\GroupChatAudience;

class UpdateGroupChatThreadsData
{
    /**
     * @param  list<GroupChatAudience>  $audiences
     */
    public function __construct(
        public readonly array $audiences,
    ) {}

    public static function fromRequest(UpdateGroupChatThreadsRequest $request): self
    {
        $audiences = array_values(array_filter(
            array_map(
                fn ($value) => GroupChatAudience::tryFrom((string) $value),
                $request->validated('audiences') ?? [],
            ),
        ));

        return new self(audiences: $audiences);
    }
}
