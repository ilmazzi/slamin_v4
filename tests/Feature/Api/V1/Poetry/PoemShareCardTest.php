<?php

use Domain\Poetry\Models\Poem;
use Domain\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

function shareCardAuthor(): User
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

function publishedSharePoem(User $author, array $overrides = []): Poem
{
    return Poem::query()->create(array_merge([
        'user_id' => $author->id,
        'title' => 'Notte in centro',
        'slug' => 'notte-in-centro',
        'content' => "Cammino piano\ntra le voci della città\ne lascio che Roma\nmi scriva addosso.",
        'language' => 'it',
        'status' => 'published',
        'is_public' => true,
        'published_at' => now(),
    ], $overrides));
}

it('returns a png share card for a published public poem', function () {
    $poem = publishedSharePoem(shareCardAuthor());

    $response = $this->get("/share/poems/{$poem->slug}/card.png");

    $response
        ->assertOk()
        ->assertHeader('Content-Type', 'image/png');

    expect(substr($response->getContent(), 0, 8))->toBe("\x89PNG\r\n\x1a\n");
});

it('stamps the official white and red logo in the bottom left', function () {
    $poem = publishedSharePoem(shareCardAuthor());

    $response = $this->get("/share/poems/{$poem->slug}/card.png");
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

it('renders distinct poem templates on the same purple palette', function () {
    $poem = publishedSharePoem(shareCardAuthor());

    $verse = $this->get("/api/v1/poems/{$poem->slug}/share-card?template=verse")->assertOk()->getContent();
    $typographic = $this->get("/api/v1/poems/{$poem->slug}/share-card?template=typographic")->assertOk()->getContent();
    $dateCut = $this->get("/api/v1/poems/{$poem->slug}/share-card?template=date_cut")->assertOk()->getContent();
    $brand = $this->get("/api/v1/poems/{$poem->slug}/share-card?template=brand")->assertOk()->getContent();

    expect(hash('sha256', $verse))->not->toBe(hash('sha256', $brand))
        ->and(hash('sha256', $dateCut))->not->toBe(hash('sha256', $verse));

    $image = imagecreatefromstring($typographic);
    $pixel = imagecolorat($image, 40, 40);
    imagedestroy($image);

    expect(($pixel >> 16) & 0xFF)->toBe(165)
        ->and(($pixel >> 8) & 0xFF)->toBe(83)
        ->and($pixel & 0xFF)->toBe(221);
});

it('exposes the same png on the public api share-card route', function () {
    $poem = publishedSharePoem(shareCardAuthor());

    $response = $this->get("/api/v1/poems/{$poem->slug}/share-card");

    $response
        ->assertOk()
        ->assertHeader('Content-Type', 'image/png');

    expect(substr($response->getContent(), 0, 8))->toBe("\x89PNG\r\n\x1a\n");
});

it('does not render share cards for draft poems even for the owner', function () {
    $author = shareCardAuthor();
    $poem = publishedSharePoem($author, [
        'title' => 'Bozza nascosta',
        'slug' => 'bozza-nascosta',
        'status' => 'draft',
        'is_public' => false,
        'published_at' => null,
    ]);

    Sanctum::actingAs($author);

    $this->get("/share/poems/{$poem->slug}/card.png")->assertNotFound();
    $this->get("/api/v1/poems/{$poem->slug}/share-card")->assertNotFound();
});

it('redirects humans from the share page to the frontend poem url', function () {
    config(['app.frontend_url' => 'http://localhost:5174']);
    $poem = publishedSharePoem(shareCardAuthor());

    $this->get("/share/poems/{$poem->slug}")
        ->assertRedirect("http://localhost:5174/poems/{$poem->slug}");
});

it('exposes the public poem page as share_url', function () {
    config(['app.frontend_url' => 'https://staging.slamin.it']);
    $poem = publishedSharePoem(shareCardAuthor());

    $this->get("/api/v1/poems/{$poem->slug}")
        ->assertOk()
        ->assertJsonPath('data.poem.share_url', "https://staging.slamin.it/poems/{$poem->slug}");
});

it('returns open graph html for social crawlers', function () {
    $poem = publishedSharePoem(shareCardAuthor());

    $response = $this->get("/share/poems/{$poem->slug}", [
        'User-Agent' => 'facebookexternalhit/1.1',
    ]);

    $response
        ->assertOk()
        ->assertSee(route('share.poems.preview', ['slug' => $poem->slug]), false)
        ->assertSee('<meta property="og:image:width" content="1200">', false)
        ->assertSee('<meta property="og:image:height" content="630">', false)
        ->assertSee('Notte in centro', false);
});

it('renders a landscape link preview with the logo still in place', function () {
    $poem = publishedSharePoem(shareCardAuthor());

    $response = $this->get("/share/poems/{$poem->slug}/preview.png");
    $response->assertOk()->assertHeader('Content-Type', 'image/png');

    $rendered = imagecreatefromstring($response->getContent());
    expect(imagesx($rendered))->toBe(1200)
        ->and(imagesy($rendered))->toBe(630);

    [, , $markX, $markY] = shareCardLogoSamplePoints(imagesx($rendered), imagesy($rendered));
    $mark = imagecolorat($rendered, $markX, $markY);
    imagedestroy($rendered);

    expect(($mark >> 16) & 0xFF)->toBeGreaterThan(210)
        ->and(($mark >> 8) & 0xFF)->toBeLessThan(20);
});
