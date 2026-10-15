<?php

use Domain\Help\Models\HelpArticle;
use Domain\Help\Models\HelpCategory;
use Domain\User\Models\Role;
use Domain\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

function helpAdminUser(): User
{
    $user = User::factory()->poetOrganizer()->create();
    $role = Role::query()->firstOrCreate(
        ['name' => 'admin'],
        ['label' => 'Admin']
    );
    $user->roles()->syncWithoutDetaching([$role->id]);

    return $user;
}

function helpMemberUser(): User
{
    return User::factory()->create();
}

function makeHelpCategory(string $slug = 'account'): HelpCategory
{
    $category = HelpCategory::query()->create([
        'slug' => $slug,
        'is_active' => true,
        'sort_order' => 1,
    ]);
    $category->translations()->createMany([
        ['locale' => 'it', 'name' => 'Account'],
        ['locale' => 'en', 'name' => 'Account'],
    ]);

    return $category;
}

it('lists published help articles for guests', function () {
    $category = makeHelpCategory();
    $published = HelpArticle::query()->create([
        'help_category_id' => $category->id,
        'slug' => 'come-creare-un-account',
        'is_published' => true,
        'sort_order' => 1,
    ]);
    $published->translations()->create([
        'locale' => 'it',
        'title' => 'Come creare un account',
        'body' => 'Testo della guida.',
    ]);

    HelpArticle::query()->create([
        'help_category_id' => $category->id,
        'slug' => 'bozza-nascosta',
        'is_published' => false,
        'sort_order' => 2,
    ])->translations()->create([
        'locale' => 'it',
        'title' => 'Bozza',
        'body' => 'Non visibile.',
    ]);

    $response = $this->getJson('/api/v1/help');

    $response
        ->assertOk()
        ->assertJsonPath('data.articles.0.slug', 'come-creare-un-account')
        ->assertJsonPath('data.articles.0.title', 'Come creare un account')
        ->assertJsonPath('data.categories.0.slug', 'account')
        ->assertJsonCount(1, 'data.articles');
});

it('filters public help articles by category and search', function () {
    $account = makeHelpCategory('account');
    $events = makeHelpCategory('events');
    $events->translations()->updateOrCreate(['locale' => 'it'], ['name' => 'Eventi']);

    HelpArticle::query()->create([
        'help_category_id' => $account->id,
        'slug' => 'come-creare-un-account',
        'is_published' => true,
        'sort_order' => 1,
    ])->translations()->create([
        'locale' => 'it',
        'title' => 'Come creare un account',
        'body' => 'Registrati in alto a destra.',
    ]);

    HelpArticle::query()->create([
        'help_category_id' => $events->id,
        'slug' => 'come-creare-un-evento',
        'is_published' => true,
        'sort_order' => 2,
    ])->translations()->create([
        'locale' => 'it',
        'title' => 'Come creare un evento',
        'body' => 'Apri Eventi e compila il form.',
    ]);

    $this->getJson('/api/v1/help?category=events')
        ->assertOk()
        ->assertJsonCount(1, 'data.articles')
        ->assertJsonPath('data.articles.0.slug', 'come-creare-un-evento');

    $this->getJson('/api/v1/help?q=Registrati')
        ->assertOk()
        ->assertJsonCount(1, 'data.articles')
        ->assertJsonPath('data.articles.0.slug', 'come-creare-un-account');
});

it('allows an admin to create update and delete help content', function () {
    Sanctum::actingAs(helpAdminUser());

    $createCategory = $this->postJson('/api/v1/admin/help/categories', [
        'slug' => 'account',
        'is_active' => true,
        'sort_order' => 1,
        'translations' => [
            ['locale' => 'it', 'name' => 'Account'],
            ['locale' => 'en', 'name' => 'Account'],
        ],
    ]);

    $createCategory
        ->assertCreated()
        ->assertJsonPath('data.category.slug', 'account');

    $categoryId = $createCategory->json('data.category.id');

    $createArticle = $this->postJson('/api/v1/admin/help/articles', [
        'help_category_id' => $categoryId,
        'slug' => 'come-creare-un-account',
        'is_published' => true,
        'sort_order' => 1,
        'translations' => [
            ['locale' => 'it', 'title' => 'Come creare un account', 'body' => 'Testo IT.'],
            ['locale' => 'en', 'title' => 'How to create an account', 'body' => 'English body.'],
        ],
    ]);

    $createArticle
        ->assertCreated()
        ->assertJsonPath('data.article.slug', 'come-creare-un-account')
        ->assertJsonCount(2, 'data.article.translations');

    $articleId = $createArticle->json('data.article.id');

    $this->patchJson("/api/v1/admin/help/articles/{$articleId}", [
        'help_category_id' => $categoryId,
        'slug' => 'come-creare-un-account',
        'is_published' => false,
        'sort_order' => 3,
        'translations' => [
            ['locale' => 'it', 'title' => 'Come creare un account', 'body' => 'Testo aggiornato.'],
        ],
    ])
        ->assertOk()
        ->assertJsonPath('data.article.is_published', false)
        ->assertJsonCount(1, 'data.article.translations');

    $this->deleteJson("/api/v1/admin/help/articles/{$articleId}")->assertOk();
    $this->assertDatabaseMissing('help_articles', ['id' => $articleId]);

    $this->deleteJson("/api/v1/admin/help/categories/{$categoryId}")->assertOk();
    $this->assertDatabaseMissing('help_categories', ['id' => $categoryId]);
});

it('forbids a non admin from writing help content', function () {
    Sanctum::actingAs(helpMemberUser());

    $this->postJson('/api/v1/admin/help/categories', [
        'slug' => 'account',
        'translations' => [
            ['locale' => 'it', 'name' => 'Account'],
        ],
    ])->assertForbidden();
});

it('rejects help translations for unsupported locales', function () {
    Sanctum::actingAs(helpAdminUser());

    $response = $this->postJson('/api/v1/admin/help/categories', [
        'slug' => 'unsupported-language',
        'translations' => [
            ['locale' => 'xx', 'name' => 'Unsupported'],
        ],
    ]);

    $response->assertUnprocessable();
    expect($response->json('errors.fields'))->toHaveKey('translations.0.locale');
});

it('prevents deleting a help category that still has articles', function () {
    Sanctum::actingAs(helpAdminUser());

    $category = makeHelpCategory();
    HelpArticle::query()->create([
        'help_category_id' => $category->id,
        'slug' => 'guida-usata',
        'is_published' => true,
        'sort_order' => 1,
    ])->translations()->create([
        'locale' => 'it',
        'title' => 'Guida usata',
        'body' => 'Corpo',
    ]);

    $this->deleteJson("/api/v1/admin/help/categories/{$category->id}")
        ->assertStatus(422)
        ->assertJsonPath('errors.message', __('help.errors.category_in_use'));
});
