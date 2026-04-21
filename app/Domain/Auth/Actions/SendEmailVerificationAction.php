<?php

namespace Domain\Auth\Actions;

use Domain\User\Models\User;

class SendEmailVerificationAction
{
    public function execute(User $user): void
    {
        if ($user->hasVerifiedEmail()) {
            return;
        }

        $user->sendEmailVerificationNotification();
    }
}
