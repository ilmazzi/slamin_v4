<?php

use Domain\Event\Models\Event;
use Domain\Event\Models\EventCategory;
use Domain\Gig\Models\GigAnnouncement;
use Domain\Gig\Models\GigApplication;
use Domain\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

function gigTestAuthor(): User
{
    return User::factory()->poetOrganizer()->create([
        'email_verified_at' => now(),
    ]);
}

function gigCategory(): EventCategory
{
    $category = EventCategory::query()->create([
        'slug' => 'showcase',
        'is_active' => true,
        'sort_order' => 10,
    ]);

    $category->translations()->create([
        'locale' => 'it',
        'name' => 'Showcase',
    ]);

    return $category;
}

function makeGigEvent(User $user, EventCategory $category, array $overrides = []): Event
{
    return Event::query()->create(array_merge([
        'user_id' => $user->id,
        'event_category_id' => $category->id,
        'title' => 'Evento Gig Test',
        'slug' => 'evento-gig-test',
        'description' => 'Evento per test gig.',
        'schedule_mode' => 'fixed',
        'delivery_mode' => 'in_person',
        'status' => 'published',
        'is_public' => true,
        'published_at' => now(),
    ], $overrides));
}

it('lists event gigs for the organizer', function () {
    $user = gigTestAuthor();
    Sanctum::actingAs($user);

    $category = gigCategory();
    $event = makeGigEvent($user, $category);

    $event->gigs()->create([
        'type' => 'poet_artist',
        'quantity' => 2,
        'filled_quantity' => 0,
        'language' => 'Italiano',
        'hospitality_required' => true,
        'description' => 'Cerchiamo due performer.',
        'status' => 'open',
        'opened_at' => now(),
        'created_by_user_id' => $user->id,
        'updated_by_user_id' => $user->id,
    ]);

    $response = $this->getJson(route('api.v1.events.gigs.index', $event));

    $response
        ->assertOk()
        ->assertJsonCount(1, 'data.gigs')
        ->assertJsonPath('data.gigs.0.type', 'poet_artist')
        ->assertJsonPath('data.gigs.0.quantity', 2)
        ->assertJsonPath('data.gigs.0.hospitality_required', true);
});

it('stores an event gig', function () {
    $user = gigTestAuthor();
    Sanctum::actingAs($user);

    $category = gigCategory();
    $event = makeGigEvent($user, $category, [
        'title' => 'Festival Spoken Word',
        'slug' => 'festival-spoken-word',
    ]);

    $response = $this->postJson(route('api.v1.events.gigs.store', $event), [
        'type' => 'mc_host',
        'quantity' => 1,
        'language' => 'Italiano',
        'hospitality_required' => false,
        'description' => 'Serve host per introdurre la serata.',
        'status' => 'open',
    ]);

    $response
        ->assertCreated()
        ->assertJsonPath('data.gig.giggable_id', $event->id)
        ->assertJsonPath('data.gig.type', 'mc_host')
        ->assertJsonPath('data.gig.quantity', 1)
        ->assertJsonPath('data.gig.status', 'open');

    $this->assertDatabaseHas('gigs', [
        'giggable_type' => Event::class,
        'giggable_id' => $event->id,
        'type' => 'mc_host',
        'quantity' => 1,
        'status' => 'open',
    ]);
});

it('updates an event gig', function () {
    $user = gigTestAuthor();
    Sanctum::actingAs($user);

    $category = gigCategory();
    $event = makeGigEvent($user, $category);

    $gig = $event->gigs()->create([
        'type' => 'technical_support',
        'quantity' => 1,
        'filled_quantity' => 0,
        'language' => null,
        'hospitality_required' => false,
        'description' => 'Serve supporto audio.',
        'status' => 'draft',
        'created_by_user_id' => $user->id,
        'updated_by_user_id' => $user->id,
    ]);

    $response = $this->patchJson(route('api.v1.events.gigs.update', [
        'event' => $event,
        'gig' => $gig,
    ]), [
        'quantity' => 2,
        'hospitality_required' => true,
        'description' => 'Servono due persone per supporto audio e palco.',
        'status' => 'open',
    ]);

    $response
        ->assertOk()
        ->assertJsonPath('data.gig.id', $gig->id)
        ->assertJsonPath('data.gig.quantity', 2)
        ->assertJsonPath('data.gig.hospitality_required', true)
        ->assertJsonPath('data.gig.status', 'open');

    $this->assertDatabaseHas('gigs', [
        'id' => $gig->id,
        'quantity' => 2,
        'hospitality_required' => true,
        'status' => 'open',
    ]);
});

it('deletes an event gig', function () {
    $user = gigTestAuthor();
    Sanctum::actingAs($user);

    $category = gigCategory();
    $event = makeGigEvent($user, $category);

    $gig = $event->gigs()->create([
        'type' => 'volunteer',
        'quantity' => 3,
        'filled_quantity' => 0,
        'language' => null,
        'hospitality_required' => false,
        'description' => 'Supporto accoglienza.',
        'status' => 'open',
        'opened_at' => now(),
        'created_by_user_id' => $user->id,
        'updated_by_user_id' => $user->id,
    ]);

    $response = $this->deleteJson(route('api.v1.events.gigs.delete', [
        'event' => $event,
        'gig' => $gig,
    ]));

    $response
        ->assertOk()
        ->assertJsonPath('data.deleted', true);

    $this->assertSoftDeleted('gigs', [
        'id' => $gig->id,
    ]);
});

it('returns not found when the gig does not belong to the event', function () {
    $user = gigTestAuthor();
    Sanctum::actingAs($user);

    $category = gigCategory();
    $event = makeGigEvent($user, $category, [
        'slug' => 'evento-uno',
    ]);
    $otherEvent = makeGigEvent($user, $category, [
        'title' => 'Altro evento',
        'slug' => 'evento-due',
    ]);

    $gig = $otherEvent->gigs()->create([
        'type' => 'poet_artist',
        'quantity' => 1,
        'filled_quantity' => 0,
        'status' => 'open',
        'opened_at' => now(),
        'created_by_user_id' => $user->id,
        'updated_by_user_id' => $user->id,
    ]);

    $response = $this->patchJson(route('api.v1.events.gigs.update', [
        'event' => $event,
        'gig' => $gig,
    ]), [
        'quantity' => 4,
    ]);

    $response->assertNotFound();
});

it('lists open gigs for authenticated users', function () {
    $owner = gigTestAuthor();
    $viewer = User::factory()->poetOrganizer()->create([
        'email_verified_at' => now(),
    ]);
    Sanctum::actingAs($viewer);

    $category = gigCategory();
    $event = makeGigEvent($owner, $category);

    $event->gigs()->create([
        'type' => 'poet_artist',
        'quantity' => 2,
        'filled_quantity' => 0,
        'status' => 'open',
        'opened_at' => now(),
        'created_by_user_id' => $owner->id,
        'updated_by_user_id' => $owner->id,
    ]);

    $event->gigs()->create([
        'type' => 'mc_host',
        'quantity' => 1,
        'filled_quantity' => 0,
        'status' => 'draft',
        'created_by_user_id' => $owner->id,
        'updated_by_user_id' => $owner->id,
    ]);

    $response = $this->getJson(route('api.v1.gigs.open'));

    $response
        ->assertOk()
        ->assertJsonCount(1, 'data.gigs')
        ->assertJsonPath('data.gigs.0.type', 'poet_artist')
        ->assertJsonPath('data.gigs.0.parent.type', 'event')
        ->assertJsonPath('data.gigs.0.parent.id', $event->id)
        ->assertJsonPath('meta.total', 1);
});

it('stores a gig application for an authenticated user', function () {
    $owner = gigTestAuthor();
    $candidate = User::factory()->poetOrganizer()->create([
        'email_verified_at' => now(),
    ]);
    Sanctum::actingAs($candidate);

    $category = gigCategory();
    $event = makeGigEvent($owner, $category);

    $gig = $event->gigs()->create([
        'type' => 'poet_artist',
        'quantity' => 2,
        'filled_quantity' => 0,
        'status' => 'open',
        'opened_at' => now(),
        'created_by_user_id' => $owner->id,
        'updated_by_user_id' => $owner->id,
    ]);

    $response = $this->postJson(route('api.v1.gigs.applications.store', $gig), [
        'message' => 'Mi candido volentieri per la serata.',
    ]);

    $response
        ->assertCreated()
        ->assertJsonPath('data.application.gig_id', $gig->id)
        ->assertJsonPath('data.application.user_id', $candidate->id)
        ->assertJsonPath('data.application.status', 'pending');

    $this->assertDatabaseHas('gig_applications', [
        'gig_id' => $gig->id,
        'user_id' => $candidate->id,
        'status' => 'pending',
    ]);
});

it('rejects duplicate gig applications from the same user', function () {
    $owner = gigTestAuthor();
    $candidate = User::factory()->poetOrganizer()->create([
        'email_verified_at' => now(),
    ]);
    Sanctum::actingAs($candidate);

    $category = gigCategory();
    $event = makeGigEvent($owner, $category);

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
        'user_id' => $candidate->id,
        'status' => 'pending',
    ]);

    $response = $this->postJson(route('api.v1.gigs.applications.store', $gig), [
        'message' => 'Seconda candidatura.',
    ]);

    $response
        ->assertStatus(422)
        ->assertJsonStructure([
            'data',
            'meta',
            'errors' => [
                'message',
                'fields' => [
                    'application',
                ],
            ],
        ]);
});

it('lists my gig applications', function () {
    $owner = gigTestAuthor();
    $candidate = User::factory()->poetOrganizer()->create([
        'email_verified_at' => now(),
    ]);
    Sanctum::actingAs($candidate);

    $category = gigCategory();
    $event = makeGigEvent($owner, $category);
    $gig = $event->gigs()->create([
        'type' => 'mc_host',
        'quantity' => 1,
        'filled_quantity' => 0,
        'status' => 'open',
        'opened_at' => now(),
        'created_by_user_id' => $owner->id,
        'updated_by_user_id' => $owner->id,
    ]);

    GigApplication::query()->create([
        'gig_id' => $gig->id,
        'user_id' => $candidate->id,
        'status' => 'pending',
        'message' => 'Sono disponibile.',
    ]);

    $response = $this->getJson(route('api.v1.gigs.applications.my'));

    $response
        ->assertOk()
        ->assertJsonCount(1, 'data.applications')
        ->assertJsonPath('data.applications.0.gig_id', $gig->id)
        ->assertJsonPath('data.applications.0.status', 'pending');
});

it('allows the organizer to list gig applications', function () {
    $owner = gigTestAuthor();
    Sanctum::actingAs($owner);

    $candidate = User::factory()->poetOrganizer()->create([
        'email_verified_at' => now(),
    ]);
    $candidate->profile()->update([
        'display_name' => 'Luca Blu',
    ]);

    $category = gigCategory();
    $event = makeGigEvent($owner, $category);
    $gig = $event->gigs()->create([
        'type' => 'technical_support',
        'quantity' => 1,
        'filled_quantity' => 0,
        'status' => 'open',
        'opened_at' => now(),
        'created_by_user_id' => $owner->id,
        'updated_by_user_id' => $owner->id,
    ]);

    GigApplication::query()->create([
        'gig_id' => $gig->id,
        'user_id' => $candidate->id,
        'status' => 'pending',
        'message' => 'Ho esperienza audio.',
    ]);

    $response = $this->getJson(route('api.v1.gigs.applications.index', $gig));

    $response
        ->assertOk()
        ->assertJsonCount(1, 'data.applications')
        ->assertJsonPath('data.applications.0.user.display_name', 'Luca Blu');
});

it('forbids non organizers from listing gig applications', function () {
    $owner = gigTestAuthor();
    $candidate = User::factory()->poetOrganizer()->create([
        'email_verified_at' => now(),
    ]);
    Sanctum::actingAs($candidate);

    $category = gigCategory();
    $event = makeGigEvent($owner, $category);
    $gig = $event->gigs()->create([
        'type' => 'volunteer',
        'quantity' => 1,
        'filled_quantity' => 0,
        'status' => 'open',
        'opened_at' => now(),
        'created_by_user_id' => $owner->id,
        'updated_by_user_id' => $owner->id,
    ]);

    $this->getJson(route('api.v1.gigs.applications.index', $gig))
        ->assertForbidden();
});

it('confirms a gig application and marks the gig as filled when quantity is reached', function () {
    $owner = gigTestAuthor();
    Sanctum::actingAs($owner);

    $candidate = User::factory()->poetOrganizer()->create([
        'email_verified_at' => now(),
    ]);

    $category = gigCategory();
    $event = makeGigEvent($owner, $category);
    $gig = $event->gigs()->create([
        'type' => 'mc_host',
        'quantity' => 1,
        'filled_quantity' => 0,
        'status' => 'open',
        'opened_at' => now(),
        'created_by_user_id' => $owner->id,
        'updated_by_user_id' => $owner->id,
    ]);

    $application = GigApplication::query()->create([
        'gig_id' => $gig->id,
        'user_id' => $candidate->id,
        'status' => 'pending',
    ]);

    $response = $this->patchJson(route('api.v1.gigs.applications.update', [
        'gig' => $gig,
        'application' => $application,
    ]), [
        'status' => 'confirmed',
    ]);

    $response
        ->assertOk()
        ->assertJsonPath('data.application.status', 'confirmed')
        ->assertJsonPath('data.gig.filled_quantity', 1)
        ->assertJsonPath('data.gig.status', 'filled');

    $this->assertDatabaseHas('gig_applications', [
        'id' => $application->id,
        'status' => 'confirmed',
    ]);

    $this->assertDatabaseHas('gigs', [
        'id' => $gig->id,
        'filled_quantity' => 1,
        'status' => 'filled',
    ]);
});

it('allows a candidate to withdraw their gig application', function () {
    $owner = gigTestAuthor();
    $candidate = User::factory()->poetOrganizer()->create([
        'email_verified_at' => now(),
    ]);
    Sanctum::actingAs($candidate);

    $category = gigCategory();
    $event = makeGigEvent($owner, $category);
    $gig = $event->gigs()->create([
        'type' => 'poet_artist',
        'quantity' => 2,
        'filled_quantity' => 0,
        'status' => 'open',
        'opened_at' => now(),
        'created_by_user_id' => $owner->id,
        'updated_by_user_id' => $owner->id,
    ]);

    $application = GigApplication::query()->create([
        'gig_id' => $gig->id,
        'user_id' => $candidate->id,
        'status' => 'pending',
    ]);

    $response = $this->postJson(route('api.v1.gigs.applications.withdraw', [
        'gig' => $gig,
        'application' => $application,
    ]));

    $response
        ->assertOk()
        ->assertJsonPath('data.application.status', 'withdrawn');

    $this->assertDatabaseHas('gig_applications', [
        'id' => $application->id,
        'status' => 'withdrawn',
    ]);
});

it('allows the organizer to close a filled gig', function () {
    $owner = gigTestAuthor();
    Sanctum::actingAs($owner);

    $category = gigCategory();
    $event = makeGigEvent($owner, $category);
    $gig = $event->gigs()->create([
        'type' => 'mc_host',
        'quantity' => 1,
        'filled_quantity' => 1,
        'status' => 'filled',
        'opened_at' => now()->subDay(),
        'filled_at' => now()->subHour(),
        'created_by_user_id' => $owner->id,
        'updated_by_user_id' => $owner->id,
    ]);

    $response = $this->postJson(route('api.v1.gigs.close', $gig));

    $response
        ->assertOk()
        ->assertJsonPath('data.gig.status', 'closed')
        ->assertJsonPath('data.gig.filled_quantity', 1);

    $this->assertDatabaseHas('gigs', [
        'id' => $gig->id,
        'status' => 'closed',
    ]);
});

it('rejects closing a gig before all positions are covered', function () {
    $owner = gigTestAuthor();
    Sanctum::actingAs($owner);

    $category = gigCategory();
    $event = makeGigEvent($owner, $category);
    $gig = $event->gigs()->create([
        'type' => 'poet_artist',
        'quantity' => 2,
        'filled_quantity' => 1,
        'status' => 'open',
        'opened_at' => now(),
        'created_by_user_id' => $owner->id,
        'updated_by_user_id' => $owner->id,
    ]);

    $this->postJson(route('api.v1.gigs.close', $gig))
        ->assertStatus(422)
        ->assertJsonStructure([
            'data',
            'meta',
            'errors' => [
                'message',
                'fields' => [
                    'gig',
                ],
            ],
        ]);
});

it('lets the organizer create and list multiple gig announcements', function () {
    $owner = gigTestAuthor();
    Sanctum::actingAs($owner);

    $category = gigCategory();
    $event = makeGigEvent($owner, $category);
    $gig = $event->gigs()->create([
        'type' => 'technical_support',
        'quantity' => 1,
        'filled_quantity' => 0,
        'status' => 'open',
        'opened_at' => now(),
        'created_by_user_id' => $owner->id,
        'updated_by_user_id' => $owner->id,
    ]);

    $first = $this->postJson(route('api.v1.gigs.announcements.store', $gig), [
        'message' => 'Cerchiamo supporto tecnico per il palco.',
        'status' => 'published',
    ]);

    $first
        ->assertCreated()
        ->assertJsonPath('data.announcement.status', 'published');

    $this->postJson(route('api.v1.gigs.announcements.store', $gig), [
        'message' => 'Aggiornamento: resta solo una posizione.',
        'status' => 'draft',
    ])->assertCreated();

    $response = $this->getJson(route('api.v1.gigs.announcements.index', $gig));

    $response
        ->assertOk()
        ->assertJsonCount(2, 'data.announcements')
        ->assertJsonPath('data.announcements.0.gig_id', $gig->id);
});

it('lets the organizer update and delete a gig announcement', function () {
    $owner = gigTestAuthor();
    Sanctum::actingAs($owner);

    $category = gigCategory();
    $event = makeGigEvent($owner, $category);
    $gig = $event->gigs()->create([
        'type' => 'volunteer',
        'quantity' => 2,
        'filled_quantity' => 0,
        'status' => 'open',
        'opened_at' => now(),
        'created_by_user_id' => $owner->id,
        'updated_by_user_id' => $owner->id,
    ]);

    $announcement = GigAnnouncement::query()->create([
        'gig_id' => $gig->id,
        'author_user_id' => $owner->id,
        'message' => 'Primo annuncio',
        'status' => 'draft',
    ]);

    $this->patchJson(route('api.v1.gigs.announcements.update', [
        'gig' => $gig,
        'announcement' => $announcement,
    ]), [
        'message' => 'Annuncio aggiornato',
        'status' => 'published',
    ])
        ->assertOk()
        ->assertJsonPath('data.announcement.message', 'Annuncio aggiornato')
        ->assertJsonPath('data.announcement.status', 'published');

    $this->deleteJson(route('api.v1.gigs.announcements.delete', [
        'gig' => $gig,
        'announcement' => $announcement,
    ]))
        ->assertOk()
        ->assertJsonPath('data.deleted', true);

    $this->assertSoftDeleted('gig_announcements', [
        'id' => $announcement->id,
    ]);
});
