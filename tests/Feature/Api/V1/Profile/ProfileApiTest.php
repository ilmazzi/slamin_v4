<?php

use Domain\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

function profileTestUser(array $overrides = []): User
{
    return User::factory()->create(array_merge([
        'email_verified_at' => now(),
    ], $overrides));
}

it('updates the authenticated profile including bio visibility and avatar', function () {
    Storage::fake('public');
    $user = profileTestUser();
    Sanctum::actingAs($user);

    $response = $this->patch('/api/v1/me/profile', [
        'display_name' => 'Davide Mazzi',
        'bio' => 'Poeta e organizzatore.',
        'location' => 'Trento',
        'website_url' => 'https://slamin.test',
        'is_public' => true,
        'visibility' => [
            'bio' => true,
            'location' => false,
            'website_url' => true,
            'email' => false,
        ],
        'avatar' => UploadedFile::fake()->image('avatar.jpg', 80, 80),
    ]);

    $response
        ->assertOk()
        ->assertJsonPath('data.profile.display_name', 'Davide Mazzi')
        ->assertJsonPath('data.profile.bio', 'Poeta e organizzatore.')
        ->assertJsonPath('data.profile.location', 'Trento')
        ->assertJsonPath('data.profile.visibility.location', false)
        ->assertJsonPath('data.profile.is_owner', true);

    expect($response->json('data.profile.avatar_url'))->not->toBeNull();
    expect($user->fresh()->profile->avatar_path)->not->toBeNull();
});

it('updates and removes the dashboard banner image', function () {
    Storage::fake('public');
    $user = profileTestUser();
    Sanctum::actingAs($user);

    $this->patch('/api/v1/me/profile', [
        'banner' => UploadedFile::fake()->image('banner.jpg', 1200, 400),
    ])
        ->assertOk();

    expect($user->fresh()->profile->banner_path)->not->toBeNull();

    $this->patchJson('/api/v1/me/profile', [
        'remove_banner' => true,
    ])
        ->assertOk()
        ->assertJsonPath('data.profile.banner_url', null);

    expect($user->fresh()->profile->banner_path)->toBeNull();
});

it('keeps the same nickname when updating other profile fields', function () {
    $user = profileTestUser();
    Sanctum::actingAs($user);
    $nickname = $user->profile->nickname;

    $this->patchJson('/api/v1/me/profile', [
        'nickname' => $nickname,
        'bio' => 'Bio aggiornata',
    ])
        ->assertOk()
        ->assertJsonPath('data.profile.nickname', $nickname)
        ->assertJsonPath('data.profile.bio', 'Bio aggiornata');
});

it('hides private fields on the public profile and 404s private profiles', function () {
    $owner = profileTestUser();
    $viewer = profileTestUser();
    $profile = $owner->profile;
    $profile->update([
        'nickname' => 'mazzi_public',
        'display_name' => 'Davide',
        'bio' => 'Testo bio',
        'location' => 'Trento',
        'website_url' => 'https://example.com',
        'is_public' => true,
        'visibility' => [
            'bio' => true,
            'location' => false,
            'website_url' => true,
            'email' => false,
        ],
    ]);

    Sanctum::actingAs($viewer);

    $this->getJson('/api/v1/profiles/mazzi_public')
        ->assertOk()
        ->assertJsonPath('data.profile.display_name', 'Davide')
        ->assertJsonPath('data.profile.bio', 'Testo bio')
        ->assertJsonPath('data.profile.website_url', 'https://example.com')
        ->assertJsonPath('data.profile.location', null)
        ->assertJsonPath('data.profile.email', null)
        ->assertJsonPath('data.profile.is_owner', false);

    $profile->update(['is_public' => false]);

    $this->getJson('/api/v1/profiles/mazzi_public')->assertNotFound();

    Sanctum::actingAs($owner);
    $this->getJson('/api/v1/profiles/mazzi_public')
        ->assertOk()
        ->assertJsonPath('data.profile.is_owner', true)
        ->assertJsonPath('data.profile.location', 'Trento')
        ->assertJsonPath('data.profile.email', $owner->email);
});
