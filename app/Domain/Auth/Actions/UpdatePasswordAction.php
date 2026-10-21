<?php

namespace Domain\Auth\Actions;

use Domain\Auth\DTOs\UpdatePasswordData;
use Domain\User\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UpdatePasswordAction
{
    public function execute(User $user, UpdatePasswordData $data, ?int $currentTokenId = null): void
    {
        $user->forceFill([
            'password' => Hash::make($data->password),
            'remember_token' => Str::random(60),
        ])->save();

        $user->tokens()
            ->when(
                $currentTokenId !== null,
                fn ($query) => $query->whereKeyNot($currentTokenId),
            )
            ->delete();

        audit()->log(
            event: 'auth.password_updated',
            userId: $user->id,
            auditable: $user,
            metadata: [
                'source' => 'profile_password_update',
            ],
        );
    }
}
