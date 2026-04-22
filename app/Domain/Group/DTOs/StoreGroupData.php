<?php

namespace Domain\Group\DTOs;

use App\Http\Requests\Api\V1\Group\StoreGroupRequest;
use Domain\Group\Enums\GroupVisibility;
use Illuminate\Http\UploadedFile;

class StoreGroupData
{
    public function __construct(
        public readonly int $userId,
        public readonly string $name,
        public readonly ?string $description,
        public readonly ?UploadedFile $image,
        public readonly GroupVisibility $visibility,
        public readonly ?string $websiteUrl,
        public readonly ?string $facebookUrl,
        public readonly ?string $instagramUrl,
        public readonly ?string $youtubeUrl,
    ) {
    }

    public static function fromRequest(StoreGroupRequest $request): self
    {
        return new self(
            userId: (int) $request->user()->id,
            name: $request->validated('name'),
            description: $request->validated('description'),
            image: $request->file('image'),
            visibility: GroupVisibility::from($request->validated('visibility')),
            websiteUrl: $request->validated('website_url'),
            facebookUrl: $request->validated('facebook_url'),
            instagramUrl: $request->validated('instagram_url'),
            youtubeUrl: $request->validated('youtube_url'),
        );
    }
}
