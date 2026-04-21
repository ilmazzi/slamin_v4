<?php

namespace Domain\Profile\Actions;

use Domain\Profile\DTOs\UpdateProfileData;
use Domain\Profile\Models\Profile;

class UpdateProfileAction
{
    public function execute(Profile $profile, UpdateProfileData $data): Profile
    {
        $updates = [];

        if ($data->nickname !== null) {
            $updates['nickname'] = $data->nickname;
        }

        if ($data->displayName !== null || array_key_exists('display_name', [
            'display_name' => $data->displayName,
        ])) {
            $updates['display_name'] = $data->displayName;
        }

        if ($data->preferredLocale !== null) {
            $updates['preferred_locale'] = $data->preferredLocale;
        }

        if ($updates !== []) {
            $profile->update($updates);

            $oldValues = $profile->only(array_keys($updates));

            audit()->log(
                event: 'profile.updated',
                userId: $profile->user->id,
                auditable: $profile,
                oldValues: $oldValues,
                newValues: $updates,
                metadata: [
                    'source' => 'profile_update',
                ],
            );
        }
        return $profile->fresh();
    }
}
