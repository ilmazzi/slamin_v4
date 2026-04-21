<?php

namespace Domain\Auth\Actions;

use Domain\Auth\DTOs\ResetPasswordData;
use Domain\User\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ResetPasswordAction
{
    public function execute(ResetPasswordData $data): void
    {


        $status = Password::reset(
            [
                'email' => $data->email,
                'password' => $data->password,
                'password_confirmation' => $data->password,
                'token' => $data->token,
            ],
            function (User $user, string $password): void {
                $oldEmailVerifiedAt = $user->email_verified_at?->toISOString();
                $user->forceFill([
                    'password' => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();

                $user->tokens()->delete();

                event(new PasswordReset($user));
                audit()->log(
                    event: 'auth.password_reset',
                    userId: $user->id,
                    auditable: $user,
                    oldValues: [
                        'email_verified_at' => $oldEmailVerifiedAt,
                    ],
                    newValues: [
                        'email_verified_at' => $user->fresh()->email_verified_at?->toISOString(),
                    ],
                    metadata: [
                        'source' => 'password_reset',
                    ],
                );
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages([
                'email' => [__($status)],
            ]);
        }


    }
}
