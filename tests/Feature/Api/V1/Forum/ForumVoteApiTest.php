<?php

use Domain\Comment\Models\Comment;
use Domain\Forum\Models\ForumCategory;
use Domain\Forum\Models\ForumPost;
use Domain\Poetry\Models\Poem;
use Domain\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

function voteForumCategory(): ForumCategory
{
    $category = ForumCategory::query()->create([
        'slug' => 'feedback',
        'is_active' => true,
        'sort_order' => 1,
    ]);
    $category->translations()->create([
        'locale' => 'it',
        'name' => 'Feedback',
    ]);

    return $category;
}

function voteForumPost(): ForumPost
{
    return ForumPost::query()->create([
        'user_id' => User::factory()->create()->id,
        'forum_category_id' => voteForumCategory()->id,
        'title' => 'Votable',
        'slug' => 'votable',
        'body' => 'Body',
        'status' => 'published',
        'score' => 0,
    ]);
}

it('toggles and switches votes on a forum post', function () {
    $post = voteForumPost();
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $this->postJson("/api/v1/forum/posts/{$post->public_id}/vote", ['value' => 1])
        ->assertOk()
        ->assertJsonPath('data.post.score', 1)
        ->assertJsonPath('data.post.my_vote', 1);

    $this->postJson("/api/v1/forum/posts/{$post->public_id}/vote", ['value' => -1])
        ->assertOk()
        ->assertJsonPath('data.post.score', -1)
        ->assertJsonPath('data.post.my_vote', -1);

    $this->postJson("/api/v1/forum/posts/{$post->public_id}/vote", ['value' => 0])
        ->assertOk()
        ->assertJsonPath('data.post.score', 0)
        ->assertJsonPath('data.post.my_vote', null);
});

it('lets a verified user vote on a forum comment', function () {
    $post = voteForumPost();
    $comment = Comment::query()->create([
        'user_id' => User::factory()->create()->id,
        'commentable_type' => $post->getMorphClass(),
        'commentable_id' => $post->id,
        'content' => 'Nice',
        'score' => 0,
    ]);

    Sanctum::actingAs(User::factory()->create());

    $this->postJson("/api/v1/comments/{$comment->id}/vote", ['value' => 1])
        ->assertOk()
        ->assertJsonPath('data.comment.score', 1)
        ->assertJsonPath('data.comment.my_vote', 1);
});

it('does not allow votes on non forum comments', function () {
    $poem = Poem::query()->create([
        'user_id' => User::factory()->create()->id,
        'title' => 'Poem',
        'slug' => 'poem-vote',
        'content' => 'Body',
        'language' => 'it',
        'status' => 'published',
        'is_public' => true,
        'published_at' => now(),
    ]);
    $comment = Comment::query()->create([
        'user_id' => User::factory()->create()->id,
        'commentable_type' => $poem->getMorphClass(),
        'commentable_id' => $poem->id,
        'content' => 'Not forum',
    ]);

    Sanctum::actingAs(User::factory()->create());

    $this->postJson("/api/v1/comments/{$comment->id}/vote", ['value' => 1])
        ->assertNotFound();
});
