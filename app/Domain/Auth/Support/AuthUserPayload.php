<?php

namespace Domain\Auth\Support;

use Domain\Profile\Support\ProfilePayload;
use Domain\User\Models\User;

final class AuthUserPayload
{
    /**
     * @return array<string, mixed>
     */
    public static function from(User $user): array
    {
        $user->loadMissing(['profile', 'roles']);
        $profile = ProfilePayload::owner($user);

        return [
            'public_id' => $user->public_id,
            'email' => $user->email,
            'email_verified_at' => $user->email_verified_at?->toISOString(),
            'email_verified' => $user->hasVerifiedEmail(),
            'status' => $user->status,
            'account_type' => $user->account_type?->value,
            'roles' => $user->roles->pluck('name')->filter()->values()->all(),
            'profile_photo_url' => $profile['avatar_url'],
            'banner_image_url' => $profile['banner_url'],
            'profile' => $profile,
        ];
    }
}
