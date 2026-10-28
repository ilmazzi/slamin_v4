<?php

use Domain\Event\Models\Event;
use Domain\Event\Models\EventCategory;
use Domain\Gig\Models\Gig;
use Domain\Gig\Models\GigAnnouncement;
use Domain\Gig\Models\GigApplication;
use Domain\Gig\Models\GigNegotiation;
use Domain\Poetry\Models\Poem;
use Domain\Poetry\Models\PoemCategory;
use Domain\Poetry\Models\PoemType;
use Domain\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

function catalogUser(): User
{
    return User::factory()->poetOrganizer()->create([
        'email_verified_at' => now(),
    ]);
}

function catalogEvent(User $owner, array $overrides = []): Event
{
    static $sequence = 0;

    $category = EventCategory::query()->firstOrCreate(
        ['slug' => 'catalog'],
        ['is_active' => true, 'sort_order' => 1],
    );
    $sequence++;

    $event = Event::query()->create(array_merge([
        'user_id' => $owner->id,
        'event_category_id' => $category->id,
        'title' => 'Evento '.$sequence,
        'slug' => 'evento-catalogo-'.$sequence.'-'.uniqid(),
        'description' => 'Evento catalogo.',
        'schedule_mode' => 'fixed',
        'delivery_mode' => 'in_person',
        'status' => 'published',
        'is_public' => true,
        'published_at' => now(),
    ], $overrides));

    $event->venueDetail()->create([
        'place_name' => 'Teatro',
        'city' => 'Venezia',
        'country_code' => 'IT',
    ]);

    return $event;
}

function catalogPoem(User $owner, array $overrides = []): Poem
{
    $category = PoemCategory::query()->firstOrCreate(['slug' => 'catalog-poems'], ['is_active' => true, 'sort_order' => 1]);
    $type = PoemType::query()->firstOrCreate(['slug' => 'catalog-type'], ['is_active' => true, 'sort_order' => 1]);

    return Poem::query()->create(array_merge([
        'user_id' => $owner->id,
        'poem_category_id' => $category->id,
        'poem_type_id' => $type->id,
        'title' => 'Poesia catalogo',
        'slug' => 'poesia-catalogo-'.uniqid(),
        'content' => 'Testo.',
        'language' => 'it',
        'status' => 'published',
        'is_public' => true,
        'published_at' => now(),
    ], $overrides));
}

function catalogGig(Event|Poem $parent, array $overrides = []): Gig
{
    return $parent->gigs()->create(array_merge([
        'type' => $parent instanceof Poem ? 'translator' : 'poet_artist',
        'language' => $parent instanceof Poem ? 'English' : null,
        'quantity' => 1,
        'filled_quantity' => 0,
        'status' => 'open',
        'opened_at' => now(),
        'created_by_user_id' => $parent->user_id,
        'updated_by_user_id' => $parent->user_id,
    ], $overrides));
}

it('lists only open gigs whose parent is published and public', function () {
    $owner = catalogUser();
    Sanctum::actingAs(catalogUser());

    $visible = catalogGig(catalogEvent($owner, ['title' => 'Serata pubblica']));
    catalogGig(catalogEvent($owner, ['status' => 'draft']));
    catalogGig(catalogEvent($owner, ['is_public' => false]));
    catalogGig(catalogPoem($owner, ['status' => 'draft']));
    $visiblePoemGig = catalogGig(catalogPoem($owner));

    $response = $this->getJson(route('api.v1.gigs.open'))->assertOk();

    expect(collect($response->json('data.gigs'))->pluck('id')->sort()->values()->all())
        ->toBe(collect([$visible->id, $visiblePoemGig->id])->sort()->values()->all());

    $eventGig = collect($response->json('data.gigs'))->firstWhere('id', $visible->id);

    expect($eventGig['parent']['city'])->toBe('Venezia')
        ->and($eventGig['parent']['title'])->toBe('Serata pubblica')
        ->and($eventGig['can_manage'])->toBeFalse()
        ->and($eventGig['my_application'])->toBeNull();
});

it('filters, searches and paginates open gigs', function () {
    $owner = catalogUser();
    Sanctum::actingAs(catalogUser());

    catalogGig(catalogEvent($owner, ['title' => 'Slam di Venezia']), ['type' => 'mc_host']);
    foreach (range(1, 3) as $index) {
        catalogGig(catalogEvent($owner, ['title' => 'Reading '.$index]), ['type' => 'volunteer']);
    }

    $this->getJson(route('api.v1.gigs.open', ['type' => 'volunteer', 'per_page' => 2]))
        ->assertOk()
        ->assertJsonCount(2, 'data.gigs')
        ->assertJsonPath('meta.total', 3)
        ->assertJsonPath('meta.last_page', 2);

    $this->getJson(route('api.v1.gigs.open', ['q' => 'slam']))
        ->assertOk()
        ->assertJsonCount(1, 'data.gigs')
        ->assertJsonPath('data.gigs.0.type', 'mc_host');

    $owner->profile()->update([
        'nickname' => 'livia_host',
        'display_name' => 'Livia Host',
    ]);

    $this->getJson(route('api.v1.gigs.open', ['q' => 'Livia']))
        ->assertOk()
        ->assertJsonCount(4, 'data.gigs');

    $this->getJson(route('api.v1.gigs.open', ['sort' => 'random']))
        ->assertStatus(422);
});

it('filters open gigs by event without leaking hidden ones', function () {
    $owner = catalogUser();
    Sanctum::actingAs(catalogUser());

    $event = catalogEvent($owner);
    $openGig = catalogGig($event, ['type' => 'mc_host']);
    catalogGig($event, ['status' => 'draft']);
    catalogGig(catalogEvent($owner));
    $privateEvent = catalogEvent($owner, ['is_public' => false]);
    catalogGig($privateEvent);

    $this->getJson(route('api.v1.gigs.open', ['event_id' => $event->id]))
        ->assertOk()
        ->assertJsonCount(1, 'data.gigs')
        ->assertJsonPath('data.gigs.0.id', $openGig->id);

    $this->getJson(route('api.v1.gigs.open', ['event_id' => $privateEvent->id]))
        ->assertOk()
        ->assertJsonCount(0, 'data.gigs');

    $this->getJson(route('api.v1.gigs.open', ['event_id' => 'abc']))
        ->assertStatus(422);
});

it('exposes the viewer application in the catalog', function () {
    $owner = catalogUser();
    $candidate = catalogUser();
    Sanctum::actingAs($candidate);

    $gig = catalogGig(catalogEvent($owner));
    $application = GigApplication::query()->create([
        'gig_id' => $gig->id,
        'user_id' => $candidate->id,
        'status' => 'pending',
    ]);

    $this->getJson(route('api.v1.gigs.open'))
        ->assertOk()
        ->assertJsonPath('data.gigs.0.my_application.id', $application->id)
        ->assertJsonPath('data.gigs.0.my_application.status', 'pending');
});

it('hides gigs of private parents from strangers but not from the owner', function () {
    $owner = catalogUser();
    $gig = catalogGig(catalogEvent($owner, ['status' => 'draft']));
    GigApplication::query()->create(['gig_id' => $gig->id, 'user_id' => catalogUser()->id, 'status' => 'pending']);

    Sanctum::actingAs(catalogUser());
    $this->getJson(route('api.v1.gigs.show', $gig))->assertNotFound();
    $this->postJson(route('api.v1.gigs.applications.store', $gig))->assertNotFound();
    $this->getJson(route('api.v1.gigs.announcements.index', $gig))->assertNotFound();

    Sanctum::actingAs($owner);
    $this->getJson(route('api.v1.gigs.show', $gig))
        ->assertOk()
        ->assertJsonPath('data.gig.can_manage', true)
        ->assertJsonPath('data.gig.applications_count', 1)
        ->assertJsonPath('data.gig.pending_applications_count', 1)
        ->assertJsonPath('data.gig.parent.owner.id', $owner->id);
});

it('does not expose application counts to non managers', function () {
    $gig = catalogGig(catalogEvent(catalogUser()));
    Sanctum::actingAs(catalogUser());

    $response = $this->getJson(route('api.v1.gigs.show', $gig))->assertOk();

    expect($response->json('data.gig'))->not->toHaveKey('applications_count')
        ->and($response->json('data.gig.can_manage'))->toBeFalse();
});

it('prevents the owner from applying to their own gig', function () {
    $owner = catalogUser();
    $gig = catalogGig(catalogEvent($owner));
    Sanctum::actingAs($owner);

    $this->postJson(route('api.v1.gigs.applications.store', $gig))
        ->assertStatus(422)
        ->assertJsonStructure(['errors' => ['fields' => ['gig']]]);
});

it('shows only published announcements to non managers', function () {
    $owner = catalogUser();
    $gig = catalogGig(catalogEvent($owner));
    GigAnnouncement::query()->create(['gig_id' => $gig->id, 'author_user_id' => $owner->id, 'message' => 'Pubblico', 'status' => 'published', 'published_at' => now()]);
    GigAnnouncement::query()->create(['gig_id' => $gig->id, 'author_user_id' => $owner->id, 'message' => 'Bozza', 'status' => 'draft']);

    Sanctum::actingAs(catalogUser());
    $this->getJson(route('api.v1.gigs.announcements.index', $gig))
        ->assertOk()
        ->assertJsonCount(1, 'data.announcements')
        ->assertJsonPath('data.announcements.0.message', 'Pubblico');
});

it('does not expose candidate emails to organizers', function () {
    $owner = catalogUser();
    $gig = catalogGig(catalogEvent($owner));
    GigApplication::query()->create(['gig_id' => $gig->id, 'user_id' => catalogUser()->id, 'status' => 'pending']);
    Sanctum::actingAs($owner);

    $response = $this->getJson(route('api.v1.gigs.applications.index', $gig))->assertOk();

    expect($response->json('data.applications.0.user'))->not->toHaveKey('email');
});

it('rejects confirming more people than requested', function () {
    $owner = catalogUser();
    $gig = catalogGig(catalogEvent($owner), ['quantity' => 1, 'filled_quantity' => 1, 'status' => 'filled']);
    GigApplication::query()->create(['gig_id' => $gig->id, 'user_id' => catalogUser()->id, 'status' => 'confirmed']);
    $second = GigApplication::query()->create(['gig_id' => $gig->id, 'user_id' => catalogUser()->id, 'status' => 'pending']);
    Sanctum::actingAs($owner);

    $this->patchJson(route('api.v1.gigs.applications.update', ['gig' => $gig, 'application' => $second]), [
        'status' => 'confirmed',
    ])->assertStatus(422);
});

it('reopens a filled gig when a confirmed candidate withdraws', function () {
    $owner = catalogUser();
    $candidate = catalogUser();
    $gig = catalogGig(catalogEvent($owner), ['quantity' => 1, 'filled_quantity' => 1, 'status' => 'filled', 'filled_at' => now()]);
    $application = GigApplication::query()->create(['gig_id' => $gig->id, 'user_id' => $candidate->id, 'status' => 'confirmed']);
    Sanctum::actingAs($candidate);

    $this->postJson(route('api.v1.gigs.applications.withdraw', ['gig' => $gig, 'application' => $application]))
        ->assertOk()
        ->assertJsonPath('data.application.status', 'withdrawn');

    $this->assertDatabaseHas('gigs', ['id' => $gig->id, 'status' => 'open', 'filled_quantity' => 0]);
});

it('updates offer fields and refuses fewer people than confirmed', function () {
    $owner = catalogUser();
    $event = catalogEvent($owner);
    $gig = catalogGig($event, ['quantity' => 3, 'filled_quantity' => 2]);
    Sanctum::actingAs($owner);

    $this->patchJson(route('api.v1.events.gigs.update', ['event' => $event, 'gig' => $gig]), [
        'cachet_enabled' => true,
        'proposed_price' => 150,
        'currency' => 'EUR',
        'accommodation_enabled' => false,
        'accommodation_details' => 'Ignorato',
    ])
        ->assertOk()
        ->assertJsonPath('data.gig.cachet_enabled', true)
        ->assertJsonPath('data.gig.proposed_price', '150.00')
        ->assertJsonPath('data.gig.accommodation_details', null);

    $this->patchJson(route('api.v1.events.gigs.update', ['event' => $event, 'gig' => $gig]), [
        'quantity' => 1,
    ])->assertStatus(422);

    $this->patchJson(route('api.v1.events.gigs.update', ['event' => $event, 'gig' => $gig]), [
        'quantity' => 2,
    ])
        ->assertOk()
        ->assertJsonPath('data.gig.status', 'filled');
});

it('lists the gigs managed by the current user with counters', function () {
    $owner = catalogUser();
    $gig = catalogGig(catalogEvent($owner, ['status' => 'draft']));
    catalogGig(catalogPoem($owner));
    catalogGig(catalogEvent(catalogUser()));
    GigApplication::query()->create(['gig_id' => $gig->id, 'user_id' => catalogUser()->id, 'status' => 'pending']);
    Sanctum::actingAs($owner);

    $response = $this->getJson(route('api.v1.gigs.managed'))
        ->assertOk()
        ->assertJsonCount(2, 'data.gigs')
        ->assertJsonPath('meta.total', 2);

    $managed = collect($response->json('data.gigs'))->firstWhere('id', $gig->id);

    expect($managed['pending_applications_count'])->toBe(1)
        ->and($managed['can_manage'])->toBeTrue();
});

it('guards negotiation state transitions', function () {
    $owner = catalogUser();
    $translator = catalogUser();
    $gig = catalogGig(catalogPoem($owner), ['status' => 'filled']);
    $application = GigApplication::query()->create(['gig_id' => $gig->id, 'user_id' => $translator->id, 'status' => 'accepted']);
    $negotiation = GigNegotiation::query()->create([
        'gig_id' => $gig->id,
        'gig_application_id' => $application->id,
        'owner_user_id' => $owner->id,
        'translator_user_id' => $translator->id,
        'status' => 'open',
        'currency' => 'EUR',
    ]);

    Sanctum::actingAs($owner);
    $this->postJson(route('api.v1.gig-negotiations.agree', $negotiation))->assertStatus(422);
    $this->postJson(route('api.v1.gig-negotiations.events.store', $negotiation), [
        'event_type' => 'translator_offer',
        'price' => 10,
    ])->assertStatus(422);

    $this->postJson(route('api.v1.gig-negotiations.events.store', $negotiation), [
        'event_type' => 'owner_offer',
        'price' => 40,
        'currency' => 'EUR',
    ])->assertCreated();

    $this->postJson(route('api.v1.gig-negotiations.agree', $negotiation))->assertOk();
    $this->postJson(route('api.v1.gig-negotiations.agree', $negotiation))->assertStatus(422);

    Sanctum::actingAs(catalogUser());
    $this->getJson(route('api.v1.gig-negotiations.show', $negotiation))->assertForbidden();
});

it('refuses negotiations on non translation gigs', function () {
    $owner = catalogUser();
    $gig = catalogGig(catalogEvent($owner));
    $application = GigApplication::query()->create(['gig_id' => $gig->id, 'user_id' => catalogUser()->id, 'status' => 'pending']);
    Sanctum::actingAs($owner);

    $this->postJson(route('api.v1.gigs.negotiations.store', ['gig' => $gig, 'application' => $application]), [
        'price' => 10,
    ])->assertStatus(422);
});

it('includes open gigs in search only for verified users', function () {
    catalogGig(catalogEvent(catalogUser(), ['title' => 'Slam lagunare']));

    $this->getJson(route('search.show', ['q' => 'lagunare']))
        ->assertOk()
        ->assertJsonMissingPath('data.groups.5');

    Sanctum::actingAs(catalogUser());

    $this->getJson(route('search.show', ['q' => 'lagunare']))
        ->assertOk()
        ->assertJsonPath('data.groups.5.type', 'gigs')
        ->assertJsonPath('data.groups.5.items.0.title', 'Slam lagunare');
});
