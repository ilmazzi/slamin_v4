<?php

use Domain\Engagement\Models\Bookmark;
use Domain\Engagement\Models\Like;
use Domain\Poetry\Models\Poem;
use Domain\Poetry\Models\PoemCategory;
use Domain\Poetry\Models\PoemType;
use Domain\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

function poetryWorkflowOrganizer(): User
{
    return User::factory()->poetOrganizer()->create([
        'email_verified_at' => now(),
    ]);
}

function poetryWorkflowAudience(): User
{
    return User::factory()->create([
        'email_verified_at' => now(),
    ]);
}

function poetryWorkflowCategory(string $slug): PoemCategory
{
    $category = PoemCategory::query()->create([
        'slug' => $slug,
        'is_active' => true,
        'sort_order' => 1,
    ]);

    $category->translations()->create([
        'locale' => 'it',
        'name' => "Categoria {$slug}",
    ]);

    return $category;
}

function poetryWorkflowType(string $slug): PoemType
{
    $type = PoemType::query()->create([
        'slug' => $slug,
        'is_active' => true,
        'sort_order' => 1,
    ]);

    $type->translations()->create([
        'locale' => 'it',
        'name' => "Tipo {$slug}",
    ]);

    return $type;
}

function poetryWorkflowPoem(
    User $author,
    PoemCategory $category,
    PoemType $type,
    array $attributes = []
): Poem {
    return Poem::query()->create(array_merge([
        'user_id' => $author->id,
        'poem_category_id' => $category->id,
        'poem_type_id' => $type->id,
        'title' => 'Poem ' . fake()->unique()->word(),
        'slug' => fake()->unique()->slug(),
        'content' => 'Test content',
        'description' => 'Test description',
        'language' => 'it',
        'status' => 'published',
        'is_public' => true,
        'published_at' => now(),
        'is_featured' => false,
        'featured_rank' => 0,
    ], $attributes));
}

it('lists only published public poems', function () {
    $author = poetryWorkflowOrganizer();
    $category = poetryWorkflowCategory('pubblico');
    $type = poetryWorkflowType('testo');

    poetryWorkflowPoem($author, $category, $type, [
        'slug' => 'public-poem',
        'status' => 'published',
        'is_public' => true,
    ]);
    poetryWorkflowPoem($author, $category, $type, [
        'slug' => 'draft-poem',
        'status' => 'draft',
        'is_public' => true,
        'published_at' => null,
    ]);
    poetryWorkflowPoem($author, $category, $type, [
        'slug' => 'private-poem',
        'status' => 'published',
        'is_public' => false,
    ]);

    $this->getJson('/api/v1/poems')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.slug', 'public-poem');
});

it('shows own draft poem but hides it from other users', function () {
    $author = poetryWorkflowOrganizer();
    $viewer = poetryWorkflowAudience();
    $category = poetryWorkflowCategory('bozza');
    $type = poetryWorkflowType('spoken-word');

    poetryWorkflowPoem($author, $category, $type, [
        'slug' => 'my-draft-poem',
        'status' => 'draft',
        'is_public' => false,
        'published_at' => null,
    ]);

    Sanctum::actingAs($author);
    $this->getJson('/api/v1/poems/my-draft-poem')
        ->assertOk()
        ->assertJsonPath('data.poem.slug', 'my-draft-poem');

    Sanctum::actingAs($viewer);
    $this->getJson('/api/v1/poems/my-draft-poem')
        ->assertNotFound();
});

it('allows like and bookmark only on public published poems', function () {
    $author = poetryWorkflowOrganizer();
    $audience = poetryWorkflowAudience();
    $category = poetryWorkflowCategory('engagement');
    $type = poetryWorkflowType('orale');

    $publicPoem = poetryWorkflowPoem($author, $category, $type, [
        'slug' => 'engage-me',
    ]);
    $hiddenPoem = poetryWorkflowPoem($author, $category, $type, [
        'slug' => 'hidden-me',
        'status' => 'draft',
        'is_public' => false,
        'published_at' => null,
    ]);

    Sanctum::actingAs($audience);

    $this->postJson("/api/v1/poems/{$publicPoem->id}/like")->assertOk();
    $this->postJson("/api/v1/poems/{$publicPoem->id}/bookmark")->assertOk();

    $this->assertDatabaseHas('likes', [
        'user_id' => $audience->id,
        'likeable_id' => $publicPoem->id,
    ]);
    $this->assertDatabaseHas('bookmarks', [
        'user_id' => $audience->id,
        'bookmarkable_id' => $publicPoem->id,
    ]);

    $this->postJson("/api/v1/poems/{$hiddenPoem->id}/like")->assertForbidden();
    $this->postJson("/api/v1/poems/{$hiddenPoem->id}/bookmark")->assertForbidden();
});

it('filters featured poems by category and type', function () {
    $author = poetryWorkflowOrganizer();
    $categoryA = poetryWorkflowCategory('amore');
    $categoryB = poetryWorkflowCategory('sociale');
    $typeA = poetryWorkflowType('haiku');
    $typeB = poetryWorkflowType('free-verse');

    poetryWorkflowPoem($author, $categoryA, $typeA, [
        'slug' => 'featured-target',
        'is_featured' => true,
        'featured_rank' => 1,
    ]);
    poetryWorkflowPoem($author, $categoryA, $typeB, [
        'slug' => 'featured-other-type',
        'is_featured' => true,
        'featured_rank' => 2,
    ]);
    poetryWorkflowPoem($author, $categoryB, $typeA, [
        'slug' => 'featured-other-category',
        'is_featured' => true,
        'featured_rank' => 3,
    ]);

    $this->getJson("/api/v1/featured-poems?poem_category_id={$categoryA->id}&poem_type_id={$typeA->id}")
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.slug', 'featured-target');
});

it('accepts string boolean for remove_image while updating poem', function () {
    Storage::fake('public');

    $author = poetryWorkflowOrganizer();
    $category = poetryWorkflowCategory('update');
    $type = poetryWorkflowType('lyric');

    $poem = poetryWorkflowPoem($author, $category, $type, [
        'status' => 'draft',
        'published_at' => null,
    ]);

    $oldImagePath = UploadedFile::fake()->image('old-poem.jpg')->store('poems', 'public');
    $poem->update(['image_path' => $oldImagePath]);

    Sanctum::actingAs($author);
    $this->patchJson("/api/v1/poems/{$poem->id}", [
        'title' => $poem->title,
        'content' => $poem->content,
        'description' => $poem->description,
        'language' => 'it',
        'status' => 'draft',
        'is_public' => true,
        'poem_category_id' => $category->id,
        'poem_type_id' => $type->id,
        'remove_image' => 'true',
    ])
        ->assertOk()
        ->assertJsonPath('data.poem.image_path', null);

    expect(Storage::disk('public')->exists($oldImagePath))->toBeFalse();
});

it('validates per_page bounds in my poems endpoint', function () {
    $author = poetryWorkflowOrganizer();
    $category = poetryWorkflowCategory('mine');
    $type = poetryWorkflowType('mine-type');

    poetryWorkflowPoem($author, $category, $type);

    Sanctum::actingAs($author);
    $this->getJson('/api/v1/my/poems?per_page=1000')
        ->assertStatus(422);
});

