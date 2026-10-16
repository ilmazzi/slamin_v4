<?php

use Domain\Article\Enums\ArticleStatus;
use Domain\Article\Models\Article;
use Domain\Comment\Models\Comment;
use Domain\Engagement\Models\Follow;
use Domain\Engagement\Models\Like;
use Domain\Media\Enums\MediaStatus;
use Domain\Media\Enums\MediaType;
use Domain\Media\Enums\MediaVisibility;
use Domain\Media\Models\MediaItem;
use Domain\Poetry\Enums\PoemStatus;
use Domain\Poetry\Models\Poem;
use Domain\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

it('returns statistics received on public published content', function () {
    $owner = User::factory()->create();
    $visitor = User::factory()->create();

    $poem = Poem::query()->create([
        'user_id' => $owner->id,
        'title' => 'The visible poem',
        'slug' => 'the-visible-poem',
        'content' => 'A poem',
        'status' => PoemStatus::Published,
        'is_public' => true,
        'published_at' => now(),
    ]);
    $poem->forceFill(['views_count' => 42])->save();

    $media = MediaItem::query()->create([
        'user_id' => $owner->id,
        'type' => MediaType::Video,
        'title' => 'A public video',
        'status' => MediaStatus::Ready,
        'visibility' => MediaVisibility::Public,
        'published_at' => now(),
    ]);
    $media->forceFill(['views_count' => 10])->save();

    $draftArticle = Article::query()->create([
        'user_id' => $owner->id,
        'title' => 'A draft',
        'slug' => 'a-draft',
        'content' => 'Hidden',
        'status' => ArticleStatus::Draft,
        'is_public' => true,
    ]);
    $draftArticle->forceFill(['views_count' => 999])->save();

    Like::query()->create([
        'user_id' => $visitor->id,
        'likeable_type' => $poem->getMorphClass(),
        'likeable_id' => $poem->id,
    ]);
    Like::query()->create([
        'user_id' => $owner->id,
        'likeable_type' => $poem->getMorphClass(),
        'likeable_id' => $poem->id,
    ]);
    Like::query()->create([
        'user_id' => $visitor->id,
        'likeable_type' => $draftArticle->getMorphClass(),
        'likeable_id' => $draftArticle->id,
    ]);
    Comment::query()->create([
        'user_id' => $visitor->id,
        'commentable_type' => $poem->getMorphClass(),
        'commentable_id' => $poem->id,
        'content' => 'Great',
    ]);
    Follow::query()->create([
        'user_id' => $visitor->id,
        'followable_type' => $owner->getMorphClass(),
        'followable_id' => $owner->id,
    ]);

    Sanctum::actingAs($owner);

    $response = $this->getJson('/api/v1/me/statistics');

    $response
        ->assertOk()
        ->assertJsonPath('data.totals.views_received', 52)
        ->assertJsonPath('data.totals.likes_received', 1)
        ->assertJsonPath('data.totals.comments_received', 1)
        ->assertJsonPath('data.totals.followers', 1)
        ->assertJsonPath('data.publications.poems', 1)
        ->assertJsonPath('data.publications.articles', 0)
        ->assertJsonPath('data.publications.events', 0)
        ->assertJsonPath('data.publications.media', 1)
        ->assertJsonPath('data.top_by_views.0.type', 'poem')
        ->assertJsonPath('data.top_by_views.0.url', '/poems/the-visible-poem')
        ->assertJsonPath('data.top_by_likes.0.likes_count', 2);

    $this->getJson('/api/v1/me/dashboard-widgets')
        ->assertOk()
        ->assertJsonPath('data.widgets.2.value', 52)
        ->assertJsonPath('data.widgets.3.value', 1);
});

it('requires authentication for personal statistics', function () {
    $this->getJson('/api/v1/me/statistics')->assertUnauthorized();
});
