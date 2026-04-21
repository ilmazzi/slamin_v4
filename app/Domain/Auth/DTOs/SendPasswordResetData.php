<?php

namespace Domain\Auth\DTOs;

use App\Http\Requests\Api\V1\Auth\ForgotPasswordRequest;

class SendPasswordResetData
{
    public function __construct(
        public readonly string $email,
    ) {
    }

    public static function fromRequest(ForgotPasswordRequest $request): self
    {
        $validated = $request->validated();

        return new self(
            email: $validated['email'],
        );
    }
}
