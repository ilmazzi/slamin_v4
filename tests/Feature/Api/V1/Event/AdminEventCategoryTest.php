<?php

use Domain\Event\Models\Event;
use Domain\Event\Models\EventCategory;
use Domain\User\Models\Role;
use Domain\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

function adminEventUser(): User
{
    $user = User::factory()->poetOrganizer()->create();
    $role = Role::query()->firstOrCreate(
        ['name' => 'admin'],
        ['label' => 'Admin']
    );
    $user->roles()->syncWithoutDetaching([$role->id]);

    return $user;
}

function nonAdminEventUser(): User
{
    return User::factory()->create();
}

it('allows an admin to create an event category with translations', function () {
    Sanctum::actingAs(adminEventUser());

    $response = $this->postJson('/api/v1/admin/event-categories', [
        'slug' => 'open-mic',
        'is_active' => true,
        'sort_order' => 3,
        'translations' => [
            ['locale' => 'it', 'name' => 'Open mic'],
            ['locale' => 'en', 'name' => 'Open mic'],
        ],
    ]);

    $response
        ->assertCreated()
        ->assertJsonPath('data.category.slug', 'open-mic')
        ->assertJsonCount(2, 'data.category.translations');

    $this->assertDatabaseHas('event_categories', [
        'slug' => 'open-mic',
        'is_active' => true,
        'sort_order' => 3,
    ]);
});

it('forbids a non admin from writing event categories', function () {
    Sanctum::actingAs(nonAdminEventUser());

    $response = $this->postJson('/api/v1/admin/event-categories', [
        'slug' => 'festival',
        'translations' => [
            ['locale' => 'it', 'name' => 'Festival'],
        ],
    ]);

    $response->assertForbidden();
});

it('allows an admin to update an event category', function () {
    Sanctum::actingAs(adminEventUser());

    $category = EventCategory::query()->create([
        'slug' => 'reading',
        'is_active' => true,
        'sort_order' => 1,
    ]);

    $category->translations()->createMany([
        ['locale' => 'it', 'name' => 'Reading'],
        ['locale' => 'en', 'name' => 'Reading'],
    ]);

    $response = $this->patchJson("/api/v1/admin/event-categories/{$category->id}", [
        'slug' => 'reading-updated',
        'is_active' => false,
        'sort_order' => 5,
        'translations' => [
            ['locale' => 'it', 'name' => 'Reading aggiornato'],
            ['locale' => 'fr', 'name' => 'Lecture'],
        ],
    ]);

    $response
        ->assertOk()
        ->assertJsonPath('data.category.slug', 'reading-updated')
        ->assertJsonPath('data.category.is_active', false)
        ->assertJsonCount(2, 'data.category.translations');

    $this->assertDatabaseHas('event_categories', [
        'id' => $category->id,
        'slug' => 'reading-updated',
        'is_active' => false,
        'sort_order' => 5,
    ]);
});

it('prevents deleting an event category that is already used by an event', function () {
    Sanctum::actingAs(adminEventUser());

    $owner = User::factory()->poetOrganizer()->create();
    $category = EventCategory::query()->create([
        'slug' => 'festival',
        'is_active' => true,
        'sort_order' => 1,
    ]);
    $category->translations()->create([
        'locale' => 'it',
        'name' => 'Festival',
    ]);

    Event::query()->create([
        'user_id' => $owner->id,
        'event_category_id' => $category->id,
        'title' => 'Used event',
        'slug' => 'used-event',
        'description' => 'Body',
        'schedule_mode' => 'fixed',
        'delivery_mode' => 'online',
        'language' => 'it',
        'status' => 'draft',
        'is_public' => false,
    ]);

    $response = $this->deleteJson("/api/v1/admin/event-categories/{$category->id}");

    $response
        ->assertStatus(422)
        ->assertJsonPath('errors.message', __('event.errors.category_in_use'));
});

it('protects system event category slugs and deletion', function () {
    Sanctum::actingAs(adminEventUser());

    $category = EventCategory::query()->create([
        'slug' => 'poetry-slam',
        'is_active' => true,
        'sort_order' => 1,
    ]);
    $category->translations()->create([
        'locale' => 'it',
        'name' => 'Poetry Slam',
    ]);

    $this->patchJson("/api/v1/admin/event-categories/{$category->id}", [
        'slug' => 'slam',
        'is_active' => true,
        'sort_order' => 1,
        'translations' => [
            ['locale' => 'it', 'name' => 'Poetry Slam'],
        ],
    ])->assertUnprocessable()->assertJsonPath('errors.fields.slug.0', 'The selected slug is invalid.');

    $this->deleteJson("/api/v1/admin/event-categories/{$category->id}")
        ->assertUnprocessable()
        ->assertJsonPath('errors.message', __('event.errors.system_category'));

    $this->assertDatabaseHas('event_categories', [
        'id' => $category->id,
        'slug' => 'poetry-slam',
    ]);
});
