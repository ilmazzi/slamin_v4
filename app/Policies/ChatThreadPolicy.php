<?php

namespace App\Policies;

use Domain\Chat\Models\ChatThread;
use Domain\User\Models\User;

class ChatThreadPolicy
{
    public function view(User $user, ChatThread $thread): bool
    {
        if ($thread->isAttachedGroup()) {
            return $thread->group?->hasActiveMember($user->id)
                ?? false;
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
