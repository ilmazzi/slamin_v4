<?php

namespace Domain\Profile\DTOs;

use Illuminate\Http\UploadedFile;

class UpdateProfileData
{
    /**
     * @param  array<string, bool>|null  $visibility
     * @param  list<string>  $provided
     */
    public function __construct(
        public readonly ?string $nickname,
        public readonly ?string $displayName,
        public readonly ?string $preferredLocale,
        public readonly ?string $bio,
        public readonly ?string $location,
        public readonly ?string $websiteUrl,
        public readonly ?bool $isPublic,
        public readonly ?array $visibility,
        public readonly ?UploadedFile $avatar,
        public readonly bool $removeAvatar,
        public readonly ?UploadedFile $banner,
        public readonly bool $removeBanner,
        public readonly array $provided,
    ) {}

    public function wasProvided(string $field): bool
    {
        return in_array($field, $this->provided, true);
    }
}
