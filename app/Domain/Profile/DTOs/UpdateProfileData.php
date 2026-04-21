<?php

namespace Domain\Profile\DTOs;

class UpdateProfileData
{
    public function __construct(
        public readonly ?string $nickname,
        public readonly ?string $displayName,
        public readonly ?string $preferredLocale,
    ) {
    }
}
