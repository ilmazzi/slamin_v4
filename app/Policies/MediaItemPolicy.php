<?php

namespace App\Policies;

use Domain\Media\Enums\MediaStatus;
use Domain\Media\Enums\MediaVisibility;
use Domain\Media\Models\MediaItem;
use Domain\User\Models\User;

class MediaItemPolicy
{
    public function view(?User $user, MediaItem $media): bool
    {
        if ($user && ($media->user_id === $user->id || $user->isAdmin())) {
            return true;
        }

        return $media->status === MediaStatus::Ready
            && $media->visibility === MediaVisibility::Public;
    }

    public function update(User $user, MediaItem $media): bool
    {
        return $media->user_id === $user->id || $user->isAdmin();
    }

    public function delete(User $user, MediaItem $media): bool
    {
        return $media->user_id === $user->id || $user->isAdmin();
    }

    public function like(User $user, MediaItem $media): bool
    {
        return $this->view($user, $media);
    }

    public function comment(User $user, MediaItem $media): bool
    {
        return $this->view($user, $media);
    }
}
