<?php

use Domain\Article\Models\Article;
use Domain\Comment\Models\Comment;
use Domain\Media\Models\MediaItem;
use Domain\Notification\Models\Notification;
use Domain\Poetry\Models\Poem;
use Domain\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

function commentUser(): User
{
    return User::factory()->poetOrganizer()->create();
}

it('creates a root comment on a published article and notifies the author', function () {
    $author = commentUser();
    $commenter = commentUser();
    Sanctum::actingAs($commenter);

    $article = Article::query()->create([
        'user_id' => $author->id,
        'title' => 'Commentable article',
        'slug' => 'commentable-article',
        'content' => 'Body',
        'language' => 'it',
        'status' => 'published',
        'is_public' => true,
        'published_at' => now(),
    ]);

    $response = $this->postJson("/api/v1/articles/{$article->id}/comments", [
        'content' => 'Great article',
    ]);

    $response
        ->assertOk()
        ->assertJsonPath('data.comment.content', 'Great article')
        ->assertJsonPath('data.comment.parent_id', null);

    $this->assertDatabaseHas('comments', [
        'commentable_type' => $article->getMorphClass(),
        'commentable_id' => $article->id,
        'content' => 'Great article',
    ]);

    $this->assertDatabaseHas('notifications', [
        'user_id' => $author->id,
        'type' => 'article_commented',
    ]);
});

it('creates a reply on a poem and notifies parent author and poem author', function () {
    $poemAuthor = commentUser();
    $parentAuthor = commentUser();
    $replier = commentUser();
    Sanctum::actingAs($replier);

    $poem = Poem::query()->create([
        'user_id' => $poemAuthor->id,
        'title' => 'Commentable poem',
        'slug' => 'commentable-poem',
        'content' => 'Body',
        'language' => 'it',
        'status' => 'published',
        'is_public' => true,
        'published_at' => now(),
    ]);

    $parent = Comment::query()->create([
        'user_id' => $parentAuthor->id,
        'commentable_type' => $poem->getMorphClass(),
        'commentable_id' => $poem->id,
        'content' => 'Original comment',
    ]);

    $response = $this->postJson("/api/v1/poems/{$poem->id}/comments", [
        'content' => 'Reply comment',
        'parent_id' => $parent->id,
    ]);

    $response
        ->assertOk()
        ->assertJsonPath('data.comment.parent_id', $parent->id);

    $this->assertDatabaseHas('notifications', [
        'user_id' => $poemAuthor->id,
        'type' => 'poem_commented',
    ]);

    $this->assertDatabaseHas('notifications', [
        'user_id' => $parentAuthor->id,
        'type' => 'comment_replied',
    ]);
});

it('prevents replying to a reply', function () {
    $user = commentUser();
    Sanctum::actingAs($user);

    $article = Article::query()->create([
        'user_id' => commentUser()->id,
        'title' => 'Article thread',
        'slug' => 'article-thread',
        'content' => 'Body',
        'language' => 'it',
        'status' => 'published',
        'is_public' => true,
        'published_at' => now(),
    ]);

    $root = Comment::query()->create([
        'user_id' => commentUser()->id,
        'commentable_type' => $article->getMorphClass(),
        'commentable_id' => $article->id,
        'content' => 'Root',
    ]);

    $reply = Comment::query()->create([
        'user_id' => commentUser()->id,
        'commentable_type' => $article->getMorphClass(),
        'commentable_id' => $article->id,
        'parent_id' => $root->id,
        'content' => 'Reply',
    ]);

    $response = $this->postJson("/api/v1/articles/{$article->id}/comments", [
        'content' => 'Nested reply',
        'parent_id' => $reply->id,
    ]);

    $response
        ->assertUnprocessable()
        ->assertJsonPath('errors.fields.parent_id.0', __('comment.errors.single_reply_level'));
});

it('updates and deletes only own comments', function () {
    $owner = commentUser();
    $other = commentUser();
    Sanctum::actingAs($owner);

    $comment = Comment::query()->create([
        'user_id' => $owner->id,
        'commentable_type' => (new Article())->getMorphClass(),
        'commentable_id' => 1,
        'content' => 'Old content',
    ]);

    $this->patchJson("/api/v1/comments/{$comment->id}", [
        'content' => 'Updated content',
    ])->assertOk()->assertJsonPath('data.comment.content', 'Updated content');

    Sanctum::actingAs($other);

    $this->patchJson("/api/v1/comments/{$comment->id}", [
        'content' => 'Hack',
    ])->assertForbidden();

    Sanctum::actingAs($owner);

    $this->deleteJson("/api/v1/comments/{$comment->id}")
        ->assertOk()
        ->assertJsonPath('data.deleted', true);

    $this->assertSoftDeleted('comments', ['id' => $comment->id]);
});

it('lists root comments with replies for published public content', function () {
    $article = Article::query()->create([
        'user_id' => commentUser()->id,
        'title' => 'Listed article',
        'slug' => 'listed-article',
        'content' => 'Body',
        'language' => 'it',
        'status' => 'published',
        'is_public' => true,
        'published_at' => now(),
    ]);

    $root = Comment::query()->create([
        'user_id' => commentUser()->id,
        'commentable_type' => $article->getMorphClass(),
        'commentable_id' => $article->id,
        'content' => 'Root comment',
    ]);

    Comment::query()->create([
        'user_id' => commentUser()->id,
        'commentable_type' => $article->getMorphClass(),
        'commentable_id' => $article->id,
        'parent_id' => $root->id,
        'content' => 'Reply comment',
    ]);

    $response = $this->getJson("/api/v1/articles/{$article->id}/comments");

    $response
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.content', 'Root comment')
        ->assertJsonPath('data.0.replies_count', 1)
        ->assertJsonPath('data.0.replies.0.content', 'Reply comment');
});

it('prevents storing a comment on non-public content', function () {
    $author = commentUser();
    $commenter = commentUser();
    Sanctum::actingAs($commenter);

    $privatePoem = Poem::query()->create([
        'user_id' => $author->id,
        'title' => 'Private poem',
        'slug' => 'private-poem-comment-test',
        'content' => 'Body',
        'language' => 'it',
        'status' => 'draft',
        'is_public' => false,
        'published_at' => null,
    ]);

    $this->postJson("/api/v1/poems/{$privatePoem->id}/comments", [
        'content' => 'Should fail',
    ])->assertNotFound();
});

it('prevents replying with a parent from another content item', function () {
    $author = commentUser();
    $commenter = commentUser();
    Sanctum::actingAs($commenter);

    $firstArticle = Article::query()->create([
        'user_id' => $author->id,
        'title' => 'First article',
        'slug' => 'first-article-comment-parent',
        'content' => 'Body',
        'language' => 'it',
        'status' => 'published',
        'is_public' => true,
        'published_at' => now(),
    ]);

    $secondArticle = Article::query()->create([
        'user_id' => $author->id,
        'title' => 'Second article',
        'slug' => 'second-article-comment-parent',
        'content' => 'Body',
        'language' => 'it',
        'status' => 'published',
        'is_public' => true,
        'published_at' => now(),
    ]);

    $parentOnFirst = Comment::query()->create([
        'user_id' => $author->id,
        'commentable_type' => $firstArticle->getMorphClass(),
        'commentable_id' => $firstArticle->id,
        'content' => 'Parent on first article',
    ]);

    $this->postJson("/api/v1/articles/{$secondArticle->id}/comments", [
        'content' => 'Invalid reply',
        'parent_id' => $parentOnFirst->id,
    ])
        ->assertUnprocessable()
        ->assertJsonPath('errors.fields.parent_id.0', __('comment.errors.parent_mismatch'));
});

it('lists media comments only for ready public media', function () {
    $owner = commentUser();

    $publicMedia = MediaItem::query()->create([
        'user_id' => $owner->id,
        'type' => 'video',
        'title' => 'Public media for comments',
        'visibility' => 'public',
        'status' => 'ready',
        'peertube_video_id' => 'comment-media-1',
        'peertube_uuid' => 'comment-media-1',
        'uploaded_at' => now(),
        'published_at' => now(),
    ]);

    $privateMedia = MediaItem::query()->create([
        'user_id' => $owner->id,
        'type' => 'video',
        'title' => 'Private media for comments',
        'visibility' => 'private',
        'status' => 'ready',
        'peertube_video_id' => 'comment-media-2',
        'peertube_uuid' => 'comment-media-2',
        'uploaded_at' => now(),
    ]);

    Comment::query()->create([
        'user_id' => commentUser()->id,
        'commentable_type' => $publicMedia->getMorphClass(),
        'commentable_id' => $publicMedia->id,
        'content' => 'Public media comment',
    ]);

    $this->getJson("/api/v1/media/{$publicMedia->id}/comments")
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.content', 'Public media comment');

    $this->getJson("/api/v1/media/{$privateMedia->id}/comments")
        ->assertNotFound();
});
