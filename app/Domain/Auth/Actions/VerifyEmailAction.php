<?php

namespace Domain\Auth\Actions;

use Domain\Auth\DTOs\VerifyEmailData;
use Domain\User\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class VerifyEmailAction
{
    public function execute(VerifyEmailData $data): void
    {
        if (Carbon::now()->timestamp > $data->expires) {
            throw ValidationException::withMessages([
                'signature' => [__('auth.invalid_verification_link')],
            ]);
        }

        $user = User::find($data->id);

        if (! $user) {
            throw ValidationException::withMessages([
                'user' => [__('auth.user_not_found')],
            ]);
        }

        $expectedHash = sha1($user->getEmailForVerification());

        if (! hash_equals($expectedHash, $data->hash)) {
            throw ValidationException::withMessages([
                'hash' => [__('auth.invalid_verification_link')],
            ]);
        }

        $expectedSignature = hash_hmac(
            'sha256',
            "{$data->id}|{$data->hash}|{$data->expires}",
            config('app.key')
        );

        if (! hash_equals($expectedSignature, $data->signature)) {
            throw ValidationException::withMessages([
                'signature' => [__('auth.invalid_verification_link')],
            ]);
        }

        if ($user->hasVerifiedEmail()) {
            return;
        }

        $oldEmailVerifiedAt = $user->email_verified_at;

        $user->markEmailAsVerified();

        audit()->log(
            event: 'auth.email_verified',
            userId: $user->id,
            auditable: $user,
            oldValues: [
                'email_verified_at' => $oldEmailVerifiedAt?->toISOString(),
            ],
            newValues: [
                'email_verified_at' => $user->fresh()->email_verified_at?->toISOString(),
            ],
            metadata: [
                'source' => 'email_verification_link',
            ],
        );
    }
}
