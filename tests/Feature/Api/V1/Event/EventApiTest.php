<?php

use Domain\Event\Models\Event;
use Domain\Event\Models\EventAudienceInvite;
use Domain\Event\Models\EventParticipantInvite;
use Domain\Event\Models\EventCategory;
use Domain\Notification\Models\Notification;
use Domain\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

function eventAuthor(): User
{
    return User::factory()->poetOrganizer()->create();
}

it('creates a fixed in person event and stores the recent place', function () {
    $user = eventAuthor();
    Sanctum::actingAs($user);

    $category = EventCategory::query()->create([
        'slug' => 'reading',
        'is_active' => true,
        'sort_order' => 1,
    ]);
    $category->translations()->create([
        'locale' => 'it',
        'name' => 'Reading',
    ]);

    $response = $this->postJson('/api/v1/events', [
        'title' => 'My reading',
        'description' => 'Event body',
        'event_category_id' => $category->id,
        'schedule_mode' => 'fixed',
        'delivery_mode' => 'in_person',
        'status' => 'published',
        'is_public' => true,
        'occurrence' => [
            'starts_at' => now()->addDay()->toISOString(),
            'timezone' => 'Europe/Rome',
        ],
        'venue' => [
            'place_name' => 'Circolo culturale',
            'country_code' => 'IT',
            'city' => 'Roma',
            'postal_code' => '00100',
            'address_line' => 'Via Roma 1',
        ],
    ]);

    $response
        ->assertOk()
        ->assertJsonPath('data.event.slug', 'my-reading')
        ->assertJsonPath('data.event.schedule_mode', 'fixed')
        ->assertJsonPath('data.event.delivery_mode', 'in_person');

    $this->assertDatabaseHas('events', [
        'title' => 'My reading',
        'status' => 'published',
    ]);

    $this->assertDatabaseHas('recent_event_places', [
        'user_id' => $user->id,
        'place_name' => 'Circolo culturale',
        'city' => 'Roma',
    ]);
});

it('notifies internal participant and audience invitees when creating an event', function () {
    $organizer = eventAuthor();
    $participant = eventAuthor();
    $audience = User::factory()->create();
    Sanctum::actingAs($organizer);

    $response = $this->postJson('/api/v1/events', [
        'title' => 'Invite event',
        'description' => 'Event body',
        'schedule_mode' => 'fixed',
        'delivery_mode' => 'online',
        'status' => 'draft',
        'occurrence' => [
            'starts_at' => now()->addDay()->toISOString(),
            'timezone' => 'UTC',
        ],
        'online' => [
            'meeting_url' => 'https://example.com/meet',
            'timezone' => 'UTC',
        ],
        'participant_invites' => [
            [
                'user_id' => $participant->id,
                'role' => 'artist',
                'invite_type' => 'internal',
            ],
        ],
        'audience_invite_user_ids' => [$audience->id],
    ]);

    $response->assertOk();

    $this->assertDatabaseHas('notifications', [
        'user_id' => $participant->id,
        'type' => 'event_participant_invited',
    ]);

    $this->assertDatabaseHas('notifications', [
        'user_id' => $audience->id,
        'type' => 'event_audience_invited',
    ]);
});

it('creates an availability based online event', function () {
    Sanctum::actingAs(eventAuthor());

    $response = $this->postJson('/api/v1/events', [
        'title' => 'Online workshop',
        'description' => 'Workshop body',
        'schedule_mode' => 'availability',
        'delivery_mode' => 'online',
        'status' => 'draft',
        'availability_poll' => [
            'response_deadline' => now()->addDays(2)->toISOString(),
            'instructions' => 'Scegli almeno una opzione',
        ],
        'availability_options' => [
            [
                'starts_at' => now()->addDays(3)->toISOString(),
                'timezone' => 'UTC',
            ],
        ],
        'online' => [
            'meeting_url' => 'https://example.com/meet',
            'timezone' => 'UTC',
        ],
    ]);

    $response
        ->assertOk()
        ->assertJsonPath('data.event.schedule_mode', 'availability')
        ->assertJsonPath('data.event.delivery_mode', 'online')
        ->assertJsonPath('data.event.availability_options.0.timezone', 'UTC');
});

it('lists event categories publicly', function () {
    $category = EventCategory::query()->create([
        'slug' => 'poetry-slam',
        'is_active' => true,
        'sort_order' => 1,
    ]);
    $category->translations()->create([
        'locale' => 'it',
        'name' => 'Poetry Slam',
    ]);

    $response = $this->getJson('/api/v1/event-categories');

    $response
        ->assertOk()
        ->assertJsonCount(1, 'data.categories')
        ->assertJsonPath('data.categories.0.slug', 'poetry-slam');
});

it('shows own draft event to the owner', function () {
    $user = eventAuthor();
    Sanctum::actingAs($user);

    $event = Event::query()->create([
        'user_id' => $user->id,
        'title' => 'Private draft event',
        'slug' => 'private-draft-event',
        'description' => 'Body',
        'schedule_mode' => 'fixed',
        'delivery_mode' => 'online',
        'language' => 'it',
        'status' => 'draft',
        'is_public' => false,
    ]);

    $response = $this->getJson("/api/v1/events/{$event->slug}");

    $response
        ->assertOk()
        ->assertJsonPath('data.event.slug', 'private-draft-event')
        ->assertJsonPath('data.event.status', 'draft');
});

it('allows invited users to list and respond to event invites', function () {
    $organizer = eventAuthor();
    $participant = eventAuthor();
    $audience = User::factory()->create();

    $event = Event::query()->create([
        'user_id' => $organizer->id,
        'title' => 'Response event',
        'slug' => 'response-event',
        'description' => 'Body',
        'schedule_mode' => 'fixed',
        'delivery_mode' => 'online',
        'language' => 'it',
        'status' => 'published',
        'is_public' => true,
        'published_at' => now(),
    ]);

    $participantInvite = EventParticipantInvite::query()->create([
        'event_id' => $event->id,
        'user_id' => $participant->id,
        'role' => 'artist',
        'invite_type' => 'internal',
        'status' => 'pending',
    ]);

    $audienceInvite = EventAudienceInvite::query()->create([
        'event_id' => $event->id,
        'user_id' => $audience->id,
        'status' => 'pending',
    ]);

    Sanctum::actingAs($participant);

    $this->getJson('/api/v1/my/event-participant-invites')
        ->assertOk()
        ->assertJsonCount(1, 'data.invites')
        ->assertJsonPath('data.invites.0.id', $participantInvite->id);

    $this->patchJson("/api/v1/event-participant-invites/{$participantInvite->id}/respond", [
        'status' => 'accepted',
    ])
        ->assertOk()
        ->assertJsonPath('data.invite.status', 'accepted');

    Sanctum::actingAs($audience);

    $this->getJson('/api/v1/my/event-audience-invites')
        ->assertOk()
        ->assertJsonCount(1, 'data.invites')
        ->assertJsonPath('data.invites.0.id', $audienceInvite->id);

    $this->patchJson("/api/v1/event-audience-invites/{$audienceInvite->id}/respond", [
        'status' => 'declined',
    ])
        ->assertOk()
        ->assertJsonPath('data.invite.status', 'declined');

    $this->assertDatabaseHas('notifications', [
        'user_id' => $organizer->id,
        'type' => 'event_participant_invite_response',
    ]);

    $this->assertDatabaseHas('notifications', [
        'user_id' => $organizer->id,
        'type' => 'event_audience_invite_response',
    ]);
});
