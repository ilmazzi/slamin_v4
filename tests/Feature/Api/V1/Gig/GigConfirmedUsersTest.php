<?php

use Domain\Event\Models\Event;
use Domain\Event\Models\EventCategory;
use Domain\Gig\Models\Gig;
use Domain\Gig\Models\GigApplication;
use Domain\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

function confirmedGigUser(string $nickname): User
{
    $user = User::factory()->poetOrganizer()->create(['email_verified_at' => now()]);
    $user->profile()->update(['nickname' => $nickname, 'display_name' => ucfirst($nickname)]);

    return $user->refresh();
}

function confirmedGigEvent(User $owner, array $overrides = []): Event
{
    $category = EventCategory::query()->firstOrCreate(
        ['slug' => 'confirmed-showcase'],
        ['is_active' => true, 'sort_order' => 10],
    );

    return Event::query()->create(array_merge([
        'user_id' => $owner->id,
        'event_category_id' => $category->id,
        'title' => 'Serata Confermati',
        'slug' => 'serata-confermati-'.uniqid(),
        'description' => 'Evento per test confermati.',
        'schedule_mode' => 'fixed',
        'delivery_mode' => 'in_person',
        'status' => 'published',
        'is_public' => true,
        'published_at' => now(),
    ], $overrides));
}

function confirmedGigFor(Event $event, User $owner, string $status = 'open'): Gig
{
    return $event->gigs()->create([
        'type' => 'poet_artist',
        'quantity' => 3,
        'filled_quantity' => 0,
        'status' => $status,
        'opened_at' => now(),
        'created_by_user_id' => $owner->id,
        'updated_by_user_id' => $owner->id,
    ]);
}

function applyToGig(Gig $gig, User $user, string $status): GigApplication
{
    return GigApplication::query()->create([
        'gig_id' => $gig->id,
        'user_id' => $user->id,
        'status' => $status,
        'confirmed_at' => $status === 'confirmed' ? now() : null,
    ]);
}

it('shows only confirmed people on the gig detail', function () {
    $owner = confirmedGigUser('organizzatrice');
    $gig = confirmedGigFor(confirmedGigEvent($owner), $owner);
    applyToGig($gig, confirmedGigUser('anna'), 'confirmed');
    applyToGig($gig, confirmedGigUser('bruno'), 'pending');

    Sanctum::actingAs(confirmedGigUser('visitatore'));

    $this->getJson(route('api.v1.gigs.show', $gig))
        ->assertOk()
        ->assertJsonCount(1, 'data.gig.confirmed_users')
        ->assertJsonPath('data.gig.confirmed_users.0.nickname', 'anna')
        ->assertJsonPath('data.gig.confirmed_users.0.display_name', 'Anna');
});

it('lists confirmed people of an event, skipping draft gigs and gigs without confirmations', function () {
    $owner = confirmedGigUser('organizzatrice');
    $event = confirmedGigEvent($owner);
    $withConfirmed = confirmedGigFor($event, $owner, 'filled');
    $draft = confirmedGigFor($event, $owner, 'draft');
    $empty = confirmedGigFor($event, $owner);
    applyToGig($withConfirmed, confirmedGigUser('anna'), 'confirmed');
    applyToGig($draft, confirmedGigUser('carla'), 'confirmed');
    applyToGig($empty, confirmedGigUser('bruno'), 'accepted');

    Sanctum::actingAs(confirmedGigUser('visitatore'));

    $this->getJson(route('api.v1.events.gigs.confirmed', $event))
        ->assertOk()
        ->assertJsonCount(1, 'data.gigs')
        ->assertJsonPath('data.gigs.0.id', $withConfirmed->id)
        ->assertJsonPath('data.gigs.0.confirmed_users.0.nickname', 'anna');
});

it('hides confirmed people of a private event from other users', function () {
    $owner = confirmedGigUser('organizzatrice');
    $event = confirmedGigEvent($owner, ['is_public' => false]);
    applyToGig(confirmedGigFor($event, $owner), confirmedGigUser('anna'), 'confirmed');

    Sanctum::actingAs(confirmedGigUser('visitatore'));
    $this->getJson(route('api.v1.events.gigs.confirmed', $event))
        ->assertOk()
        ->assertJsonCount(0, 'data.gigs');

    Sanctum::actingAs($owner);
    $this->getJson(route('api.v1.events.gigs.confirmed', $event))
        ->assertOk()
        ->assertJsonCount(1, 'data.gigs');
});

it('shows confirmed people of a public event to guests but not of a private one', function () {
    $owner = confirmedGigUser('organizzatrice');
    $public = confirmedGigEvent($owner);
    $private = confirmedGigEvent($owner, ['is_public' => false]);
    applyToGig(confirmedGigFor($public, $owner), confirmedGigUser('anna'), 'confirmed');
    applyToGig(confirmedGigFor($private, $owner), confirmedGigUser('bruno'), 'confirmed');

    $this->getJson(route('api.v1.events.gigs.confirmed', $public))
        ->assertOk()
        ->assertJsonPath('data.gigs.0.confirmed_users.0.nickname', 'anna');

    $this->getJson(route('api.v1.events.gigs.confirmed', $private))
        ->assertOk()
        ->assertJsonCount(0, 'data.gigs');
});

it('returns the updated confirmed people after confirming an application', function () {
    $owner = confirmedGigUser('organizzatrice');
    $gig = confirmedGigFor(confirmedGigEvent($owner), $owner);
    $application = applyToGig($gig, confirmedGigUser('anna'), 'accepted');

    Sanctum::actingAs($owner);

    $this->patchJson(route('api.v1.gigs.applications.update', ['gig' => $gig, 'application' => $application]), [
        'status' => 'confirmed',
    ])
        ->assertOk()
        ->assertJsonPath('data.gig.confirmed_users.0.nickname', 'anna');
});
