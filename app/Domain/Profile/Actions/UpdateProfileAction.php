<?php

namespace Domain\Profile\Actions;

use Domain\Profile\DTOs\UpdateProfileData;
use Domain\Profile\Models\Profile;
use Domain\Profile\Support\ProfilePayload;
use Domain\Profile\Support\SocialLinks;
use Illuminate\Support\Facades\Storage;

class UpdateProfileAction
{
    public function execute(Profile $profile, UpdateProfileData $data): Profile
    {
        $updates = [];

        if ($data->wasProvided('nickname') && $data->nickname !== null) {
            $updates['nickname'] = $data->nickname;
        }

        if ($data->wasProvided('display_name')) {
            $updates['display_name'] = $data->displayName;
        }

        if ($data->wasProvided('preferred_locale') && $data->preferredLocale !== null) {
            $updates['preferred_locale'] = $data->preferredLocale;
        }

        if ($data->wasProvided('bio')) {
            $updates['bio'] = $data->bio;
        }

        if ($data->wasProvided('location')) {
            $updates['location'] = $data->location;
        }

        if ($data->wasProvided('website_url')) {
            $updates['website_url'] = $data->websiteUrl;
        }

        if ($data->wasProvided('social_links')) {
            $updates['social_links'] = SocialLinks::normalize($data->socialLinks);
        }

        if ($data->wasProvided('spoken_languages')) {
            $updates['spoken_languages'] = collect($data->spokenLanguages ?? [])
                ->map(fn (string $language): string => trim($language))
                ->filter()
                ->unique(fn (string $language): string => mb_strtolower($language))
                ->values()
                ->all();
        }

        if ($data->wasProvided('is_public') && $data->isPublic !== null) {
            $updates['is_public'] = $data->isPublic;
        }

        if ($data->wasProvided('visibility') && $data->visibility !== null) {
            $updates['visibility'] = ProfilePayload::visibility(array_merge(
                ProfilePayload::visibility($profile->visibility),
                $data->visibility,
            ));
        }

        $avatarPath = $profile->avatar_path;

        if ($data->removeAvatar && $avatarPath) {
            Storage::disk('public')->delete($avatarPath);
            $avatarPath = null;
            $updates['avatar_path'] = null;
        }

        if ($data->avatar) {
            if ($avatarPath) {
                Storage::disk('public')->delete($avatarPath);
            }
            $updates['avatar_path'] = $data->avatar->store('avatars', 'public');
        }

        $bannerPath = $profile->banner_path;

        if ($data->removeBanner && $bannerPath) {
            Storage::disk('public')->delete($bannerPath);
            $bannerPath = null;
            $updates['banner_path'] = null;
        }

        if ($data->banner) {
            if ($bannerPath) {
                Storage::disk('public')->delete($bannerPath);
            }
            $updates['banner_path'] = $data->banner->store('banners', 'public');
        }

        if ($updates !== []) {
            $oldValues = $profile->only(array_keys($updates));
            $profile->update($updates);

            audit()->log(
                event: 'profile.updated',
                userId: $profile->user->id,
                auditable: $profile,
                oldValues: $oldValues,
                newValues: collect($updates)->except(['avatar_path', 'banner_path'])->all(),
                metadata: [
                    'source' => 'profile_update',
                    'avatar_changed' => array_key_exists('avatar_path', $updates),
                    'banner_changed' => array_key_exists('banner_path', $updates),
                ],
            );
        }

        return $profile->fresh();
    }
}
