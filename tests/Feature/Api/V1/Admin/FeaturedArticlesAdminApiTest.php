<?php

use Domain\Article\Models\Article;
use Domain\Article\Models\ArticleCategory;
use Domain\User\Models\Role;
use Domain\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

function featuredArticlesAdmin(): User
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

function featuredArticlesMake(
    User $author,
    int $categoryId,
    string $title,
    string $slug,
    string $status = 'published',
    bool $isPublic = true,
): Article {
    return Article::query()->create([
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
        'is_featured' => false,
        'featured_rank' => 0,
    ]);
}

it('lets admins search published articles and sync homepage featured order', function () {
    $admin = featuredArticlesAdmin();
    $author = User::factory()->poetOrganizer()->create(['email_verified_at' => now()]);
    $category = ArticleCategory::query()->create([
        'slug' => 'news',
        'is_active' => true,
        'sort_order' => 1,
    ]);
    $category->translations()->create(['locale' => 'it', 'name' => 'News']);

    $first = featuredArticlesMake($author, $category->id, 'Primo piano', 'primo-piano');
    $second = featuredArticlesMake($author, $category->id, 'Secondo piano', 'secondo-piano');
    featuredArticlesMake($author, $category->id, 'Bozza', 'bozza', 'draft');
    featuredArticlesMake($author, $category->id, 'Privato', 'privato', 'published', false);

    Sanctum::actingAs($admin);

    $this->getJson('/api/v1/admin/articles?search=piano')
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('data.0.is_featured', false);

    $this->putJson('/api/v1/admin/featured-articles', [
        'article_ids' => [$second->id, $first->id],
    ])
        ->assertOk()
        ->assertJsonPath('data.articles.0.slug', 'secondo-piano')
        ->assertJsonPath('data.articles.0.is_featured', true)
        ->assertJsonPath('data.articles.0.featured_rank', 1)
        ->assertJsonPath('data.articles.1.slug', 'primo-piano')
        ->assertJsonPath('data.articles.1.featured_rank', 2);

    $this->getJson('/api/v1/admin/featured-articles')
        ->assertOk()
        ->assertJsonCount(2, 'data.articles')
        ->assertJsonPath('data.articles.0.slug', 'secondo-piano');

    $this->getJson('/api/v1/featured-articles?per_page=10')
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('data.0.slug', 'secondo-piano')
        ->assertJsonPath('data.1.slug', 'primo-piano');

    $this->putJson('/api/v1/admin/featured-articles', [
        'article_ids' => [],
    ])
        ->assertOk()
        ->assertJsonCount(0, 'data.articles');
});

it('rejects featuring draft or private articles and forbids non-admins', function () {
    $author = User::factory()->poetOrganizer()->create(['email_verified_at' => now()]);
    $category = ArticleCategory::query()->create([
        'slug' => 'news',
        'is_active' => true,
        'sort_order' => 1,
    ]);
    $draft = featuredArticlesMake($author, $category->id, 'Bozza', 'bozza-feat', 'draft');

    Sanctum::actingAs($author);
    $this->putJson('/api/v1/admin/featured-articles', [
        'article_ids' => [$draft->id],
    ])->assertForbidden();

    Sanctum::actingAs(featuredArticlesAdmin());
    $this->putJson('/api/v1/admin/featured-articles', [
        'article_ids' => [$draft->id],
    ])
        ->assertUnprocessable()
        ->assertJsonPath('errors.fields.article_ids.0', __('admin.featured_articles.invalid'));
});
