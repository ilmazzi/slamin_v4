<?php

use Domain\Poetry\Models\Poem;
use Domain\Poetry\Models\PoemCategory;
use Domain\Poetry\Models\PoemType;
use Domain\User\Models\Role;
use Domain\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

function adminUser(): User
{
    $user = User::factory()->poetOrganizer()->create();
    $role = Role::query()->firstOrCreate(
        ['name' => 'admin'],
        ['label' => 'Admin']
    );
    $user->roles()->syncWithoutDetaching([$role->id]);

    return $user;
}

function audienceUser(): User
{
    return User::factory()->create();
}

it('allows an admin to create a poem category with translations', function () {
    Sanctum::actingAs(adminUser());

    $response = $this->postJson('/api/v1/admin/poem-categories', [
        'slug' => 'test-category',
        'is_active' => true,
        'sort_order' => 5,
        'translations' => [
            ['locale' => 'it', 'name' => 'Categoria test'],
            ['locale' => 'en', 'name' => 'Test category'],
        ],
    ]);

    $response
        ->assertCreated()
        ->assertJsonPath('data.category.slug', 'test-category')
        ->assertJsonPath('data.category.name', 'Test category')
        ->assertJsonCount(2, 'data.category.translations');

    $this->assertDatabaseHas('poem_categories', [
        'slug' => 'test-category',
        'is_active' => true,
        'sort_order' => 5,
    ]);

    $categoryId = PoemCategory::query()->where('slug', 'test-category')->value('id');

    $this->assertDatabaseHas('poem_category_translations', [
        'poem_category_id' => $categoryId,
        'locale' => 'it',
        'name' => 'Categoria test',
    ]);

    $this->assertDatabaseHas('poem_category_translations', [
        'poem_category_id' => $categoryId,
        'locale' => 'en',
        'name' => 'Test category',
    ]);
});

it('forbids a non admin from writing poem categories', function () {
    Sanctum::actingAs(audienceUser());

    $response = $this->postJson('/api/v1/admin/poem-categories', [
        'slug' => 'forbidden-category',
        'translations' => [
            ['locale' => 'it', 'name' => 'Categoria vietata'],
        ],
    ]);

    $response->assertForbidden();
});

it('allows an admin to update poem category translations', function () {
    Sanctum::actingAs(adminUser());

    $category = PoemCategory::query()->create([
        'slug' => 'amore',
        'is_active' => true,
        'sort_order' => 1,
    ]);

    $category->translations()->createMany([
        ['locale' => 'it', 'name' => 'Amore'],
        ['locale' => 'en', 'name' => 'Love'],
    ]);

    $response = $this->patchJson("/api/v1/admin/poem-categories/{$category->id}", [
        'slug' => 'amore-updated',
        'is_active' => false,
        'sort_order' => 10,
        'translations' => [
            ['locale' => 'it', 'name' => 'Amore aggiornato'],
            ['locale' => 'fr', 'name' => 'Amour'],
        ],
    ]);

    $response
        ->assertOk()
        ->assertJsonPath('data.category.slug', 'amore-updated')
        ->assertJsonPath('data.category.is_active', false)
        ->assertJsonCount(2, 'data.category.translations');

    $this->assertDatabaseHas('poem_categories', [
        'id' => $category->id,
        'slug' => 'amore-updated',
        'is_active' => false,
        'sort_order' => 10,
    ]);

    $this->assertDatabaseHas('poem_category_translations', [
        'poem_category_id' => $category->id,
        'locale' => 'it',
        'name' => 'Amore aggiornato',
    ]);

    $this->assertDatabaseHas('poem_category_translations', [
        'poem_category_id' => $category->id,
        'locale' => 'fr',
        'name' => 'Amour',
    ]);

    $this->assertDatabaseMissing('poem_category_translations', [
        'poem_category_id' => $category->id,
        'locale' => 'en',
    ]);
});

it('prevents deleting a poem category that is already used by a poem', function () {
    Sanctum::actingAs(adminUser());

    $owner = User::factory()->poetOrganizer()->create();
    $category = PoemCategory::query()->create([
        'slug' => 'used-category',
        'is_active' => true,
        'sort_order' => 1,
    ]);
    $category->translations()->create([
        'locale' => 'it',
        'name' => 'Categoria usata',
    ]);

    $type = PoemType::query()->create([
        'slug' => 'used-type',
        'is_active' => true,
        'sort_order' => 1,
    ]);
    $type->translations()->create([
        'locale' => 'it',
        'name' => 'Tipo usato',
    ]);

    Poem::query()->create([
        'user_id' => $owner->id,
        'poem_category_id' => $category->id,
        'poem_type_id' => $type->id,
        'title' => 'Test poem',
        'slug' => 'test-poem',
        'content' => 'Test content',
        'language' => 'it',
        'status' => 'draft',
        'is_public' => false,
        'published_at' => null,
    ]);

    $response = $this->deleteJson("/api/v1/admin/poem-categories/{$category->id}");

    $response
        ->assertStatus(422)
        ->assertJsonPath('errors.message', 'Cannot delete a category that is already used by one or more poems.');

    $this->assertDatabaseHas('poem_categories', [
        'id' => $category->id,
    ]);
});

it('allows an admin to create and delete an unused poem type', function () {
    Sanctum::actingAs(adminUser());

    $createResponse = $this->postJson('/api/v1/admin/poem-types', [
        'slug' => 'spoken-word',
        'is_active' => true,
        'sort_order' => 3,
        'translations' => [
            ['locale' => 'it', 'name' => 'Spoken word'],
            ['locale' => 'en', 'name' => 'Spoken word'],
        ],
    ]);

    $createResponse->assertCreated();

    $typeId = PoemType::query()->where('slug', 'spoken-word')->value('id');

    $deleteResponse = $this->deleteJson("/api/v1/admin/poem-types/{$typeId}");

    $deleteResponse->assertOk();

    $this->assertDatabaseMissing('poem_types', [
        'id' => $typeId,
    ]);
});
