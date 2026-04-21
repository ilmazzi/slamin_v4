<?php

namespace Domain\Auth\DTOs;

use Domain\User\Enums\AccountType;
use App\Http\Requests\Api\V1\Auth\RegisterRequest;

class RegisterUserData
{
    public function __construct(
        public readonly string $email,
        public readonly string $password,
        public readonly string $nickname,
        public readonly ?string $displayName,
        public readonly string $preferredLocale,
        public readonly AccountType $accountType,
    ) {
    }
    public static function fromRequest(RegisterRequest $request): self
    {
        $validated = $request->validated();

        return new self(
            email: $validated['email'],
            password: $validated['password'],
            nickname: $validated['nickname'],
            displayName: $validated['display_name'] ?? null,
            preferredLocale: $validated['preferred_locale'] ?? 'it',
            accountType: AccountType::from($validated['account_type']),
        );
    }
}
