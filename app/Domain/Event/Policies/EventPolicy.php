<?php

namespace Domain\Event\Policies;

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

    public function follow(User $user, Event $event): bool
    {
        return $event->user_id !== $user->id
            && $event->status === EventStatus::Published
            && $event->is_public === true;
    }

    public function comment(User $user, Event $event): bool
    {
        return $this->canInteract($user, $event);
    }

    public function voteAvailability(User $user, Event $event): bool
    {
        if ($event->user_id === $user->id) {
            return true;
        }

        return $event->participantInvites()
            ->where('user_id', $user->id)
            ->where('status', '!=', 'declined')
            ->exists()
            || $event->audienceInvites()
                ->where('user_id', $user->id)
                ->where('status', '!=', 'declined')
                ->exists();
    }

    public function finalizeAvailability(User $user, Event $event): bool
    {
        return $event->user_id === $user->id;
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
