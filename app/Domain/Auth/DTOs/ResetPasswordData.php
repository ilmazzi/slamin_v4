<?php

namespace Domain\Auth\DTOs;

use App\Http\Requests\Api\V1\Auth\ResetPasswordRequest;

class ResetPasswordData
{
    public function __construct(
        public readonly string $token,
        public readonly string $email,
        public readonly string $password,
        public readonly string $passwordConfirmation,
    ) {
    }

    public static function fromRequest(ResetPasswordRequest $request): self
    {
        $validated = $request->validated();

        return new self(
            token: $validated['token'],
            email: $validated['email'],
            password: $validated['password'],
            passwordConfirmation: $validated['password_confirmation'],
        );
    }
}
