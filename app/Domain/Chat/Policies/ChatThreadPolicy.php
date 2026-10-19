<?php

namespace Domain\Chat\Policies;

use Domain\Chat\Models\ChatThread;
use Domain\User\Models\User;

class ChatThreadPolicy
{
    public function view(User $user, ChatThread $thread): bool
    {
        if ($thread->isAttachedGroup() && ! $thread->isEnabledAttachedGroup()) {
            return false;
        }

        return $thread->memberRecords()
            ->where('user_id', $user->id)
            ->exists();
    }

    public function send(User $user, ChatThread $thread): bool
    {
        return $this->view($user, $thread);
    }

    public function manageMembers(User $user, ChatThread $thread): bool
    {
        return $thread->isGroup()
            && ! $thread->isAttachedGroup()
            && $thread->created_by_user_id === $user->id;
    }
}
