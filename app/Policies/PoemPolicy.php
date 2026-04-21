<?php

namespace App\Policies;

use Domain\Poetry\Enums\PoemStatus;
use Domain\Poetry\Models\Poem;
use Domain\User\Models\User;

class PoemPolicy
{
    public function update(User $user, Poem $poem): bool
    {
        return $poem->user_id === $user->id;
    }

    public function delete(User $user, Poem $poem): bool
    {
        return $poem->user_id === $user->id;
    }
    public function like(User $user, Poem $poem): bool
    {
        return $this->canInteract($user, $poem);
    }

    public function bookmark(User $user, Poem $poem): bool
    {
        return $this->canInteract($user, $poem);
    }

    private function canInteract(User $user, Poem $poem): bool
    {
        if ($poem->user_id === $user->id) {
            return true;
        }

        return $poem->status === PoemStatus::Published
            && $poem->is_public === true;
    }
}
