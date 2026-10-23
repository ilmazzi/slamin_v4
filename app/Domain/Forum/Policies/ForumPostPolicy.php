<?php

namespace Domain\Forum\Policies;

use Domain\Forum\Models\ForumPost;
use Domain\User\Models\User;

class ForumPostPolicy
{
    public function view(?User $user, ForumPost $post): bool
    {
        if ($post->isPubliclyVisible()) {
            return true;
        }

        if (! $user) {
            return false;
        }

        return $post->user_id === $user->id || $this->isStaff($user);
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, ForumPost $post): bool
    {
        return $post->user_id === $user->id || $this->isStaff($user);
    }

    public function delete(User $user, ForumPost $post): bool
    {
        return $post->user_id === $user->id || $this->isStaff($user);
    }

    public function moderate(User $user, ForumPost $post): bool
    {
        return $this->isStaff($user);
    }

    public function vote(User $user, ForumPost $post): bool
    {
        return $this->view($user, $post);
    }

    private function isStaff(User $user): bool
    {
        return $user->isAdmin() || $user->isModerator();
    }
}
