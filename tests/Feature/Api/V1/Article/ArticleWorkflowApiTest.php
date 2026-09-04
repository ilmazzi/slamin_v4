<?php

use Domain\Article\Models\Article;
use Domain\Article\Models\ArticleCategory;
use Domain\Engagement\Models\Bookmark;
use Domain\Engagement\Models\Like;
use Domain\User\Models\Role;
use Domain\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

function articleWorkflowAuthor(): User
{
    return User::factory()->poetOrganizer()->create([
        'email_verified_at' => now(),
    ]);
}

function articleWorkflowReader(): User
{
    return User::factory()->create([
        'email_verified_at' => now(),
    ]);
}

function articleWorkflowAdmin(): User
{
    $user = User::factory()->poetOrganizer()->create([
        'email_verified_at' => now(),
    ]);
    $role = Role::query()->firstOrCreate(
        ['name' => 'admin'],
        ['label' => 'Admin']
    );
    $user->roles()->syncWithoutDetaching([$role->id]);

    return $user;
}

function articleWorkflowCategory(string $slug = 'news', bool $isActive = true, int $sortOrder = 1): ArticleCategory
{
    $category = ArticleCategory::query()->create([
        'slug' => $slug,
        'is_active' => $isActive,
        'sort_order' => $sortOrder,
    ]);
    $category->translations()->create([
        'locale' => 'it',
        'name' => ucfirst($slug),
    ]);

    return $category;
}

function articleWorkflowCreate(
    User $author,
    ?int $categoryId,
    string $title,
    string $slug,
    string $status = 'published',
    bool $isPublic = true,
    bool $isFeatured = false,
    ?int $featuredRank = null
): Article {
    /** @var Article $article */
    $article = Article::query()->create([
        'user_id' => $author->id,
        'article_category_id' => $categoryId,
        'title' => $title,
        'slug' => $slug,
        'content' => 'Body',
        'excerpt' => 'Excerpt',
        'language' => 'it',
        'status' => $status,
        'is_public' => $isPublic,
        'published_at' => $status === 'published' ? now() : null,
        'is_featured' => $isFeatured,
        'featured_rank' => $featuredRank,
    ]);

    return $article;
}

it('lists only published public articles', function () {
    $author = articleWorkflowAuthor();
    $category = articleWorkflowCategory();

    articleWorkflowCreate($author, $category->id, 'Public', 'public-article', 'published', true);
    articleWorkflowCreate($author, $category->id, 'Draft', 'draft-article', 'draft', true);
    articleWorkflowCreate($author, $category->id, 'Private', 'private-article', 'published', false);

    $this->getJson('/api/v1/articles')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.slug', 'public-article');
});

it('allows owner to view own draft article and hides it from others', function () {
    $author = articleWorkflowAuthor();
    $other = articleWorkflowAuthor();
    $category = articleWorkflowCategory();

    articleWorkflowCreate($author, $category->id, 'Owner Draft', 'owner-draft', 'draft', false);

    Sanctum::actingAs($other);
    $this->getJson('/api/v1/articles/owner-draft')->assertNotFound();

    Sanctum::actingAs($author);
    $this->getJson('/api/v1/articles/owner-draft')
        ->assertOk()
        ->assertJsonPath('data.article.slug', 'owner-draft');
});

it('lists only my articles for authenticated poet organizer', function () {
    $owner = articleWorkflowAuthor();
    $other = articleWorkflowAuthor();
    $category = articleWorkflowCategory();

    articleWorkflowCreate($owner, $category->id, 'Mine', 'mine');
    articleWorkflowCreate($other, $category->id, 'Not mine', 'not-mine');

    Sanctum::actingAs($owner);
    $this->getJson('/api/v1/my/articles')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.slug', 'mine');
});

it('supports like and unlike for published public articles', function () {
    $author = articleWorkflowAuthor();
    $reader = articleWorkflowReader();
    $category = articleWorkflowCategory();
    $article = articleWorkflowCreate($author, $category->id, 'Like me', 'like-me');

    Sanctum::actingAs($reader);

    $this->postJson("/api/v1/articles/{$article->id}/like")
        ->assertOk()
        ->assertJsonPath('data.article.id', $article->id);

    $this->assertDatabaseHas('likes', [
        'user_id' => $reader->id,
        'likeable_id' => $article->id,
        'likeable_type' => $article->getMorphClass(),
    ]);

    $this->deleteJson("/api/v1/articles/{$article->id}/like")
        ->assertOk();

    expect(Like::query()
        ->where('user_id', $reader->id)
        ->where('likeable_id', $article->id)
        ->exists())->toBeFalse();
});

it('forbids liking and bookmarking non public or non published articles', function () {
    $author = articleWorkflowAuthor();
    $reader = articleWorkflowReader();
    $category = articleWorkflowCategory();
    $article = articleWorkflowCreate($author, $category->id, 'Hidden', 'hidden-article', 'draft', false);

    Sanctum::actingAs($reader);
    $this->postJson("/api/v1/articles/{$article->id}/like")->assertForbidden();
    $this->postJson("/api/v1/articles/{$article->id}/bookmark")->assertForbidden();
});

it('supports bookmark and unbookmark for published public articles', function () {
    $author = articleWorkflowAuthor();
    $reader = articleWorkflowReader();
    $category = articleWorkflowCategory();
    $article = articleWorkflowCreate($author, $category->id, 'Bookmark me', 'bookmark-me');

    Sanctum::actingAs($reader);

    $this->postJson("/api/v1/articles/{$article->id}/bookmark")
        ->assertOk()
        ->assertJsonPath('data.article.id', $article->id);

    $this->assertDatabaseHas('bookmarks', [
        'user_id' => $reader->id,
        'bookmarkable_id' => $article->id,
        'bookmarkable_type' => $article->getMorphClass(),
    ]);

    $this->deleteJson("/api/v1/articles/{$article->id}/bookmark")
        ->assertOk();

    expect(Bookmark::query()
        ->where('user_id', $reader->id)
        ->where('bookmarkable_id', $article->id)
        ->exists())->toBeFalse();
});

it('filters featured articles by category and rank', function () {
    $author = articleWorkflowAuthor();
    $categoryA = articleWorkflowCategory('culture', true, 1);
    $categoryB = articleWorkflowCategory('events', true, 2);

    articleWorkflowCreate($author, $categoryA->id, 'A2', 'feat-a2', 'published', true, true, 2);
    articleWorkflowCreate($author, $categoryA->id, 'A1', 'feat-a1', 'published', true, true, 1);
    articleWorkflowCreate($author, $categoryB->id, 'B1', 'feat-b1', 'published', true, true, 1);
    articleWorkflowCreate($author, $categoryA->id, 'Not featured', 'not-featured', 'published', true, false, null);

    $this->getJson("/api/v1/featured-articles?article_category_id={$categoryA->id}&per_page=10")
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('data.0.slug', 'feat-a1')
        ->assertJsonPath('data.1.slug', 'feat-a2');
});

it('supports admin article category list show and delete when unused', function () {
    $admin = articleWorkflowAdmin();
    Sanctum::actingAs($admin);

    $category = articleWorkflowCategory('press');

    $this->getJson('/api/v1/admin/article-categories')
        ->assertOk()
        ->assertJsonPath('data.categories.0.slug', 'press');

    $this->getJson("/api/v1/admin/article-categories/{$category->id}")
        ->assertOk()
        ->assertJsonPath('data.category.slug', 'press');

    $this->deleteJson("/api/v1/admin/article-categories/{$category->id}")
        ->assertOk();
});
