<?php

use Domain\Event\Models\Event;
use Domain\Event\Models\EventCategory;
use Domain\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

function eventShareAuthor(): User
{
    $user = User::factory()->poetOrganizer()->create();
    $user->profile()->update([
        'display_name' => 'Giada Rossi',
        'location' => 'Roma',
        'visibility' => [
            'bio' => true,
            'location' => true,
            'website_url' => true,
            'email' => false,
        ],
    ]);

    return $user->fresh(['profile']);
}

function eventShareCategory(): EventCategory
{
    $category = EventCategory::query()->firstOrCreate(
        ['slug' => 'open-mic-share'],
        ['is_active' => true, 'sort_order' => 1],
    );
    $category->translations()->firstOrCreate(
        ['locale' => 'it'],
        ['name' => 'Open Mic'],
    );

    return $category;
}

function publishedShareEvent(User $author, array $overrides = []): Event
{
    $event = Event::query()->create(array_merge([
        'user_id' => $author->id,
        'event_category_id' => eventShareCategory()->id,
        'title' => 'Slam al Parco',
        'slug' => 'slam-al-parco',
        'description' => 'Una serata di poesia all\'aperto.',
        'language' => 'it',
        'schedule_mode' => 'fixed',
        'delivery_mode' => 'in_person',
        'status' => 'published',
        'is_public' => true,
        'published_at' => now(),
    ], $overrides));

    $event->occurrences()->create([
        'starts_at' => now()->addWeek(),
        'timezone' => 'Europe/Rome',
        'is_primary' => true,
    ]);

    $event->venueDetail()->create([
        'city' => 'Roma',
        'place_name' => 'Parco della Poesia',
        'country_code' => 'IT',
    ]);

    return $event->fresh(['occurrences', 'venueDetail', 'user.profile']);
}

it('returns a png share card for a published public event', function () {
    $event = publishedShareEvent(eventShareAuthor());

    $response = $this->get("/share/events/{$event->slug}/card.png");

    $response
        ->assertOk()
        ->assertHeader('Content-Type', 'image/png');

    expect(substr($response->getContent(), 0, 8))->toBe("\x89PNG\r\n\x1a\n");
});

it('stamps the official white and red logo in the bottom left', function () {
    $event = publishedShareEvent(eventShareAuthor());

    $response = $this->get("/share/events/{$event->slug}/card.png");
    $response->assertOk();

    $rendered = imagecreatefromstring($response->getContent());
    expect($rendered)->toBeInstanceOf(GdImage::class);

    [$letterX, $letterY, $markX, $markY] = shareCardLogoSamplePoints(imagesx($rendered), imagesy($rendered));
    $letter = imagecolorat($rendered, $letterX, $letterY);
    $mark = imagecolorat($rendered, $markX, $markY);
    imagedestroy($rendered);

    expect(($letter >> 16) & 0xFF)->toBeGreaterThan(240)
        ->and(($letter >> 8) & 0xFF)->toBeGreaterThan(240)
        ->and($letter & 0xFF)->toBeGreaterThan(240);

    expect(($mark >> 16) & 0xFF)->toBeGreaterThan(210)
        ->and(($mark >> 8) & 0xFF)->toBeLessThan(20)
        ->and($mark & 0xFF)->toBeLessThan(40);
});

it('renders the cover image when the event has one', function () {
    Storage::fake('public');

    $canvas = imagecreatetruecolor(800, 500);
    imagefilledrectangle($canvas, 0, 0, 800, 500, imagecolorallocate($canvas, 220, 40, 40));
    ob_start();
    imagejpeg($canvas, null, 90);
    $jpeg = (string) ob_get_clean();
    imagedestroy($canvas);
    Storage::disk('public')->put('events/locandina.jpg', $jpeg);

    $event = publishedShareEvent(eventShareAuthor(), [
        'slug' => 'slam-con-locandina',
        'cover_image_path' => 'events/locandina.jpg',
    ]);

    $response = $this->get("/share/events/{$event->slug}/card.png");
    $response->assertOk()->assertHeader('Content-Type', 'image/png');

    $rendered = imagecreatefromstring($response->getContent());
    expect($rendered)->toBeInstanceOf(GdImage::class);

    $rgb = imagecolorat($rendered, 540, 80);
    [, , $markX, $markY] = shareCardLogoSamplePoints(imagesx($rendered), imagesy($rendered));
    $mark = imagecolorat($rendered, $markX, $markY);
    imagedestroy($rendered);

    expect(($rgb >> 16) & 0xFF)->toBeGreaterThan(180)
        ->and(($rgb >> 8) & 0xFF)->toBeLessThan(80)
        ->and($rgb & 0xFF)->toBeLessThan(80);

    expect(($mark >> 16) & 0xFF)->toBeGreaterThan(210)
        ->and(($mark >> 8) & 0xFF)->toBeLessThan(20)
        ->and($mark & 0xFF)->toBeLessThan(40);
});

it('fits a portrait cover without cropping it', function () {
    Storage::fake('public');

    $canvas = imagecreatetruecolor(400, 800);
    imagefilledrectangle($canvas, 0, 0, 400, 800, imagecolorallocate($canvas, 220, 40, 40));
    ob_start();
    imagejpeg($canvas, null, 90);
    $jpeg = (string) ob_get_clean();
    imagedestroy($canvas);
    Storage::disk('public')->put('events/locandina-verticale.jpg', $jpeg);

    $event = publishedShareEvent(eventShareAuthor(), [
        'slug' => 'slam-locandina-verticale',
        'cover_image_path' => 'events/locandina-verticale.jpg',
    ]);

    $response = $this->get("/share/events/{$event->slug}/card.png");
    $response->assertOk()->assertHeader('Content-Type', 'image/png');

    $rendered = imagecreatefromstring($response->getContent());
    expect($rendered)->toBeInstanceOf(GdImage::class);

    $center = imagecolorat($rendered, 540, 80);
    $gutter = imagecolorat($rendered, 24, 80);
    imagedestroy($rendered);

    expect(($center >> 16) & 0xFF)->toBeGreaterThan(180)
        ->and(($center >> 8) & 0xFF)->toBeLessThan(80);

    expect(($gutter >> 16) & 0xFF)->toBeLessThan(180)
        ->and(($gutter >> 8) & 0xFF)->toBeGreaterThan(180);
});

it('exposes the same png on the public api share-card route', function () {
    $event = publishedShareEvent(eventShareAuthor());

    $this->get("/api/v1/events/{$event->slug}/share-card")
        ->assertOk()
        ->assertHeader('Content-Type', 'image/png');
});

it('renders distinct event templates without changing the mint palette', function () {
    Storage::fake('public');

    $canvas = imagecreatetruecolor(800, 500);
    imagefilledrectangle($canvas, 0, 0, 800, 500, imagecolorallocate($canvas, 220, 40, 40));
    ob_start();
    imagejpeg($canvas, null, 90);
    $jpeg = (string) ob_get_clean();
    imagedestroy($canvas);
    Storage::disk('public')->put('events/locandina-template.jpg', $jpeg);

    $event = publishedShareEvent(eventShareAuthor(), [
        'slug' => 'slam-template-palette',
        'cover_image_path' => 'events/locandina-template.jpg',
    ]);

    $poster = $this->get("/api/v1/events/{$event->slug}/share-card?template=poster")->assertOk()->getContent();
    $typographic = $this->get("/api/v1/events/{$event->slug}/share-card?template=typographic")->assertOk()->getContent();
    $brand = $this->get("/api/v1/events/{$event->slug}/share-card?template=brand")->assertOk()->getContent();

    expect(hash('sha256', $poster))
        ->not->toBe(hash('sha256', $typographic))
        ->and(hash('sha256', $typographic))->not->toBe(hash('sha256', $brand));

    $image = imagecreatefromstring($brand);
    $pixel = imagecolorat($image, 40, 40);
    imagedestroy($image);

    expect(($pixel >> 16) & 0xFF)->toBe(132)
        ->and(($pixel >> 8) & 0xFF)->toBe(218)
        ->and($pixel & 0xFF)->toBe(180);
});

it('renders the date and framed event templates', function () {
    Storage::fake('public');

    $canvas = imagecreatetruecolor(800, 500);
    imagefilledrectangle($canvas, 0, 0, 800, 500, imagecolorallocate($canvas, 20, 20, 20));
    ob_start();
    imagejpeg($canvas, null, 90);
    $jpeg = (string) ob_get_clean();
    imagedestroy($canvas);
    Storage::disk('public')->put('events/cornice.jpg', $jpeg);

    $event = publishedShareEvent(eventShareAuthor(), [
        'slug' => 'slam-template-grafici',
        'cover_image_path' => 'events/cornice.jpg',
    ]);

    $dateCut = $this->get("/api/v1/events/{$event->slug}/share-card?template=date_cut")->assertOk()->getContent();
    $diagonal = $this->get("/api/v1/events/{$event->slug}/share-card?template=diagonal")->assertOk()->getContent();
    $framed = $this->get("/api/v1/events/{$event->slug}/share-card?template=framed")->assertOk()->getContent();
    $poster = $this->get("/api/v1/events/{$event->slug}/share-card?template=poster")->assertOk()->getContent();

    expect(hash('sha256', $dateCut))->not->toBe(hash('sha256', $framed))
        ->and(hash('sha256', $diagonal))->not->toBe(hash('sha256', $dateCut))
        ->and(hash('sha256', $framed))->not->toBe(hash('sha256', $poster));

    $dateCutImage = imagecreatefromstring($dateCut);
    expect($dateCutImage)->toBeInstanceOf(GdImage::class);
    $mint = imagecolorat($dateCutImage, 20, 20);
    $body = imagecolorat($dateCutImage, 1000, 180);
    $tail = imagecolorat($dateCutImage, 1060, 1060);
    $cut = imagecolorat($dateCutImage, 40, 1060);
    imagedestroy($dateCutImage);

    expect([($mint >> 16) & 0xFF, ($mint >> 8) & 0xFF, $mint & 0xFF])->toBe([132, 218, 180])
        ->and([($body >> 16) & 0xFF, ($body >> 8) & 0xFF, $body & 0xFF])->toBe([132, 218, 180])
        ->and([($tail >> 16) & 0xFF, ($tail >> 8) & 0xFF, $tail & 0xFF])->toBe([132, 218, 180])
        ->and([($cut >> 16) & 0xFF, ($cut >> 8) & 0xFF, $cut & 0xFF])->toBe([255, 255, 255]);
});

it('renders every post format at the expected dimensions', function (string $format, int $width, int $height) {
    $event = publishedShareEvent(eventShareAuthor());

    $response = $this->get("/api/v1/events/{$event->slug}/share-card?format={$format}");
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

it('renders the event category, time, location and cost details', function () {
    $author = eventShareAuthor();
    $freeEvent = publishedShareEvent($author, ['slug' => 'evento-gratuito-con-dettagli']);
    $paidEvent = publishedShareEvent($author, [
        'slug' => 'evento-a-pagamento-con-dettagli',
        'has_cost' => true,
        'price' => 18.50,
        'currency' => 'EUR',
    ]);
    $differentTime = publishedShareEvent($author, ['slug' => 'evento-orario-diverso']);
    $differentTime->occurrences()->update(['starts_at' => now()->addMonth()->setTime(21, 30)]);
    $differentPlace = publishedShareEvent($author, ['slug' => 'evento-luogo-diverso']);
    $differentPlace->venueDetail()->update(['place_name' => 'Teatro Nuovo', 'city' => 'Milano']);

    $differentCategory = EventCategory::query()->create([
        'slug' => 'festival-share-details',
        'is_active' => true,
        'sort_order' => 2,
    ]);
    $differentCategory->translations()->create(['locale' => 'it', 'name' => 'Festival']);
    $categoryEvent = publishedShareEvent($author, [
        'slug' => 'evento-categoria-diversa',
        'event_category_id' => $differentCategory->id,
    ]);

    $baseHash = hash(
        'sha256',
        $this->get("/api/v1/events/{$freeEvent->slug}/share-card")->assertOk()->getContent(),
    );

    foreach ([$paidEvent, $differentTime, $differentPlace, $categoryEvent] as $event) {
        $card = $this->get("/api/v1/events/{$event->slug}/share-card")->assertOk()->getContent();
        expect(hash('sha256', $card))->not->toBe($baseHash);
    }
});

it('does not render share cards for draft events even for the owner', function () {
    $author = eventShareAuthor();
    $event = publishedShareEvent($author, [
        'title' => 'Bozza evento',
        'slug' => 'bozza-evento',
        'status' => 'draft',
        'is_public' => false,
        'published_at' => null,
    ]);

    Sanctum::actingAs($author);

    $this->get("/share/events/{$event->slug}/card.png")->assertNotFound();
    $this->get("/api/v1/events/{$event->slug}/share-card")->assertNotFound();
});

it('redirects humans from the share page to the frontend event url', function () {
    config(['app.frontend_url' => 'http://localhost:5174']);
    $event = publishedShareEvent(eventShareAuthor());

    $this->get("/share/events/{$event->slug}")
        ->assertRedirect("http://localhost:5174/events/{$event->slug}");
});

it('exposes the public event page as share_url', function () {
    config(['app.frontend_url' => 'https://staging.slamin.it']);
    $event = publishedShareEvent(eventShareAuthor());

    $this->get("/api/v1/events/{$event->slug}")
        ->assertOk()
        ->assertJsonPath('data.event.share_url', "https://staging.slamin.it/events/{$event->slug}");
});

it('returns open graph html for social crawlers', function () {
    $event = publishedShareEvent(eventShareAuthor());

    $this->get("/share/events/{$event->slug}", [
        'User-Agent' => 'facebookexternalhit/1.1',
    ])
        ->assertOk()
        ->assertSee(route('share.events.preview', ['slug' => $event->slug]), false)
        ->assertSee('<meta property="og:image:width" content="1200">', false)
        ->assertSee('<meta property="og:image:height" content="630">', false)
        ->assertSee('Slam al Parco', false);
});

it('renders a landscape link preview with the cover on the right', function () {
    Storage::fake('public');

    $canvas = imagecreatetruecolor(800, 500);
    imagefilledrectangle($canvas, 0, 0, 800, 500, imagecolorallocate($canvas, 220, 40, 40));
    ob_start();
    imagejpeg($canvas, null, 90);
    $jpeg = (string) ob_get_clean();
    imagedestroy($canvas);
    Storage::disk('public')->put('events/locandina.jpg', $jpeg);

    $event = publishedShareEvent(eventShareAuthor(), [
        'slug' => 'slam-anteprima',
        'cover_image_path' => 'events/locandina.jpg',
    ]);

    $response = $this->get("/share/events/{$event->slug}/preview.png");
    $response->assertOk()->assertHeader('Content-Type', 'image/png');

    $rendered = imagecreatefromstring($response->getContent());
    expect(imagesx($rendered))->toBe(1200)
        ->and(imagesy($rendered))->toBe(630);

    $cover = imagecolorat($rendered, 1100, 80);
    $text = imagecolorat($rendered, 600, 80);
    imagedestroy($rendered);

    expect(($cover >> 16) & 0xFF)->toBeGreaterThan(180)
        ->and(($cover >> 8) & 0xFF)->toBeLessThan(80);

    expect(($text >> 8) & 0xFF)->toBeGreaterThan(180);
});

it('keeps the open graph preview on the default template', function () {
    $event = publishedShareEvent(eventShareAuthor(), ['slug' => 'slam-preview-template']);

    $default = $this->get("/share/events/{$event->slug}/preview.png")->assertOk()->getContent();
    $forcedBrand = $this->get("/share/events/{$event->slug}/preview.png?template=brand")->assertOk()->getContent();

    expect(hash('sha256', $default))->toBe(hash('sha256', $forcedBrand));
});
