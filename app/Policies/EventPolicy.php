<?php

namespace App\Policies;

use Domain\Event\Enums\EventStatus;
use Domain\Event\Models\Event;
use Domain\User\Models\User;

class EventPolicy
{
    public function update(User $user, Event $event): bool
    {
        return $event->user_id === $user->id;
    }

    public function delete(User $user, Event $event): bool
    {
        return $event->user_id === $user->id;
    }

    public function like(User $user, Event $event): bool
    {
        return $this->canInteract($user, $event);
    }

    public function bookmark(User $user, Event $event): bool
    {
        return $this->canInteract($user, $event);
    }

    private function canInteract(User $user, Event $event): bool
    {
        if ($event->user_id === $user->id) {
            return true;
        }

        return $event->status === EventStatus::Published
            && $event->is_public === true;
    }
}
