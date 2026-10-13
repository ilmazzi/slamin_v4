<?php

use Domain\User\Models\Role;
use Domain\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

function adminUserForManagement(): User
{
    $user = User::factory()->create();
    $role = Role::query()->firstOrCreate(['name' => 'admin'], ['label' => 'Admin']);
    $user->roles()->attach($role);

    return $user;
}

it('lists and filters users for administrators', function () {
    $admin = adminUserForManagement();
    $target = User::factory()->create(['email' => 'poeta@example.com']);
    $target->profile()->update(['display_name' => 'Poeta Test']);

    Sanctum::actingAs($admin);

    $this->getJson('/api/v1/admin/users?search=Poeta')
        ->assertOk()
        ->assertJsonPath('data.users.0.id', $target->id)
        ->assertJsonPath('data.users.0.email', 'poeta@example.com')
        ->assertJsonPath('meta.total', 1);
});

it('updates status account type verification and roles', function () {
    $admin = adminUserForManagement();
    $target = User::factory()->unverified()->create();
    Role::query()->firstOrCreate(['name' => 'moderator'], ['label' => 'Moderator']);

    Sanctum::actingAs($admin);

    $this->patchJson('/api/v1/admin/users/'.$target->id, [
        'status' => 'suspended',
        'account_type' => 'poet_organizer',
        'email_verified' => true,
        'roles' => ['moderator'],
    ])
        ->assertOk()
        ->assertJsonPath('data.user.status', 'suspended')
        ->assertJsonPath('data.user.account_type', 'poet_organizer')
        ->assertJsonPath('data.user.email_verified', true)
        ->assertJsonPath('data.user.roles.0', 'moderator');

    expect($target->fresh()->hasRole('moderator'))->toBeTrue();
});

it('prevents an administrator from locking their own account', function () {
    $admin = adminUserForManagement();

    Sanctum::actingAs($admin);

    $this->patchJson('/api/v1/admin/users/'.$admin->id, [
        'status' => 'suspended',
        'account_type' => 'audience',
        'email_verified' => true,
        'roles' => ['admin'],
    ])->assertUnprocessable();
});

it('requires the admin role', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $this->getJson('/api/v1/admin/users')->assertForbidden();
});
