<?php

use Domain\Article\Models\Article;
use Domain\Media\Models\MediaItem;
use Domain\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('renders branded article cards in every selectable format', function (string $format, int $width, int $height) {
    $author = User::factory()->poetOrganizer()->create();
    $author->profile()->update(['display_name' => 'Ada Poeta']);
    $article = Article::query()->create([
        'user_id' => $author->id,
        'title' => 'Le parole cambiano la città',
        'slug' => 'parole-cambiano-citta',
        'content' => 'Un articolo dedicato alla poesia contemporanea.',
        'excerpt' => 'Un articolo dedicato alla poesia contemporanea.',
        'language' => 'it',
        'status' => 'published',
        'is_public' => true,
        'published_at' => now(),
    ]);

    $response = $this->get("/api/v1/articles/{$article->slug}/share-card?format={$format}");
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

it('renders a branded media card and uses it in the social preview', function () {
    $author = User::factory()->poetOrganizer()->create();
    $author->profile()->update(['display_name' => 'Ada Poeta']);
    $media = MediaItem::query()->create([
        'user_id' => $author->id,
        'type' => 'photo',
        'title' => 'Dietro le quinte',
        'description' => 'Una fotografia dal poetry slam.',
        'visibility' => 'public',
        'status' => 'ready',
        'file_size' => 0,
        'published_at' => now(),
    ]);

    $response = $this->get("/api/v1/media/{$media->id}/share-card?format=portrait");
    $response->assertOk()->assertHeader('Content-Type', 'image/png');

    $rendered = imagecreatefromstring($response->getContent());
    expect($rendered)->toBeInstanceOf(GdImage::class)
        ->and(imagesx($rendered))->toBe(1080)
        ->and(imagesy($rendered))->toBe(1350);
    imagedestroy($rendered);

    $this->get("/share/media/{$media->id}", ['User-Agent' => 'facebookexternalhit/1.1'])
        ->assertOk()
        ->assertSee(route('share.media.preview', ['id' => $media->id]), false);
});

it('does not generate cards for unpublished articles or private media', function () {
    $author = User::factory()->poetOrganizer()->create();
    $article = Article::query()->create([
        'user_id' => $author->id,
        'title' => 'Bozza',
        'slug' => 'bozza-share',
        'content' => 'Testo',
        'language' => 'it',
        'status' => 'draft',
        'is_public' => false,
    ]);
    $media = MediaItem::query()->create([
        'user_id' => $author->id,
        'type' => 'photo',
        'title' => 'Foto privata',
        'visibility' => 'private',
        'status' => 'ready',
        'file_size' => 0,
    ]);

    $this->get("/api/v1/articles/{$article->slug}/share-card")->assertNotFound();
    $this->get("/api/v1/media/{$media->id}/share-card")->assertNotFound();
});
