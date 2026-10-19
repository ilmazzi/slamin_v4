<?php

namespace Domain\Group\DTOs;

use App\Http\Requests\Api\V1\Group\StoreGroupRequest;

class UpdateGroupData extends StoreGroupData
{
    public function __construct(
        int $userId,
        string $name,
        ?string $description,
        ?\Illuminate\Http\UploadedFile $image,
        int $groupCategoryId,
        \Domain\Group\Enums\GroupVisibility $visibility,
        bool $isListed,
        bool $autoAcceptEvents,
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
            $groupCategoryId,
            $visibility,
            $isListed,
            $autoAcceptEvents,
            [],
            $websiteUrl,
            $facebookUrl,
            $instagramUrl,
            $youtubeUrl,
        );
    }

    public static function fromRequest(StoreGroupRequest $request): self
    {
        $base = parent::fromRequest($request);

        return new self(
            userId: $base->userId,
            name: $base->name,
            description: $base->description,
            image: $base->image,
            groupCategoryId: $base->groupCategoryId,
            visibility: $base->visibility,
            isListed: $base->isListed,
            autoAcceptEvents: $base->autoAcceptEvents,
            websiteUrl: $base->websiteUrl,
            facebookUrl: $base->facebookUrl,
            instagramUrl: $base->instagramUrl,
            youtubeUrl: $base->youtubeUrl,
            removeImage: $request->boolean('remove_image'),
        );
    }
}
