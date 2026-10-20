<?php

use Domain\Article\Models\Article;
use Domain\Event\Models\Event;
use Domain\Media\Models\MediaItem;
use Domain\Poetry\Models\Poem;
use Domain\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function sitemapAuthor(): User
{
    return User::factory()->poetOrganizer()->create([
        'email_verified_at' => now(),
    ]);
}

it('exposes a sitemap of public pages and published content', function () {
    config(['app.frontend_url' => 'https://slamin.it']);

    $author = sitemapAuthor();
    $author->profile()->update(['nickname' => 'giada', 'is_public' => true]);

    Event::query()->create([
        'user_id' => $author->id,
        'title' => 'Slam pubblico',
        'slug' => 'slam-pubblico',
        'description' => 'Una serata aperta.',
        'schedule_mode' => 'fixed',
        'delivery_mode' => 'in_person',
        'status' => 'published',
        'is_public' => true,
        'published_at' => now(),
    ]);

    Event::query()->create([
        'user_id' => $author->id,
        'title' => 'Bozza',
        'slug' => 'slam-bozza',
        'description' => 'Non ancora pubblico.',
        'schedule_mode' => 'fixed',
        'delivery_mode' => 'in_person',
        'status' => 'draft',
        'is_public' => true,
    ]);

    Event::query()->create([
        'user_id' => $author->id,
        'title' => 'Privato',
        'slug' => 'slam-privato',
        'description' => 'Solo invitati.',
        'schedule_mode' => 'fixed',
        'delivery_mode' => 'in_person',
        'status' => 'published',
        'is_public' => false,
        'published_at' => now(),
    ]);

    Poem::query()->create([
        'user_id' => $author->id,
        'title' => 'Notte',
        'slug' => 'notte-pubblica',
        'content' => 'Verso.',
        'language' => 'it',
        'status' => 'published',
        'is_public' => true,
        'published_at' => now(),
    ]);

    Article::query()->create([
        'user_id' => $author->id,
        'title' => 'Voci',
        'slug' => 'voci-pubbliche',
        'content' => '<p>Testo</p>',
        'language' => 'it',
        'status' => 'published',
        'is_public' => true,
        'published_at' => now(),
    ]);

    $media = MediaItem::query()->create([
        'user_id' => $author->id,
        'type' => 'photo',
        'title' => 'Palco',
        'visibility' => 'public',
        'status' => 'ready',
        'file_path' => 'media/photos/palco.jpg',
        'uploaded_at' => now(),
        'published_at' => now(),
    ]);

    $hidden = User::factory()->create();
    $hidden->profile()->update(['nickname' => 'nascosto', 'is_public' => false]);

    $this->get('/api/v1/sitemap.xml')
        ->assertOk()
        ->assertHeader('Content-Type', 'application/xml; charset=UTF-8')
        ->assertSee('https://slamin.it/events/slam-pubblico', false);

    $this->get('/sitemap.xml')
        ->assertOk()
        ->assertHeader('Content-Type', 'application/xml; charset=UTF-8')
        ->assertSee('https://slamin.it/', false)
        ->assertSee('https://slamin.it/events', false)
        ->assertSee('https://slamin.it/events/slam-pubblico', false)
        ->assertSee('https://slamin.it/poems/notte-pubblica', false)
        ->assertSee('https://slamin.it/articles/voci-pubbliche', false)
        ->assertSee('https://slamin.it/media/'.$media->id, false)
        ->assertSee('https://slamin.it/u/giada', false)
        ->assertSee('https://slamin.it/terms', false)
        ->assertSee('https://slamin.it/privacy', false)
        ->assertDontSee('slam-bozza', false)
        ->assertDontSee('slam-privato', false)
        ->assertDontSee('/groups', false)
        ->assertDontSee('nascosto', false);
});

it('points robots.txt at the public sitemap', function () {
    config(['app.frontend_url' => 'https://slamin.it']);

    $this->get('/robots.txt')
        ->assertOk()
        ->assertHeader('Content-Type', 'text/plain; charset=UTF-8')
        ->assertSee('Sitemap: https://slamin.it/api/v1/sitemap.xml', false);
});
