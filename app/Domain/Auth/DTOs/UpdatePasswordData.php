<?php

namespace Domain\Auth\DTOs;

use App\Http\Requests\Api\V1\Auth\UpdatePasswordRequest;

class UpdatePasswordData
{
    public function __construct(
        public readonly string $password,
    ) {}

    public static function fromRequest(UpdatePasswordRequest $request): self
    {
        return new self(
            password: (string) $request->validated('password'),
        );
    }
}
