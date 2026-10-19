<?php

use Domain\Group\Models\Group;
use Domain\Group\Models\GroupCategory;
use Domain\User\Models\Role;
use Domain\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

function adminGroupCategoryUser(): User
{
    $user = User::factory()->poetOrganizer()->create();
    $role = Role::query()->firstOrCreate(
        ['name' => 'admin'],
        ['label' => 'Admin']
    );
    $user->roles()->syncWithoutDetaching([$role->id]);

    return $user;
}

function nonAdminGroupCategoryUser(): User
{
    return User::factory()->create();
}

it('allows an admin to create a group category with translations', function () {
    Sanctum::actingAs(adminGroupCategoryUser());

    $response = $this->postJson('/api/v1/admin/group-categories', [
        'slug' => 'progetto-artistico',
        'is_active' => true,
        'sort_order' => 1,
        'translations' => [
            ['locale' => 'it', 'name' => 'Progetto artistico'],
            ['locale' => 'en', 'name' => 'Artistic project'],
        ],
    ]);

    $response
        ->assertCreated()
        ->assertJsonPath('data.category.slug', 'progetto-artistico')
        ->assertJsonCount(2, 'data.category.translations');

    $this->assertDatabaseHas('group_categories', [
        'slug' => 'progetto-artistico',
        'is_active' => true,
        'sort_order' => 1,
    ]);
});

it('forbids a non admin from writing group categories', function () {
    Sanctum::actingAs(nonAdminGroupCategoryUser());

    $response = $this->postJson('/api/v1/admin/group-categories', [
        'slug' => 'luogo',
        'translations' => [
            ['locale' => 'it', 'name' => 'Luogo'],
        ],
    ]);

    $response->assertForbidden();
});

it('allows an admin to update a group category', function () {
    Sanctum::actingAs(adminGroupCategoryUser());

    $category = GroupCategory::query()->create([
        'slug' => 'associazione-organizzazione',
        'is_active' => true,
        'sort_order' => 1,
    ]);

    $category->translations()->createMany([
        ['locale' => 'it', 'name' => 'Associazione'],
        ['locale' => 'en', 'name' => 'Association'],
    ]);

    $response = $this->patchJson("/api/v1/admin/group-categories/{$category->id}", [
        'slug' => 'associazione',
        'is_active' => false,
        'sort_order' => 5,
        'translations' => [
            ['locale' => 'it', 'name' => 'Associazione aggiornata'],
            ['locale' => 'fr', 'name' => 'Association'],
        ],
    ]);

    $response
        ->assertOk()
        ->assertJsonPath('data.category.slug', 'associazione')
        ->assertJsonPath('data.category.is_active', false)
        ->assertJsonCount(2, 'data.category.translations');

    $this->assertDatabaseHas('group_categories', [
        'id' => $category->id,
        'slug' => 'associazione',
        'is_active' => false,
        'sort_order' => 5,
    ]);
});

it('prevents deleting a group category that is already used by a group', function () {
    Sanctum::actingAs(adminGroupCategoryUser());

    $owner = User::factory()->poetOrganizer()->create();
    $category = GroupCategory::query()->create([
        'slug' => 'luogo',
        'is_active' => true,
        'sort_order' => 1,
    ]);
    $category->translations()->create([
        'locale' => 'it',
        'name' => 'Luogo',
    ]);

    Group::query()->create([
        'name' => 'Used group',
        'slug' => 'used-group',
        'description' => 'Body',
        'visibility' => 'public',
        'is_listed' => true,
        'group_category_id' => $category->id,
        'is_active' => true,
    ]);

    $response = $this->deleteJson("/api/v1/admin/group-categories/{$category->id}");

    $response
        ->assertStatus(422)
        ->assertJsonPath('errors.message', __('group.errors.category_in_use'));
});

it('lists active group categories to authenticated users', function () {
    $active = GroupCategory::query()->create([
        'slug' => 'altro',
        'is_active' => true,
        'sort_order' => 1,
    ]);
    $active->translations()->create([
        'locale' => 'it',
        'name' => 'Altro',
    ]);

    GroupCategory::query()->create([
        'slug' => 'hidden',
        'is_active' => false,
        'sort_order' => 2,
    ]);

    Sanctum::actingAs(nonAdminGroupCategoryUser());

    $this->getJson('/api/v1/group-categories')
        ->assertOk()
        ->assertJsonCount(1, 'data.categories')
        ->assertJsonPath('data.categories.0.slug', 'altro');
});
