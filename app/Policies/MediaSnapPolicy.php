<?php

namespace App\Policies;

use Domain\Media\Enums\MediaStatus;
use Domain\Media\Enums\MediaType;
use Domain\Media\Enums\MediaVisibility;
use Domain\Media\Models\MediaItem;
use Domain\Media\Models\MediaSnap;
use Domain\User\Models\User;

class MediaSnapPolicy
{
    public function view(?User $user, MediaItem $media): bool
    {
        return $media->type === MediaType::Video
            && app(MediaItemPolicy::class)->view($user, $media);
    }

    public function create(User $user, MediaItem $media): bool
    {
        return $media->type === MediaType::Video
            && $media->status === MediaStatus::Ready
            && $media->visibility === MediaVisibility::Public
            && app(MediaItemPolicy::class)->view($user, $media);
    }

    public function update(User $user, MediaSnap $snap): bool
    {
        return $user->isAdmin() || $snap->user_id === $user->id;
    }

    public function delete(User $user, MediaSnap $snap): bool
    {
        return $this->update($user, $snap);
    }
}
