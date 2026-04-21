<?php

use Domain\Admin\Models\ContentPublicationSetting;
use Domain\Shared\Enums\PublicationFlow;
use Domain\User\Models\Role;
use Domain\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

function adminModeratorBaseUser(): User
{
    return User::factory()->poetOrganizer()->create();
}

function adminUserForPublicationFlow(): User
{
    $user = adminModeratorBaseUser();
    $role = Role::query()->firstOrCreate(
        ['name' => 'admin'],
        ['label' => 'Admin']
    );
    $user->roles()->syncWithoutDetaching([$role->id]);

    return $user;
}

function poetOrganizerUser(): User
{
    return User::factory()->poetOrganizer()->create();
}

it('lists content publication settings for admin', function () {
    Sanctum::actingAs(adminUserForPublicationFlow());

    ContentPublicationSetting::query()->create([
        'content_type' => 'poem',
        'publication_flow' => PublicationFlow::AutoPublish,
        'is_active' => true,
    ]);

    $response = $this->getJson('/api/v1/admin/content-publication-settings');

    $response
        ->assertOk()
        ->assertJsonPath('data.settings.0.content_type', 'poem')
        ->assertJsonPath('data.settings.0.publication_flow', 'auto_publish');
});

it('allows admin to update the publication flow setting', function () {
    Sanctum::actingAs(adminUserForPublicationFlow());

    $setting = ContentPublicationSetting::query()->create([
        'content_type' => 'poem',
        'publication_flow' => PublicationFlow::AutoPublish,
        'is_active' => true,
    ]);

    $response = $this->patchJson("/api/v1/admin/content-publication-settings/{$setting->id}", [
        'publication_flow' => 'manual_review',
        'is_active' => true,
    ]);

    $response
        ->assertOk()
        ->assertJsonPath('data.setting.content_type', 'poem')
        ->assertJsonPath('data.setting.publication_flow', 'manual_review');

    $this->assertDatabaseHas('content_publication_settings', [
        'id' => $setting->id,
        'publication_flow' => 'manual_review',
    ]);
});

it('auto publishes a poem when the publication flow is auto_publish', function () {
    Sanctum::actingAs(poetOrganizerUser());

    ContentPublicationSetting::query()->create([
        'content_type' => 'poem',
        'publication_flow' => PublicationFlow::AutoPublish,
        'is_active' => true,
    ]);

    $response = $this->postJson('/api/v1/poems', [
        'title' => 'Auto published poem',
        'content' => 'Body',
        'status' => 'published',
        'is_public' => true,
        'language' => 'it',
    ]);

    $response
        ->assertOk()
        ->assertJsonPath('data.poem.status', 'published');

    $this->assertDatabaseHas('poems', [
        'slug' => 'auto-published-poem',
        'status' => 'published',
    ]);
});

it('moves a poem to pending_review when the publication flow is manual_review', function () {
    Sanctum::actingAs(poetOrganizerUser());

    ContentPublicationSetting::query()->create([
        'content_type' => 'poem',
        'publication_flow' => PublicationFlow::ManualReview,
        'is_active' => true,
    ]);

    $response = $this->postJson('/api/v1/poems', [
        'title' => 'Needs review poem',
        'content' => 'Body',
        'status' => 'published',
        'is_public' => true,
        'language' => 'it',
    ]);

    $response
        ->assertOk()
        ->assertJsonPath('data.poem.status', 'pending_review')
        ->assertJsonPath('data.poem.published_at', null);

    $this->assertDatabaseHas('poems', [
        'slug' => 'needs-review-poem',
        'status' => 'pending_review',
    ]);
});
