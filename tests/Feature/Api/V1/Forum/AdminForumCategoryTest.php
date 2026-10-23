<?php

use Domain\Forum\Models\ForumCategory;
use Domain\Forum\Models\ForumPost;
use Domain\User\Models\Role;
use Domain\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

function forumAdminUser(): User
{
    $user = User::factory()->create();
    $role = Role::query()->firstOrCreate(
        ['name' => 'admin'],
        ['label' => 'Admin']
    );
    $user->roles()->syncWithoutDetaching([$role->id]);

    return $user;
}

it('allows an admin to create a forum category with translations', function () {
    Sanctum::actingAs(forumAdminUser());

    $response = $this->postJson('/api/v1/admin/forum-categories', [
        'slug' => 'poesia',
        'is_active' => true,
        'sort_order' => 1,
        'translations' => [
            ['locale' => 'it', 'name' => 'Poesia', 'description' => 'Versi e slam'],
            ['locale' => 'en', 'name' => 'Poetry', 'description' => 'Verses and slam'],
        ],
    ]);

    $response
        ->assertCreated()
        ->assertJsonPath('data.category.slug', 'poesia')
        ->assertJsonCount(2, 'data.category.translations');

    $this->assertDatabaseHas('forum_categories', [
        'slug' => 'poesia',
        'is_active' => true,
        'sort_order' => 1,
    ]);
});

it('forbids a non admin from writing forum categories', function () {
    Sanctum::actingAs(User::factory()->create());

    $this->postJson('/api/v1/admin/forum-categories', [
        'slug' => 'off-topic',
        'translations' => [
            ['locale' => 'it', 'name' => 'Off topic'],
        ],
    ])->assertForbidden();
});

it('prevents deleting a forum category that already has posts', function () {
    Sanctum::actingAs(forumAdminUser());

    $category = ForumCategory::query()->create([
        'slug' => 'eventi',
        'is_active' => true,
        'sort_order' => 1,
    ]);
    $category->translations()->create([
        'locale' => 'it',
        'name' => 'Eventi',
    ]);

    ForumPost::query()->create([
        'user_id' => User::factory()->create()->id,
        'forum_category_id' => $category->id,
        'title' => 'Used post',
        'slug' => 'used-post',
        'body' => 'Body',
        'status' => 'published',
    ]);

    $this->deleteJson("/api/v1/admin/forum-categories/{$category->id}")
        ->assertStatus(422)
        ->assertJsonPath('errors.message', __('forum.errors.category_in_use'));
});
