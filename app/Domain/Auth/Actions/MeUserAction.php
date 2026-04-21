<?php

namespace Domain\Auth\Actions;

use Domain\User\Models\User;

class MeUserAction
{
    public function execute(User $user): User
    {
        return $user->fresh()->load('profile');
    }
}
