<?php

namespace Domain\Group\DTOs;

use App\Http\Requests\Api\V1\Group\UpdateGroupRequest;

class UpdateGroupData extends StoreGroupData
{
    public function __construct(
        int $userId,
        string $name,
        ?string $description,
        ?\Illuminate\Http\UploadedFile $image,
        \Domain\Group\Enums\GroupVisibility $visibility,
        ?string $websiteUrl,
        ?string $facebookUrl,
        ?string $instagramUrl,
        ?string $youtubeUrl,
        public readonly bool $removeImage,
    ) {
        parent::__construct(
            $userId,
            $name,
            $description,
            $image,
            $visibility,
            $websiteUrl,
            $facebookUrl,
            $instagramUrl,
            $youtubeUrl,
        );
    }

    public static function fromRequest(UpdateGroupRequest $request): self
    {
        $base = StoreGroupData::fromRequest($request);

        return new self(
            userId: $base->userId,
            name: $base->name,
            description: $base->description,
            image: $base->image,
            visibility: $base->visibility,
            websiteUrl: $base->websiteUrl,
            facebookUrl: $base->facebookUrl,
            instagramUrl: $base->instagramUrl,
            youtubeUrl: $base->youtubeUrl,
            removeImage: (bool) $request->validated('remove_image', false),
        );
    }
}
