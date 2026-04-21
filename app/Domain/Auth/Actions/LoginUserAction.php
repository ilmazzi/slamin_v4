<?php

namespace Domain\Auth\Actions;

use Domain\Auth\DTOs\LoginUserData;
use Domain\User\Models\User;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\Hash;

class LoginUserAction
{
    public function execute(LoginUserData $data): User
    {
        $user = User::query()
            ->where('email', $data->email)
            ->first();

        if (! $user || ! Hash::check($data->password, $user->password)) {
            throw new AuthenticationException(__('auth.invalid_credentials'));
        }

        audit()->log(
            event: 'login',
            userId: $user->id,
            auditable: $user,
            newValues: [
                'email' => $user->email,
                'status' => $user->status,
                'account_type' => $user->account_type?->value,
            ],
            metadata: [
                'source' => 'login',
            ],
        );

        return $user->fresh()->load('profile');
    }
}
