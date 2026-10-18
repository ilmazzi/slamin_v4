<?php

use Domain\Event\Models\Event;
use Domain\Event\Models\EventCategory;
use Domain\Gig\Models\Gig;
use Domain\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

function shareGigOwner(): User
{
    return User::factory()->poetOrganizer()->create([
        'email_verified_at' => now(),
    ]);
}

function shareGigEvent(User $owner, array $overrides = []): Event
{
    $category = EventCategory::query()->firstOrCreate(
        ['slug' => 'share-gig'],
        ['is_active' => true, 'sort_order' => 1],
    );

    $event = Event::query()->create(array_merge([
        'user_id' => $owner->id,
        'event_category_id' => $category->id,
        'title' => 'Serata pubblica',
        'slug' => 'serata-pubblica-share-'.uniqid(),
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

function shareGig(Event $event, array $overrides = []): Gig
{
    return $event->gigs()->create(array_merge([
        'type' => 'poet_artist',
        'quantity' => 1,
        'filled_quantity' => 0,
        'status' => 'open',
        'opened_at' => now(),
        'description' => 'Cercasi poeta per la serata.',
        'created_by_user_id' => $event->user_id,
        'updated_by_user_id' => $event->user_id,
    ], $overrides));
}

it('renders branded gig cards in every selectable format', function (string $format, int $width, int $height) {
    $gig = shareGig(shareGigEvent(shareGigOwner()));

    $response = $this->get("/api/v1/gigs/{$gig->id}/share-card?format={$format}");
    $response->assertOk()->assertHeader('Content-Type', 'image/png');

    $rendered = imagecreatefromstring($response->getContent());
    expect($rendered)->toBeInstanceOf(GdImage::class)
        ->and(imagesx($rendered))->toBe($width)
        ->and(imagesy($rendered))->toBe($height);
    imagedestroy($rendered);
})->with([
    'square' => ['square', 1080, 1080],
    'portrait' => ['portrait', 1080, 1350],
    'story' => ['story', 1080, 1920],
]);

it('changes the card when the gig asks for different people', function () {
    $event = shareGigEvent(shareGigOwner());
    $event->occurrences()->create([
        'starts_at' => now()->addWeek()->setTime(21, 0),
        'timezone' => 'Europe/Rome',
        'is_primary' => true,
    ]);
    $poets = shareGig($event, ['quantity' => 2]);
    $host = shareGig($event, ['type' => 'mc_host', 'quantity' => 1]);

    $poetCard = $this->get("/api/v1/gigs/{$poets->id}/share-card?template=typographic")->assertOk()->getContent();
    $hostCard = $this->get("/api/v1/gigs/{$host->id}/share-card?template=typographic")->assertOk()->getContent();
    $dateCut = $this->get("/api/v1/gigs/{$poets->id}/share-card?template=date_cut")->assertOk()->getContent();
    $sameHost = $this->get("/api/v1/gigs/{$host->id}/share-card?template=typographic")->assertOk()->getContent();

    expect(hash('sha256', $poetCard))->not->toBe(hash('sha256', $hostCard))
        ->and(hash('sha256', $poetCard))->not->toBe(hash('sha256', $dateCut))
        ->and(hash('sha256', $hostCard))->toBe(hash('sha256', $sameHost));
});

it('keeps the gig orange palette across templates', function () {
    $gig = shareGig(shareGigEvent(shareGigOwner()));

    $promo = $this->get("/api/v1/gigs/{$gig->id}/share-card?template=promo")->assertOk()->getContent();
    $brand = $this->get("/api/v1/gigs/{$gig->id}/share-card?template=brand")->assertOk()->getContent();

    expect(hash('sha256', $promo))->not->toBe(hash('sha256', $brand));

    $image = imagecreatefromstring($brand);
    $pixel = imagecolorat($image, 40, 40);
    imagedestroy($image);

    expect(($pixel >> 16) & 0xFF)->toBe(253)
        ->and(($pixel >> 8) & 0xFF)->toBe(174)
        ->and($pixel & 0xFF)->toBe(95);
});

it('does not generate cards for draft gigs or private parents', function () {
    $owner = shareGigOwner();
    $draft = shareGig(shareGigEvent($owner, ['slug' => 'serata-draft-parent']), ['status' => 'draft']);
    $privateParent = shareGig(shareGigEvent($owner, ['slug' => 'serata-private-parent']));
    $privateParent->load('giggable');
    $privateParent->giggable->update(['is_public' => false, 'status' => 'draft']);

    $this->get("/api/v1/gigs/{$draft->id}/share-card")->assertNotFound();
    $this->get("/api/v1/gigs/{$privateParent->id}/share-card")->assertNotFound();
});

it('exposes share urls only for publicly listed gigs', function () {
    config(['app.frontend_url' => 'https://staging.slamin.it']);
    $owner = shareGigOwner();
    $listed = shareGig(shareGigEvent($owner, ['slug' => 'serata-listed-share']));
    $draft = shareGig(shareGigEvent($owner, ['slug' => 'serata-draft-share']), ['status' => 'draft']);

    Sanctum::actingAs($owner);

    $this->getJson(route('api.v1.gigs.show', $listed))
        ->assertOk()
        ->assertJsonPath('data.gig.share_url', "https://staging.slamin.it/gigs/{$listed->id}");

    $this->getJson(route('api.v1.gigs.show', $draft))
        ->assertOk()
        ->assertJsonMissingPath('data.gig.share_url');
});

it('redirects humans from the gig share page and exposes a crawler preview', function () {
    config(['app.frontend_url' => 'http://localhost:5174']);
    $gig = shareGig(shareGigEvent(shareGigOwner()));

    $this->get("/share/gigs/{$gig->id}")
        ->assertRedirect("http://localhost:5174/gigs/{$gig->id}");

    $this->get("/share/gigs/{$gig->id}", ['User-Agent' => 'facebookexternalhit/1.1'])
        ->assertOk()
        ->assertSee(route('share.gigs.preview', ['id' => $gig->id]), false)
        ->assertSee('Serata pubblica', false);
});
