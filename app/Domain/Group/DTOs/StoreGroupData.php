<?php

namespace Domain\Group\DTOs;

use App\Http\Requests\Api\V1\Group\StoreGroupRequest;
use Domain\Group\Enums\GroupChatAudience;
use Domain\Group\Enums\GroupVisibility;
use Illuminate\Http\UploadedFile;

class StoreGroupData
{
    /**
     * @param  list<GroupChatAudience>  $chatAudiences
     */
    public function __construct(
        public readonly int $userId,
        public readonly string $name,
        public readonly ?string $description,
        public readonly ?UploadedFile $image,
        public readonly int $groupCategoryId,
        public readonly GroupVisibility $visibility,
        public readonly bool $isListed,
        public readonly bool $autoAcceptEvents,
        public readonly array $chatAudiences,
        public readonly ?string $websiteUrl,
        public readonly ?string $facebookUrl,
        public readonly ?string $instagramUrl,
        public readonly ?string $youtubeUrl,
    ) {
    }

    public static function fromRequest(StoreGroupRequest $request): self
    {
        $visibility = GroupVisibility::from($request->validated('visibility'));

        return new self(
            userId: (int) $request->user()->id,
            name: $request->validated('name'),
            description: $request->validated('description'),
            image: $request->file('image'),
            groupCategoryId: (int) $request->validated('group_category_id'),
            visibility: $visibility,
            isListed: $visibility === GroupVisibility::Public
                ? true
                : (bool) ($request->validated('is_listed') ?? true),
            autoAcceptEvents: (bool) ($request->validated('auto_accept_events') ?? false),
            chatAudiences: array_values(array_filter(array_map(
                fn ($value) => GroupChatAudience::tryFrom((string) $value),
                $request->validated('chat_audiences') ?? [],
            ))),
            websiteUrl: $request->validated('website_url'),
            facebookUrl: $request->validated('facebook_url'),
            instagramUrl: $request->validated('instagram_url'),
            youtubeUrl: $request->validated('youtube_url'),
        );
    }
}
