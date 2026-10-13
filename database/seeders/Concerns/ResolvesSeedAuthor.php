<?php

namespace Database\Seeders\Concerns;

use Domain\User\Enums\AccountType;
use Domain\User\Models\User;
use Illuminate\Support\Facades\Hash;

trait ResolvesSeedAuthor
{
    protected function resolveSeedAuthor(): User
    {
        $existing = User::query()->first();

        if ($existing) {
            return $existing;
        }

        // Avoid Factories/fake() on Forge (--no-dev has no fakerphp/faker).
        return User::query()->create([
            'email' => 'seed.poet@slamin.local',
            'password' => Hash::make('password'),
            'account_type' => AccountType::PoetOrganizer,
            'status' => 'active',
            'email_verified_at' => now(),
        ]);
    }
}
