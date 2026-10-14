<?php

use Domain\Article\Models\Article;
use Domain\Article\Models\ArticleCategory;
use Domain\Event\Models\Event;
use Domain\Event\Models\EventCategory;
use Domain\Media\Models\MediaItem;
use Domain\Poetry\Models\Poem;
use Domain\Poetry\Models\PoemCategory;
use Domain\Poetry\Models\PoemType;
use Domain\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

function profileWorksAuthor(string $nickname): User
{
    $user = User::factory()->poetOrganizer()->create([
        'email_verified_at' => now(),
    ]);
    $user->profile->update([
        'nickname' => $nickname,
        'is_public' => true,
    ]);

    return $user->fresh('profile');
}

function profileWorksPoemCategory(): PoemCategory
{
    $category = PoemCategory::query()->create([
        'slug' => 'profile-works',
        'is_active' => true,
        'sort_order' => 1,
    ]);
    $category->translations()->create([
        'locale' => 'it',
        'name' => 'Profilo',
    ]);

    return $category;
}

function profileWorksPoemType(): PoemType
{
    $type = PoemType::query()->create([
        'slug' => 'profile-works-type',
        'is_active' => true,
        'sort_order' => 1,
    ]);
    $type->translations()->create([
        'locale' => 'it',
        'name' => 'Testo',
    ]);

    return $type;
}

function profileWorksPoem(User $author, PoemCategory $category, PoemType $type, array $attributes = []): Poem
{
    return Poem::query()->create(array_merge([
        'user_id' => $author->id,
        'poem_category_id' => $category->id,
        'poem_type_id' => $type->id,
        'title' => 'Poesia profilo',
        'slug' => 'poesia-profilo',
        'content' => 'Verso',
        'description' => 'Desc',
        'language' => 'it',
        'status' => 'published',
        'is_public' => true,
        'published_at' => now(),
        'is_featured' => false,
        'featured_rank' => 0,
    ], $attributes));
}

it('lists only published public poems for a profile nickname', function () {
    $author = profileWorksAuthor('poet_alpha');
    $other = profileWorksAuthor('poet_beta');
    $category = profileWorksPoemCategory();
    $type = profileWorksPoemType();

    profileWorksPoem($author, $category, $type, [
        'title' => 'Pubblica alpha',
        'slug' => 'pubblica-alpha',
    ]);
    profileWorksPoem($author, $category, $type, [
        'title' => 'Bozza alpha',
        'slug' => 'bozza-alpha',
        'status' => 'draft',
        'is_public' => false,
        'published_at' => null,
    ]);
    profileWorksPoem($other, $category, $type, [
        'title' => 'Pubblica beta',
        'slug' => 'pubblica-beta',
    ]);

    $this->getJson('/api/v1/poems?nickname=poet_alpha')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.slug', 'pubblica-alpha');
});

it('lets the owner list drafts, publish them and delete published poems', function () {
    $author = profileWorksAuthor('poet_owner');
    $viewer = User::factory()->create(['email_verified_at' => now()]);
    $category = profileWorksPoemCategory();
    $type = profileWorksPoemType();

    $draft = profileWorksPoem($author, $category, $type, [
        'title' => 'Bozza da pubblicare',
        'slug' => 'bozza-da-pubblicare',
        'status' => 'draft',
        'is_public' => false,
        'published_at' => null,
    ]);
    $published = profileWorksPoem($author, $category, $type, [
        'title' => 'Da cancellare',
        'slug' => 'da-cancellare',
    ]);

    Sanctum::actingAs($author);
    $this->getJson('/api/v1/my/poems')
        ->assertOk()
        ->assertJsonCount(2, 'data');

    $this->patchJson("/api/v1/poems/{$draft->id}", [
        'title' => $draft->title,
        'content' => $draft->content,
        'description' => $draft->description,
        'language' => 'it',
        'poem_category_id' => $category->id,
        'poem_type_id' => $type->id,
        'status' => 'published',
        'is_public' => true,
    ])
        ->assertOk()
        ->assertJsonPath('data.poem.status', 'published');

    $this->deleteJson("/api/v1/poems/{$published->id}")
        ->assertOk();

    expect(Poem::query()->whereKey($published->id)->exists())->toBeFalse();

    Sanctum::actingAs($viewer);
    $this->deleteJson("/api/v1/poems/{$draft->id}")->assertForbidden();
});

it('filters public articles events and media by nickname and lets the owner publish an event', function () {
    $author = profileWorksAuthor('creator_one');
    $other = profileWorksAuthor('creator_two');

    $articleCategory = ArticleCategory::query()->create([
        'slug' => 'profile-news',
        'is_active' => true,
        'sort_order' => 1,
    ]);
    $articleCategory->translations()->create(['locale' => 'it', 'name' => 'News']);

    Article::query()->create([
        'user_id' => $author->id,
        'article_category_id' => $articleCategory->id,
        'title' => 'Articolo mio',
        'slug' => 'articolo-mio',
        'content' => 'Body',
        'excerpt' => 'Excerpt',
        'language' => 'it',
        'status' => 'published',
        'is_public' => true,
        'published_at' => now(),
        'is_featured' => false,
        'featured_rank' => 0,
    ]);
    Article::query()->create([
        'user_id' => $other->id,
        'article_category_id' => $articleCategory->id,
        'title' => 'Articolo altro',
        'slug' => 'articolo-altro',
        'content' => 'Body',
        'excerpt' => 'Excerpt',
        'language' => 'it',
        'status' => 'published',
        'is_public' => true,
        'published_at' => now(),
        'is_featured' => false,
        'featured_rank' => 0,
    ]);

    $eventCategory = EventCategory::query()->create([
        'slug' => 'profile-open-mic',
        'is_active' => true,
        'sort_order' => 1,
    ]);
    $eventCategory->translations()->create(['locale' => 'it', 'name' => 'Open mic']);

    $draftEvent = Event::query()->create([
        'user_id' => $author->id,
        'event_category_id' => $eventCategory->id,
        'title' => 'Evento bozza',
        'slug' => 'evento-bozza',
        'description' => 'Desc',
        'schedule_mode' => 'fixed',
        'delivery_mode' => 'online',
        'status' => 'draft',
        'is_public' => true,
    ]);
    Event::query()->create([
        'user_id' => $author->id,
        'event_category_id' => $eventCategory->id,
        'title' => 'Evento pubblicato',
        'slug' => 'evento-pubblicato',
        'description' => 'Desc',
        'schedule_mode' => 'fixed',
        'delivery_mode' => 'online',
        'status' => 'published',
        'is_public' => true,
        'published_at' => now(),
    ]);
    Event::query()->create([
        'user_id' => $other->id,
        'event_category_id' => $eventCategory->id,
        'title' => 'Evento altro',
        'slug' => 'evento-altro',
        'description' => 'Desc',
        'schedule_mode' => 'fixed',
        'delivery_mode' => 'online',
        'status' => 'published',
        'is_public' => true,
        'published_at' => now(),
    ]);

    MediaItem::query()->create([
        'user_id' => $author->id,
        'type' => 'photo',
        'title' => 'Foto mia',
        'visibility' => 'public',
        'status' => 'ready',
        'file_path' => 'media/photos/mine.jpg',
        'uploaded_at' => now(),
        'published_at' => now(),
    ]);
    MediaItem::query()->create([
        'user_id' => $other->id,
        'type' => 'photo',
        'title' => 'Foto altra',
        'visibility' => 'public',
        'status' => 'ready',
        'file_path' => 'media/photos/other.jpg',
        'uploaded_at' => now(),
        'published_at' => now(),
    ]);
    MediaItem::query()->create([
        'user_id' => $author->id,
        'type' => 'photo',
        'title' => 'Foto privata',
        'visibility' => 'private',
        'status' => 'ready',
        'file_path' => 'media/photos/private.jpg',
        'uploaded_at' => now(),
    ]);

    $this->getJson('/api/v1/articles?nickname=creator_one')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.slug', 'articolo-mio');

    $this->getJson('/api/v1/events?nickname=creator_one')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.slug', 'evento-pubblicato');

    $this->getJson('/api/v1/media?nickname=creator_one')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.title', 'Foto mia');

    Sanctum::actingAs($author);
    $this->patchJson("/api/v1/events/{$draftEvent->id}", [
        'status' => 'published',
    ])
        ->assertOk()
        ->assertJsonPath('data.event.status', 'published');

    $this->getJson('/api/v1/events?nickname=creator_one')
        ->assertOk()
        ->assertJsonCount(2, 'data');

    $this->deleteJson("/api/v1/events/{$draftEvent->id}")
        ->assertOk();

    expect(Event::query()->whereKey($draftEvent->id)->exists())->toBeFalse();
});
