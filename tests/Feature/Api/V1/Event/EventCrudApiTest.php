<?php

use Domain\Event\Models\Event;
use Domain\Event\Models\EventCategory;
use Domain\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

function eventCrudAuthor(): User
{
    return User::factory()->poetOrganizer()->create([
        'email_verified_at' => now(),
    ]);
}

function eventCrudCategory(string $slug = 'open-mic'): EventCategory
{
    $category = EventCategory::query()->create([
        'slug' => $slug,
        'is_active' => true,
        'sort_order' => 1,
    ]);

    $category->translations()->create([
        'locale' => 'it',
        'name' => ucfirst(str_replace('-', ' ', $slug)),
    ]);

    return $category;
}

function eventCrudPayload(int $eventCategoryId): array
{
    return [
        'title' => 'Evento Test',
        'description' => 'Descrizione evento di test',
        'event_category_id' => $eventCategoryId,
        'schedule_mode' => 'fixed',
        'delivery_mode' => 'online',
        'status' => 'draft',
        'is_public' => true,
        'occurrence' => [
            'starts_at' => now()->addDay()->toISOString(),
            'timezone' => 'Europe/Rome',
        ],
        'online' => [
            'meeting_url' => 'https://meet.example.com/room-test',
            'timezone' => 'Europe/Rome',
        ],
    ];
}

it('stores an event with fixed online schedule', function () {
    $user = eventCrudAuthor();
    Sanctum::actingAs($user);
    $category = eventCrudCategory();

    $response = $this->postJson('/api/v1/events', eventCrudPayload($category->id));

    $response
        ->assertOk()
        ->assertJsonPath('data.event.title', 'Evento Test')
        ->assertJsonPath('data.event.delivery_mode', 'online')
        ->assertJsonPath('data.event.online.meeting_url', 'https://meet.example.com/room-test')
        ->assertJsonPath('errors', null);
});

it('updates an event without venue data', function () {
    $user = eventCrudAuthor();
    Sanctum::actingAs($user);
    $category = eventCrudCategory();

    /** @var Event $event */
    $event = Event::query()->create([
        'user_id' => $user->id,
        'event_category_id' => $category->id,
        'title' => 'Vecchio titolo',
        'slug' => 'vecchio-titolo',
        'description' => 'Vecchia descrizione',
        'schedule_mode' => 'fixed',
        'delivery_mode' => 'online',
        'status' => 'draft',
        'is_public' => true,
    ]);

    $response = $this->patchJson("/api/v1/events/{$event->id}", [
        ...eventCrudPayload($category->id),
        'title' => 'Nuovo titolo',
    ]);

    $response
        ->assertOk()
        ->assertJsonPath('data.event.title', 'Nuovo titolo')
        ->assertJsonPath('data.event.venue', null)
        ->assertJsonPath('data.event.online.meeting_url', 'https://meet.example.com/room-test');
});

it('accepts string boolean for remove_cover_image in form payload', function () {
    Storage::fake('public');

    $user = eventCrudAuthor();
    Sanctum::actingAs($user);
    $category = eventCrudCategory();

    $oldCoverPath = UploadedFile::fake()->image('old-cover.jpg')->store('events', 'public');

    /** @var Event $event */
    $event = Event::query()->create([
        'user_id' => $user->id,
        'event_category_id' => $category->id,
        'title' => 'Evento con cover',
        'slug' => 'evento-con-cover',
        'description' => 'Descrizione evento con immagine',
        'schedule_mode' => 'fixed',
        'delivery_mode' => 'online',
        'cover_image_path' => $oldCoverPath,
        'status' => 'draft',
        'is_public' => true,
    ]);

    $response = $this->patch("/api/v1/events/{$event->id}", [
        ...eventCrudPayload($category->id),
        'title' => 'Evento senza cover',
        'remove_cover_image' => 'true',
    ]);

    $response->assertOk();

    expect($event->fresh()->cover_image_path)->toBeNull();
    expect(Storage::disk('public')->exists($oldCoverPath))->toBeFalse();
});

it('returns validation error when participant_invites is not an array', function () {
    $user = eventCrudAuthor();
    Sanctum::actingAs($user);
    $category = eventCrudCategory();

    $response = $this->postJson('/api/v1/events', [
        ...eventCrudPayload($category->id),
        'participant_invites' => 'not-an-array',
    ]);

    $response
        ->assertStatus(422)
        ->assertJsonPath('errors.fields.participant_invites.0', __('validation.array', ['attribute' => 'participant invites']));
});
