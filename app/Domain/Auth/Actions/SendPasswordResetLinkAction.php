<?php

namespace Domain\Auth\Actions;

use Domain\Auth\DTOs\SendPasswordResetData;
use Domain\User\Models\User;
use Illuminate\Support\Facades\Password;

class SendPasswordResetLinkAction
{
    public function execute(SendPasswordResetData $data): void
    {
        Password::sendResetLink([
            'email' => $data->email,
        ]);
    }
}
