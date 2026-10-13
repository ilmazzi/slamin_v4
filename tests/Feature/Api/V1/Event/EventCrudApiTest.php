<?php

use Domain\Engagement\Models\Bookmark;
use Domain\Event\Models\Event;
use Domain\Event\Models\EventCategory;
use Domain\Gig\Models\GigApplication;
use Domain\Notification\Models\Notification;
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

it('notifies bookmarkers, participants and gig applicants when the owner updates an event', function () {
    $owner = eventCrudAuthor();
    $bookmarker = User::factory()->create(['email_verified_at' => now()]);
    $participant = User::factory()->create(['email_verified_at' => now()]);
    $declined = User::factory()->create(['email_verified_at' => now()]);
    $gigApplicant = User::factory()->poetOrganizer()->create(['email_verified_at' => now()]);
    $rejectedApplicant = User::factory()->poetOrganizer()->create(['email_verified_at' => now()]);
    $outsider = User::factory()->create(['email_verified_at' => now()]);
    Sanctum::actingAs($owner);
    $category = eventCrudCategory();

    /** @var Event $event */
    $event = Event::query()->create([
        'user_id' => $owner->id,
        'event_category_id' => $category->id,
        'title' => 'Evento da aggiornare',
        'slug' => 'evento-da-aggiornare',
        'description' => 'Descrizione originale',
        'schedule_mode' => 'fixed',
        'delivery_mode' => 'online',
        'status' => 'draft',
        'is_public' => true,
    ]);

    Bookmark::query()->create([
        'user_id' => $bookmarker->id,
        'bookmarkable_type' => $event->getMorphClass(),
        'bookmarkable_id' => $event->id,
    ]);

    $event->participantInvites()->create([
        'user_id' => $participant->id,
        'role' => 'artist',
        'invite_type' => 'internal',
        'status' => 'accepted',
    ]);
    $event->participantInvites()->create([
        'user_id' => $declined->id,
        'role' => 'artist',
        'invite_type' => 'internal',
        'status' => 'declined',
    ]);

    $gig = $event->gigs()->create([
        'type' => 'poet_artist',
        'quantity' => 1,
        'filled_quantity' => 0,
        'status' => 'open',
        'opened_at' => now(),
        'created_by_user_id' => $owner->id,
        'updated_by_user_id' => $owner->id,
    ]);

    GigApplication::query()->create([
        'gig_id' => $gig->id,
        'user_id' => $gigApplicant->id,
        'status' => 'pending',
    ]);
    GigApplication::query()->create([
        'gig_id' => $gig->id,
        'user_id' => $rejectedApplicant->id,
        'status' => 'rejected',
    ]);

    $response = $this->patchJson("/api/v1/events/{$event->id}", [
        ...eventCrudPayload($category->id),
        'title' => 'Evento aggiornato',
        'participant_invites' => [
            [
                'invite_type' => 'internal',
                'role' => 'artist',
                'user_id' => $participant->id,
                'status' => 'accepted',
            ],
        ],
    ]);

    $response
        ->assertOk()
        ->assertJsonPath('data.event.title', 'Evento aggiornato');

    $notifiedIds = Notification::query()
        ->where('type', 'event_updated')
        ->pluck('user_id')
        ->map(fn ($id) => (int) $id)
        ->sort()
        ->values()
        ->all();

    expect($notifiedIds)->toEqualCanonicalizing([
        (int) $bookmarker->id,
        (int) $participant->id,
        (int) $gigApplicant->id,
    ]);

    expect(Notification::query()->where('user_id', $owner->id)->where('type', 'event_updated')->exists())->toBeFalse();
    expect(Notification::query()->where('user_id', $declined->id)->where('type', 'event_updated')->exists())->toBeFalse();
    expect(Notification::query()->where('user_id', $rejectedApplicant->id)->where('type', 'event_updated')->exists())->toBeFalse();
    expect(Notification::query()->where('user_id', $outsider->id)->where('type', 'event_updated')->exists())->toBeFalse();
});

it('forbids another user from updating an event', function () {
    $owner = eventCrudAuthor();
    $other = eventCrudAuthor();
    Sanctum::actingAs($other);
    $category = eventCrudCategory();

    /** @var Event $event */
    $event = Event::query()->create([
        'user_id' => $owner->id,
        'event_category_id' => $category->id,
        'title' => 'Evento di un altro',
        'slug' => 'evento-di-un-altro',
        'description' => 'Non tuo',
        'schedule_mode' => 'fixed',
        'delivery_mode' => 'online',
        'status' => 'draft',
        'is_public' => true,
    ]);

    $this->patchJson("/api/v1/events/{$event->id}", [
        ...eventCrudPayload($category->id),
        'title' => 'Titolo rubato',
    ])->assertForbidden();
});
