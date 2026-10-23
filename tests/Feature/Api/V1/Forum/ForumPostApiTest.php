<?php

use Domain\Forum\Models\ForumCategory;
use Domain\Forum\Models\ForumPost;
use Domain\User\Models\Role;
use Domain\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

function forumVerifiedUser(): User
{
    return User::factory()->create();
}

function forumStaffUser(string $roleName = 'moderator'): User
{
    $user = User::factory()->create();
    $role = Role::query()->firstOrCreate(
        ['name' => $roleName],
        ['label' => ucfirst($roleName)]
    );
    $user->roles()->syncWithoutDetaching([$role->id]);

    return $user;
}

function makeForumCategory(string $slug = 'poesia'): ForumCategory
{
    $category = ForumCategory::query()->create([
        'slug' => $slug,
        'is_active' => true,
        'sort_order' => 1,
    ]);
    $category->translations()->create([
        'locale' => 'it',
        'name' => ucfirst($slug),
        'description' => 'Board',
    ]);

    return $category;
}

it('lets a verified user create a post with an auto upvote', function () {
    $category = makeForumCategory();
    $user = forumVerifiedUser();
    Sanctum::actingAs($user);

    $response = $this->postJson('/api/v1/forum/posts', [
        'forum_category_id' => $category->id,
        'title' => 'Primo post',
        'body' => 'Ciao forum',
    ]);

    $response
        ->assertCreated()
        ->assertJsonPath('data.post.title', 'Primo post')
        ->assertJsonPath('data.post.score', 1)
        ->assertJsonPath('data.post.my_vote', 1)
        ->assertJsonPath('data.post.category.slug', 'poesia');

    $this->assertDatabaseHas('forum_posts', [
        'title' => 'Primo post',
        'slug' => 'primo-post',
        'score' => 1,
    ]);
});

it('forbids an unverified user from creating a post', function () {
    $category = makeForumCategory();
    Sanctum::actingAs(User::factory()->unverified()->create());

    $this->postJson('/api/v1/forum/posts', [
        'forum_category_id' => $category->id,
        'title' => 'No',
        'body' => 'No',
    ])->assertForbidden();
});

it('hides unpublished posts from the public feed', function () {
    $category = makeForumCategory();
    $author = forumVerifiedUser();

    ForumPost::query()->create([
        'user_id' => $author->id,
        'forum_category_id' => $category->id,
        'title' => 'Hidden',
        'slug' => 'hidden',
        'body' => 'Secret',
        'status' => 'hidden',
    ]);

    $this->getJson('/api/v1/forum/posts')
        ->assertOk()
        ->assertJsonPath('meta.total', 0);
});

it('rejects comments on a locked post', function () {
    $category = makeForumCategory();
    $post = ForumPost::query()->create([
        'user_id' => forumVerifiedUser()->id,
        'forum_category_id' => $category->id,
        'title' => 'Locked',
        'slug' => 'locked',
        'body' => 'Closed',
        'status' => 'published',
        'is_locked' => true,
    ]);

    Sanctum::actingAs(forumVerifiedUser());

    $this->postJson("/api/v1/forum/posts/{$post->public_id}/comments", [
        'content' => 'Too late',
    ])->assertStatus(422)
        ->assertJsonPath('errors.fields.content.0', __('forum.errors.locked'));
});

it('lets staff hide a post', function () {
    $category = makeForumCategory();
    $post = ForumPost::query()->create([
        'user_id' => forumVerifiedUser()->id,
        'forum_category_id' => $category->id,
        'title' => 'Visible',
        'slug' => 'visible',
        'body' => 'Body',
        'status' => 'published',
    ]);

    Sanctum::actingAs(forumStaffUser());

    $this->patchJson("/api/v1/forum/posts/{$post->public_id}", [
        'status' => 'hidden',
    ])->assertOk()
        ->assertJsonPath('data.post.status', 'hidden');
});

it('lets a verified user report a forum post', function () {
    $category = makeForumCategory();
    $post = ForumPost::query()->create([
        'user_id' => forumVerifiedUser()->id,
        'forum_category_id' => $category->id,
        'title' => 'Report me',
        'slug' => 'report-me',
        'body' => 'Body',
        'status' => 'published',
    ]);

    Sanctum::actingAs(forumVerifiedUser());

    $this->postJson("/api/v1/forum/posts/{$post->public_id}/reports", [
        'reason' => 'spam',
    ])->assertOk();
});
