<?php

namespace Domain\Auth\DTOs;

class LoginUserData
{
    public function __construct(
        public readonly string $email,
        public readonly string $password,
    ) {
    }
}
