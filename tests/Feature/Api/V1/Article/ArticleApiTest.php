<?php

use Domain\Article\Models\Article;
use Domain\Article\Models\ArticleCategory;
use Domain\User\Models\Role;
use Domain\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

function articleAuthor(): User
{
    return User::factory()->poetOrganizer()->create();
}

function articleModerator(): User
{
    $user = User::factory()->poetOrganizer()->create();
    $role = Role::query()->firstOrCreate(['name' => 'moderator'], ['label' => 'Moderator']);
    $user->roles()->syncWithoutDetaching([$role->id]);

    return $user;
}

it('creates an article and auto publishes it by default', function () {
    $user = articleAuthor();
    Sanctum::actingAs($user);

    $response = $this->postJson('/api/v1/articles', [
        'title' => 'First article',
        'content' => 'Article body',
        'status' => 'published',
        'language' => 'it',
        'is_public' => true,
    ]);

    $response
        ->assertOk()
        ->assertJsonPath('data.article.slug', 'first-article')
        ->assertJsonPath('data.article.status', 'published');

    $this->assertDatabaseHas('articles', [
        'slug' => 'first-article',
        'status' => 'published',
    ]);
});

it('sanitizes rich text when creating an article', function () {
    Sanctum::actingAs(articleAuthor());

    $this->postJson('/api/v1/articles', [
        'title' => 'Formatted article',
        'content' => '<h2 style="position:fixed; text-align:center" onclick="alert(1)">Hello <strong>world</strong></h2><script>alert(1)</script><a href="javascript:alert(1)">Bad link</a>',
        'status' => 'draft',
        'language' => 'it',
        'is_public' => true,
    ])->assertOk();

    $content = Article::query()->where('slug', 'formatted-article')->value('content');

    expect($content)
        ->toContain('<h2 data-text-align="center">')
        ->toContain('<strong>world</strong>')
        ->not->toContain('script')
        ->not->toContain('onclick')
        ->not->toContain('style=')
        ->not->toContain('javascript:');
});

it('lists article categories publicly', function () {
    $category = ArticleCategory::query()->create([
        'slug' => 'annunci',
        'is_active' => true,
        'sort_order' => 1,
    ]);
    $category->translations()->create([
        'locale' => 'it',
        'name' => 'Annunci',
    ]);

    $response = $this->getJson('/api/v1/article-categories');

    $response
        ->assertOk()
        ->assertJsonCount(1, 'data.categories')
        ->assertJsonPath('data.categories.0.slug', 'annunci');
});

it('allows article likes for authenticated users', function () {
    $author = articleAuthor();
    $user = articleAuthor();
    Sanctum::actingAs($user);

    $article = Article::query()->create([
        'user_id' => $author->id,
        'title' => 'Likeable article',
        'slug' => 'likeable-article',
        'content' => 'Body',
        'language' => 'it',
        'status' => 'published',
        'is_public' => true,
        'published_at' => now(),
    ]);

    $response = $this->postJson("/api/v1/articles/{$article->id}/like");

    $response
        ->assertOk()
        ->assertJsonPath('data.article.id', $article->id);
});

it('allows moderators to approve a pending review article', function () {
    Sanctum::actingAs(articleModerator());

    $article = Article::query()->create([
        'user_id' => articleAuthor()->id,
        'title' => 'Pending article',
        'slug' => 'pending-article',
        'content' => 'Body',
        'language' => 'it',
        'status' => 'pending_review',
        'is_public' => true,
    ]);

    $response = $this->postJson("/api/v1/moderation/articles/{$article->id}/approve", [
        'note' => 'Ready to go live',
    ]);

    $response
        ->assertOk()
        ->assertJsonPath('data.article.status', 'published');

    $this->assertDatabaseHas('articles', [
        'id' => $article->id,
        'status' => 'published',
    ]);
});
