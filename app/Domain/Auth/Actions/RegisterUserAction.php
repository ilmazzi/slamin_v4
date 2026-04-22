<?php

namespace Domain\Auth\Actions;

use Domain\Auth\DTOs\RegisterUserData;
use Domain\User\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class RegisterUserAction
{
    public function execute(RegisterUserData $data): array
    {
        return DB::transaction(function () use ($data): array {
            $user = User::create([
                'email' => $data->email,
                'password' => Hash::make($data->password),
                'account_type' => $data->accountType->value,
            ]);

            $user->profile()->update([
                'nickname' => $data->nickname,
                'display_name' => $data->displayName,
                'preferred_locale' => $data->preferredLocale,
            ]);

            event(new Registered($user));

            $token = $user->createToken('auth_token')->plainTextToken;

            $user->load('profile');

            audit()->log(
                event: 'auth.registered',
                userId: $user->id,
                auditable: $user,
                newValues: [
                    'public_id' => $user->public_id,
                    'email' => $user->email,
                    'status' => $user->status,
                    'account_type' => $user->account_type->value,
                    'profile' => [
                        'public_id' => $user->profile->public_id,
                        'nickname' => $user->profile->nickname,
                        'display_name' => $user->profile->display_name,
                        'preferred_locale' => $user->profile->preferred_locale,
                    ],
                ],
                metadata: [
                    'channel' => 'api',
                ],
            );

            return [
                'token' => $token,
                'user' => $user,
            ];
        });
    }
}
