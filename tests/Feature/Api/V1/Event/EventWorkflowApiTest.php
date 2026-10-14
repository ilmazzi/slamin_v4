<?php

use App\Http\Resources\Api\V1\Event\EventSummaryResource;
use App\Mail\EventExternalAudienceInviteMail;
use App\Mail\EventExternalParticipantInviteMail;
use Domain\Engagement\Models\Bookmark;
use Domain\Engagement\Models\Like;
use Domain\Event\DTOs\ListEventData;
use Domain\Event\Models\Event;
use Domain\Event\Models\EventAudienceInvite;
use Domain\Event\Models\EventAvailabilityOption;
use Domain\Event\Models\EventCategory;
use Domain\Event\Models\EventParticipantInvite;
use Domain\Event\Queries\ListEventQuery;
use Domain\User\Models\Role;
use Domain\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

function eventWorkflowOrganizer(): User
{
    return User::factory()->poetOrganizer()->create([
        'email_verified_at' => now(),
    ]);
}

function eventWorkflowUser(): User
{
    return User::factory()->create([
        'email_verified_at' => now(),
    ]);
}

function eventWorkflowAdmin(): User
{
    $user = User::factory()->poetOrganizer()->create([
        'email_verified_at' => now(),
    ]);
    $role = Role::query()->firstOrCreate(
        ['name' => 'admin'],
        ['label' => 'Admin']
    );
    $user->roles()->syncWithoutDetaching([$role->id]);

    return $user;
}

function eventWorkflowCategory(string $slug = 'open-mic', bool $active = true): EventCategory
{
    $category = EventCategory::query()->create([
        'slug' => $slug,
        'is_active' => $active,
        'sort_order' => 1,
    ]);

    $category->translations()->create([
        'locale' => 'it',
        'name' => ucfirst(str_replace('-', ' ', $slug)),
    ]);

    return $category;
}

function availabilityEvent(User $owner, EventCategory $category): Event
{
    /** @var Event $event */
    $event = Event::query()->create([
        'user_id' => $owner->id,
        'event_category_id' => $category->id,
        'title' => 'Evento availability',
        'slug' => 'evento-availability',
        'description' => 'Evento con sondaggio date',
        'schedule_mode' => 'availability',
        'delivery_mode' => 'online',
        'status' => 'draft',
        'is_public' => true,
    ]);

    $event->availabilityPoll()->create([
        'response_deadline' => now()->addDay(),
        'instructions' => 'Vota la data migliore',
    ]);

    $event->availabilityOptions()->createMany([
        [
            'starts_at' => now()->addDays(3),
            'ends_at' => now()->addDays(3)->addHours(2),
            'timezone' => 'Europe/Rome',
        ],
        [
            'starts_at' => now()->addDays(4),
            'ends_at' => now()->addDays(4)->addHours(2),
            'timezone' => 'Europe/Rome',
        ],
    ]);

    return $event->fresh(['availabilityOptions']);
}

it('lists only active event categories', function () {
    eventWorkflowCategory('festival', true);
    eventWorkflowCategory('archived', false);

    $response = $this->getJson('/api/v1/event-categories');

    $response
        ->assertOk()
        ->assertJsonCount(1, 'data.categories')
        ->assertJsonPath('data.categories.0.slug', 'festival');
});

it('lists only published public events and supports owner-only draft view on show', function () {
    $owner = eventWorkflowOrganizer();
    $other = eventWorkflowOrganizer();
    $category = eventWorkflowCategory();

    Event::query()->create([
        'user_id' => $owner->id,
        'event_category_id' => $category->id,
        'title' => 'Pubblico',
        'slug' => 'evento-pubblico',
        'description' => 'ok',
        'schedule_mode' => 'fixed',
        'delivery_mode' => 'online',
        'status' => 'published',
        'is_public' => true,
        'published_at' => now(),
    ]);

    Event::query()->create([
        'user_id' => $owner->id,
        'event_category_id' => $category->id,
        'title' => 'Privato',
        'slug' => 'evento-privato',
        'description' => 'draft',
        'schedule_mode' => 'fixed',
        'delivery_mode' => 'online',
        'status' => 'draft',
        'is_public' => false,
    ]);

    $this->getJson('/api/v1/events')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.slug', 'evento-pubblico');

    // Non-owner cannot read draft/private event by slug.
    Sanctum::actingAs($other);
    $this->getJson('/api/v1/events/evento-privato')->assertNotFound();

    // Owner can read own draft/private event by slug.
    Sanctum::actingAs($owner);
    $this->getJson('/api/v1/events/evento-privato')
        ->assertOk()
        ->assertJsonPath('data.event.slug', 'evento-privato')
        ->assertJsonPath('data.event.is_owner', true);
});

it('uses a bounded query count and does not expose invitations in the public event list', function () {
    $owner = eventWorkflowOrganizer();
    $category = eventWorkflowCategory();

    foreach (range(1, 5) as $index) {
        $event = Event::query()->create([
            'user_id' => $owner->id,
            'event_category_id' => $category->id,
            'title' => "Evento {$index}",
            'slug' => "evento-{$index}",
            'description' => 'Pubblico',
            'schedule_mode' => 'fixed',
            'delivery_mode' => 'online',
            'status' => 'published',
            'is_public' => true,
            'published_at' => now(),
        ]);

        $event->participantInvites()->create([
            'invite_type' => 'external',
            'name' => 'Private guest',
            'email' => "private-{$index}@example.test",
            'role' => 'artist',
            'status' => 'pending',
        ]);
    }

    $queryCount = 0;
    DB::listen(function () use (&$queryCount): void {
        $queryCount++;
    });

    $events = app(ListEventQuery::class)->execute(new ListEventData(10, null, null));
    EventSummaryResource::collection($events->items())->resolve();

    expect($queryCount)->toBeLessThanOrEqual(6);

    $this->getJson('/api/v1/events')
        ->assertOk()
        ->assertJsonMissingPath('data.0.participant_invites')
        ->assertJsonMissing(['email' => 'private-1@example.test']);

    $this->getJson('/api/v1/events/evento-1')
        ->assertOk()
        ->assertJsonMissingPath('data.event.participant_invites')
        ->assertJsonMissingPath('data.event.audience_invites')
        ->assertJsonMissing(['email' => 'private-1@example.test']);
});

it('allows invited users to vote and unvote availability options', function () {
    $owner = eventWorkflowOrganizer();
    $invitee = eventWorkflowUser();
    $category = eventWorkflowCategory();
    $event = availabilityEvent($owner, $category);
    $option = $event->availabilityOptions->first();

    EventParticipantInvite::query()->create([
        'event_id' => $event->id,
        'user_id' => $invitee->id,
        'role' => 'performer',
        'invite_type' => 'internal',
        'status' => 'pending',
    ]);

    Sanctum::actingAs($invitee);

    $this->postJson("/api/v1/events/{$event->id}/availability-options/{$option->id}/vote")
        ->assertOk()
        ->assertJsonPath('data.event.availability_options.0.votes_count', 1);

    $this->deleteJson("/api/v1/events/{$event->id}/availability-options/{$option->id}/vote")
        ->assertOk()
        ->assertJsonPath('data.event.availability_options.0.votes_count', 0);
});

it('forbids availability voting for users not involved in the event', function () {
    $owner = eventWorkflowOrganizer();
    $outsider = eventWorkflowUser();
    $category = eventWorkflowCategory();
    $event = availabilityEvent($owner, $category);
    $option = $event->availabilityOptions->first();

    Sanctum::actingAs($outsider);

    $this->postJson("/api/v1/events/{$event->id}/availability-options/{$option->id}/vote")
        ->assertForbidden();
});

it('finalizes availability option and notifies invitees', function () {
    $owner = eventWorkflowOrganizer();
    $participant = eventWorkflowUser();
    $audience = eventWorkflowUser();
    $category = eventWorkflowCategory();
    $event = availabilityEvent($owner, $category);
    $option = $event->availabilityOptions->first();

    EventParticipantInvite::query()->create([
        'event_id' => $event->id,
        'user_id' => $participant->id,
        'role' => 'performer',
        'invite_type' => 'internal',
        'status' => 'accepted',
    ]);

    EventAudienceInvite::query()->create([
        'event_id' => $event->id,
        'user_id' => $audience->id,
        'status' => 'accepted',
    ]);

    Sanctum::actingAs($owner);

    $response = $this->postJson("/api/v1/events/{$event->id}/availability-options/{$option->id}/finalize");

    $response
        ->assertOk();

    $this->assertDatabaseHas('event_availability_options', [
        'id' => $option->id,
    ]);
    expect(EventAvailabilityOption::query()->findOrFail($option->id)->finalized_at)->not->toBeNull();

    $this->assertDatabaseHas('event_occurrences', [
        'event_id' => $event->id,
        'timezone' => 'Europe/Rome',
    ]);

    $this->assertDatabaseHas('notifications', [
        'user_id' => $participant->id,
        'type' => 'event_availability_finalized',
    ]);
    $this->assertDatabaseHas('notifications', [
        'user_id' => $audience->id,
        'type' => 'event_availability_finalized',
    ]);
});

it('allows invited users to respond and blocks non owners of invite', function () {
    $owner = eventWorkflowOrganizer();
    $invited = eventWorkflowUser();
    $outsider = eventWorkflowUser();
    $category = eventWorkflowCategory();

    /** @var Event $event */
    $event = Event::query()->create([
        'user_id' => $owner->id,
        'event_category_id' => $category->id,
        'title' => 'Invites event',
        'slug' => 'invites-event',
        'description' => 'invites',
        'schedule_mode' => 'fixed',
        'delivery_mode' => 'online',
        'status' => 'draft',
        'is_public' => true,
    ]);

    $participantInvite = EventParticipantInvite::query()->create([
        'event_id' => $event->id,
        'user_id' => $invited->id,
        'role' => 'performer',
        'invite_type' => 'internal',
        'status' => 'pending',
    ]);

    $audienceInvite = EventAudienceInvite::query()->create([
        'event_id' => $event->id,
        'user_id' => $invited->id,
        'status' => 'pending',
    ]);

    Sanctum::actingAs($outsider);
    $this->patchJson("/api/v1/event-participant-invites/{$participantInvite->id}/respond", ['status' => 'accepted'])
        ->assertForbidden();
    $this->patchJson("/api/v1/event-audience-invites/{$audienceInvite->id}/respond", ['status' => 'accepted'])
        ->assertForbidden();

    Sanctum::actingAs($invited);
    $this->patchJson("/api/v1/event-participant-invites/{$participantInvite->id}/respond", ['status' => 'accepted'])
        ->assertOk()
        ->assertJsonPath('data.invite.status', 'accepted');
    $this->patchJson("/api/v1/event-audience-invites/{$audienceInvite->id}/respond", ['status' => 'declined'])
        ->assertOk()
        ->assertJsonPath('data.invite.status', 'declined');
});

it('stores and lists recent places from in person events', function () {
    $owner = eventWorkflowOrganizer();
    $category = eventWorkflowCategory();
    Sanctum::actingAs($owner);

    $this->postJson('/api/v1/events', [
        'title' => 'Evento Milano',
        'description' => 'Con venue',
        'event_category_id' => $category->id,
        'schedule_mode' => 'fixed',
        'delivery_mode' => 'in_person',
        'status' => 'draft',
        'is_public' => true,
        'occurrence' => [
            'starts_at' => now()->addDay()->toISOString(),
            'timezone' => 'Europe/Rome',
        ],
        'venue' => [
            'place_name' => 'Teatro Test',
            'country_code' => 'it',
            'city' => 'Milano',
        ],
    ])->assertOk();

    $this->getJson('/api/v1/events/recent-places')
        ->assertOk()
        ->assertJsonPath('data.places.0.place_name', 'Teatro Test')
        ->assertJsonPath('data.places.0.city', 'Milano');
});

it('sends external participant invite mail and marks invite as sent', function () {
    Mail::fake();

    $owner = eventWorkflowOrganizer();
    $category = eventWorkflowCategory();
    Sanctum::actingAs($owner);

    $this->postJson('/api/v1/events', [
        'title' => 'Evento con invite esterno',
        'description' => 'Descrizione',
        'event_category_id' => $category->id,
        'schedule_mode' => 'fixed',
        'delivery_mode' => 'online',
        'status' => 'draft',
        'is_public' => true,
        'occurrence' => [
            'starts_at' => now()->addDay()->toISOString(),
            'timezone' => 'Europe/Rome',
        ],
        'online' => [
            'meeting_url' => 'https://meet.example.com/ext',
            'timezone' => 'Europe/Rome',
        ],
        'participant_invites' => [
            [
                'invite_type' => 'external',
                'email' => 'guest@example.com',
                'name' => 'Guest Poet',
                'role' => 'performer',
                'status' => 'pending',
            ],
        ],
    ])->assertOk();

    Mail::assertSent(EventExternalParticipantInviteMail::class);

    $this->assertDatabaseHas('event_participant_invites', [
        'email' => 'guest@example.com',
        'invite_type' => 'external',
        'status' => 'sent',
    ]);
});

it('sends external audience invite mail and marks invite as sent', function () {
    Mail::fake();

    $owner = eventWorkflowOrganizer();
    $category = eventWorkflowCategory();
    Sanctum::actingAs($owner);

    $this->postJson('/api/v1/events', [
        'title' => 'Evento con pubblico esterno',
        'description' => 'Descrizione',
        'event_category_id' => $category->id,
        'schedule_mode' => 'fixed',
        'delivery_mode' => 'online',
        'status' => 'draft',
        'is_public' => true,
        'occurrence' => [
            'starts_at' => now()->addDay()->toISOString(),
            'timezone' => 'Europe/Rome',
        ],
        'online' => [
            'meeting_url' => 'https://meet.example.com/audience',
            'timezone' => 'Europe/Rome',
        ],
        'audience_invites' => [
            [
                'invite_type' => 'external',
                'email' => 'audience@example.com',
                'name' => 'Guest Audience',
                'status' => 'pending',
            ],
        ],
    ])->assertOk();

    Mail::assertSent(EventExternalAudienceInviteMail::class);
    $this->assertDatabaseHas('event_audience_invites', [
        'email' => 'audience@example.com',
        'invite_type' => 'external',
        'status' => 'sent',
    ]);
});

it('searches verified platform users for event invitations without exposing email', function () {
    $owner = eventWorkflowOrganizer();
    $matching = eventWorkflowUser();
    $matching->profile()->update([
        'display_name' => 'Ada Test',
        'nickname' => 'ada-test',
        'is_public' => true,
    ]);

    Sanctum::actingAs($owner);

    $this->getJson('/api/v1/events/invite-users?q=Ada')
        ->assertOk()
        ->assertJsonPath('data.0.id', $matching->id)
        ->assertJsonPath('data.0.display_name', 'Ada Test')
        ->assertJsonMissing(['email' => $matching->email]);
});

it('lists my events for the authenticated organizer only', function () {
    $owner = eventWorkflowOrganizer();
    $other = eventWorkflowOrganizer();
    $category = eventWorkflowCategory();

    Event::query()->create([
        'user_id' => $owner->id,
        'event_category_id' => $category->id,
        'title' => 'Owner event',
        'slug' => 'owner-event',
        'description' => 'mine',
        'schedule_mode' => 'fixed',
        'delivery_mode' => 'online',
        'status' => 'draft',
        'is_public' => true,
    ]);

    Event::query()->create([
        'user_id' => $other->id,
        'event_category_id' => $category->id,
        'title' => 'Other event',
        'slug' => 'other-event',
        'description' => 'not mine',
        'schedule_mode' => 'fixed',
        'delivery_mode' => 'online',
        'status' => 'draft',
        'is_public' => true,
    ]);

    Sanctum::actingAs($owner);
    $this->getJson('/api/v1/my/events')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.slug', 'owner-event');
});

it('lists my participant and audience invites', function () {
    $owner = eventWorkflowOrganizer();
    $invited = eventWorkflowUser();
    $category = eventWorkflowCategory();

    /** @var Event $event */
    $event = Event::query()->create([
        'user_id' => $owner->id,
        'event_category_id' => $category->id,
        'title' => 'Invites list',
        'slug' => 'invites-list',
        'description' => 'invites',
        'schedule_mode' => 'fixed',
        'delivery_mode' => 'online',
        'status' => 'draft',
        'is_public' => true,
    ]);

    EventParticipantInvite::query()->create([
        'event_id' => $event->id,
        'user_id' => $invited->id,
        'role' => 'judge',
        'invite_type' => 'internal',
        'status' => 'pending',
    ]);
    EventAudienceInvite::query()->create([
        'event_id' => $event->id,
        'user_id' => $invited->id,
        'status' => 'pending',
    ]);

    Sanctum::actingAs($invited);
    $this->getJson('/api/v1/my/event-participant-invites')
        ->assertOk()
        ->assertJsonCount(1, 'data.invites')
        ->assertJsonPath('data.invites.0.role', 'judge');
    $this->getJson('/api/v1/my/event-audience-invites')
        ->assertOk()
        ->assertJsonCount(1, 'data.invites')
        ->assertJsonPath('data.invites.0.status', 'pending');
});

it('supports admin event category list, show and delete when unused', function () {
    $admin = eventWorkflowAdmin();
    Sanctum::actingAs($admin);

    $category = eventWorkflowCategory('spoken-word');

    $this->getJson('/api/v1/admin/event-categories')
        ->assertOk()
        ->assertJsonPath('data.categories.0.slug', 'spoken-word');

    $this->getJson("/api/v1/admin/event-categories/{$category->id}")
        ->assertOk()
        ->assertJsonPath('data.category.slug', 'spoken-word');

    $this->deleteJson("/api/v1/admin/event-categories/{$category->id}")
        ->assertOk()
        ->assertJsonPath('data.deleted', true);
});

it('prevents deleting a festival event that has child events', function () {
    $owner = eventWorkflowOrganizer();
    Sanctum::actingAs($owner);

    $festivalCategory = eventWorkflowCategory('festival');
    $childCategory = eventWorkflowCategory('open-mic');

    /** @var Event $festival */
    $festival = Event::query()->create([
        'user_id' => $owner->id,
        'event_category_id' => $festivalCategory->id,
        'title' => 'Festival Madre',
        'slug' => 'festival-madre',
        'description' => 'evento padre',
        'schedule_mode' => 'fixed',
        'delivery_mode' => 'online',
        'status' => 'draft',
        'is_public' => true,
    ]);

    Event::query()->create([
        'user_id' => $owner->id,
        'event_category_id' => $childCategory->id,
        'festival_event_id' => $festival->id,
        'title' => 'Evento figlio',
        'slug' => 'evento-figlio',
        'description' => 'figlio',
        'schedule_mode' => 'fixed',
        'delivery_mode' => 'online',
        'status' => 'draft',
        'is_public' => true,
    ]);

    $this->deleteJson("/api/v1/events/{$festival->id}")
        ->assertStatus(422)
        ->assertJsonPath('errors.fields.event.0', __('event.validation.festival_with_children_cannot_be_deleted'));
});

it('lets a verified audience user like, bookmark and list a published event in my events', function () {
    $owner = eventWorkflowOrganizer();
    $reader = eventWorkflowUser();
    $category = eventWorkflowCategory();

    /** @var Event $event */
    $event = Event::query()->create([
        'user_id' => $owner->id,
        'event_category_id' => $category->id,
        'title' => 'Evento pubblico',
        'slug' => 'evento-pubblico-bookmark',
        'description' => 'Salvalo in calendario',
        'schedule_mode' => 'fixed',
        'delivery_mode' => 'online',
        'status' => 'published',
        'is_public' => true,
        'published_at' => now(),
    ]);

    $event->occurrences()->create([
        'starts_at' => now()->addDays(3),
        'timezone' => 'Europe/Rome',
        'is_primary' => true,
    ]);

    Sanctum::actingAs($reader);

    $this->postJson("/api/v1/events/{$event->id}/like")
        ->assertOk()
        ->assertJsonPath('data.event.id', $event->id)
        ->assertJsonPath('data.event.is_liked', true);

    $this->assertDatabaseHas('likes', [
        'user_id' => $reader->id,
        'likeable_id' => $event->id,
        'likeable_type' => $event->getMorphClass(),
    ]);

    $this->deleteJson("/api/v1/events/{$event->id}/like")->assertOk();

    expect(Like::query()
        ->where('user_id', $reader->id)
        ->where('likeable_id', $event->id)
        ->exists())->toBeFalse();

    $this->postJson("/api/v1/events/{$event->id}/bookmark")
        ->assertOk()
        ->assertJsonPath('data.event.is_bookmarked', true);

    $this->assertDatabaseHas('bookmarks', [
        'user_id' => $reader->id,
        'bookmarkable_id' => $event->id,
        'bookmarkable_type' => $event->getMorphClass(),
    ]);

    $this->getJson('/api/v1/my/events')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.slug', 'evento-pubblico-bookmark')
        ->assertJsonPath('data.0.is_bookmarked', true)
        ->assertJsonPath('data.0.is_owner', false);

    $this->deleteJson("/api/v1/events/{$event->id}/bookmark")->assertOk();

    expect(Bookmark::query()
        ->where('user_id', $reader->id)
        ->where('bookmarkable_id', $event->id)
        ->exists())->toBeFalse();
});

it('forbids liking and bookmarking unpublished events for other users', function () {
    $owner = eventWorkflowOrganizer();
    $reader = eventWorkflowUser();
    $category = eventWorkflowCategory();

    /** @var Event $event */
    $event = Event::query()->create([
        'user_id' => $owner->id,
        'event_category_id' => $category->id,
        'title' => 'Bozza nascosta',
        'slug' => 'bozza-nascosta',
        'description' => 'non pubblica',
        'schedule_mode' => 'fixed',
        'delivery_mode' => 'online',
        'status' => 'draft',
        'is_public' => true,
    ]);

    Sanctum::actingAs($reader);
    $this->postJson("/api/v1/events/{$event->id}/like")->assertForbidden();
    $this->postJson("/api/v1/events/{$event->id}/bookmark")->assertForbidden();
});
