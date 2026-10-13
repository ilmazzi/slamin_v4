<?php

use Domain\User\Enums\AccountType;
use Domain\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

it('creates an admin user', function () {
    $this->artisan('user:admin', [
        'email' => 'admin@slamin.test',
        '--password' => 'password1',
    ])->assertSuccessful();

    $user = User::query()->where('email', 'admin@slamin.test')->first();

    expect($user)->not->toBeNull()
        ->and($user->isAdmin())->toBeTrue()
        ->and($user->account_type)->toBe(AccountType::PoetOrganizer)
        ->and($user->email_verified_at)->not->toBeNull()
        ->and(Hash::check('password1', $user->password))->toBeTrue();
});

it('assigns the admin role to an existing user without changing the password', function () {
    $user = User::factory()->create([
        'email' => 'already@slamin.test',
        'password' => Hash::make('original-password'),
        'account_type' => AccountType::Audience,
    ]);

    $this->artisan('user:admin', [
        'email' => 'already@slamin.test',
    ])->assertSuccessful();

    $user->refresh();

    expect($user->isAdmin())->toBeTrue()
        ->and($user->account_type)->toBe(AccountType::Audience)
        ->and(Hash::check('original-password', $user->password))->toBeTrue();
});

it('requires a password when the user does not exist', function () {
    $this->artisan('user:admin', [
        'email' => 'new@slamin.test',
    ])->assertFailed();

    expect(User::query()->where('email', 'new@slamin.test')->exists())->toBeFalse();
});
