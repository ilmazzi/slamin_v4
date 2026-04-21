<?php

namespace Domain\Auth\DTOs;

use App\Http\Requests\Api\V1\Auth\VerifyEmailRequest;

class VerifyEmailData
{
    public function __construct(
        public readonly int $id,
        public readonly string $hash,
        public readonly int $expires,
        public readonly string $signature,
    ) {
    }

    public static function fromRequest(VerifyEmailRequest $request): self
    {
        $validated = $request->validated();

        return new self(
            id: (int) $validated['id'],
            hash: $validated['hash'],
            expires: (int) $validated['expires'],
            signature: $validated['signature'],
        );
    }
}
