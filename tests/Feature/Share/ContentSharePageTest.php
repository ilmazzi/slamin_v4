<?php

use Domain\Article\Models\Article;
use Domain\Media\Models\MediaItem;
use Domain\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

function sharedArticle(array $overrides = []): Article
{
    return Article::query()->create(array_merge([
        'user_id' => User::factory()->poetOrganizer()->create()->id,
        'title' => 'Voci dalla piazza',
        'slug' => 'voci-dalla-piazza',
        'content' => '<p>Corpo completo</p>',
        'excerpt' => 'Una serata di slam in centro',
        'cover_image_path' => 'articles/covers/piazza.jpg',
        'language' => 'it',
        'status' => 'published',
        'is_public' => true,
        'published_at' => now(),
    ], $overrides));
}

function sharedMedia(array $overrides = []): MediaItem
{
    return MediaItem::query()->create(array_merge([
        'user_id' => User::factory()->create()->id,
        'type' => 'photo',
        'title' => 'Ritratto in scena',
        'description' => 'Foto dal palco',
        'visibility' => 'public',
        'status' => 'ready',
        'file_path' => 'media/photos/ritratto.jpg',
        'uploaded_at' => now(),
        'published_at' => now(),
    ], $overrides));
}

it('returns open graph html for an article with its cover', function () {
    $article = sharedArticle();

    $this->get("/share/articles/{$article->slug}", ['User-Agent' => 'WhatsApp/2.23'])
        ->assertOk()
        ->assertSee('Voci dalla piazza', false)
        ->assertSee('Una serata di slam in centro', false)
        ->assertSee(Storage::disk('public')->url('articles/covers/piazza.jpg'), false)
        ->assertDontSee('og:image:width', false);
});

it('redirects humans from the article share page to the frontend article url', function () {
    config(['app.frontend_url' => 'http://localhost:5174']);
    $article = sharedArticle();

    $this->get("/share/articles/{$article->slug}")
        ->assertRedirect('http://localhost:5174/articles/voci-dalla-piazza');
});

it('hides article share pages that are not public', function () {
    $draft = sharedArticle(['slug' => 'bozza', 'status' => 'draft', 'published_at' => null]);
    $private = sharedArticle(['slug' => 'privato', 'is_public' => false]);

    $this->get("/share/articles/{$draft->slug}")->assertNotFound();
    $this->get("/share/articles/{$private->slug}")->assertNotFound();
});

it('returns open graph html for a public photo', function () {
    $media = sharedMedia();

    $this->get("/share/media/{$media->id}", ['User-Agent' => 'TelegramBot (like TwitterBot)'])
        ->assertOk()
        ->assertSee('Ritratto in scena', false)
        ->assertSee('Foto dal palco', false)
        ->assertSee(Storage::disk('public')->url('media/photos/ritratto.jpg'), false);
});

it('redirects humans from the media share page to the frontend media url', function () {
    config(['app.frontend_url' => 'http://localhost:5174']);
    $media = sharedMedia();

    $this->get("/share/media/{$media->id}")
        ->assertRedirect("http://localhost:5174/media/{$media->id}");
});

it('hides media share pages that are private or not ready', function () {
    $private = sharedMedia(['visibility' => 'private']);
    $processing = sharedMedia(['status' => 'processing']);

    $this->get("/share/media/{$private->id}")->assertNotFound();
    $this->get("/share/media/{$processing->id}")->assertNotFound();
});
