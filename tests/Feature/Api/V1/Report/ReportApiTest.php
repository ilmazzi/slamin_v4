<?php

use Domain\Article\Models\Article;
use Domain\Comment\Models\Comment;
use Domain\Media\Models\MediaItem;
use Domain\Notification\Models\Notification;
use Domain\Poetry\Models\Poem;
use Domain\Report\Models\Report;
use Domain\User\Models\Role;
use Domain\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

function reportUser(): User
{
    return User::factory()->poetOrganizer()->create();
}

function reportModerator(): User
{
    $user = User::factory()->poetOrganizer()->create();
    $role = Role::query()->firstOrCreate(['name' => 'moderator'], ['label' => 'Moderator']);
    $user->roles()->syncWithoutDetaching([$role->id]);

    return $user;
}

it('creates a report for a published public poem', function () {
    $owner = reportUser();
    $reporter = reportUser();
    $moderator = reportModerator();
    Sanctum::actingAs($reporter);

    $poem = Poem::query()->create([
        'user_id' => $owner->id,
        'title' => 'Reported poem',
        'slug' => 'reported-poem',
        'content' => 'Body',
        'language' => 'it',
        'status' => 'published',
        'is_public' => true,
        'published_at' => now(),
    ]);

    $response = $this->postJson("/api/v1/poems/{$poem->id}/reports", [
        'reason' => 'spam',
        'details' => 'Looks suspicious',
    ]);

    $response
        ->assertOk()
        ->assertJsonPath('data.report.reason', 'spam')
        ->assertJsonPath('data.report.status', 'open')
        ->assertJsonPath('data.report.reportable.type', 'poem');

    $this->assertDatabaseHas('reports', [
        'reporter_user_id' => $reporter->id,
        'reportable_type' => $poem->getMorphClass(),
        'reportable_id' => $poem->id,
        'reason' => 'spam',
        'status' => 'open',
    ]);

    $this->assertDatabaseHas('notifications', [
        'user_id' => $moderator->id,
        'type' => 'report_created',
    ]);
});

it('prevents duplicate open reports by the same user on the same content', function () {
    $owner = reportUser();
    $reporter = reportUser();
    Sanctum::actingAs($reporter);

    $article = Article::query()->create([
        'user_id' => $owner->id,
        'title' => 'Reported article',
        'slug' => 'reported-article',
        'content' => 'Body',
        'language' => 'it',
        'status' => 'published',
        'is_public' => true,
        'published_at' => now(),
    ]);

    Report::query()->create([
        'reporter_user_id' => $reporter->id,
        'reportable_type' => $article->getMorphClass(),
        'reportable_id' => $article->id,
        'reason' => 'spam',
        'status' => 'open',
    ]);

    $response = $this->postJson("/api/v1/articles/{$article->id}/reports", [
        'reason' => 'abuse',
    ]);

    $response
        ->assertUnprocessable()
        ->assertJsonPath('errors.fields.report.0', __('report.errors.duplicate_open'));
});

it('allows reporting a comment on public content', function () {
    $owner = reportUser();
    $commentAuthor = reportUser();
    $reporter = reportUser();
    Sanctum::actingAs($reporter);

    $article = Article::query()->create([
        'user_id' => $owner->id,
        'title' => 'Comment report article',
        'slug' => 'comment-report-article',
        'content' => 'Body',
        'language' => 'it',
        'status' => 'published',
        'is_public' => true,
        'published_at' => now(),
    ]);

    $comment = Comment::query()->create([
        'user_id' => $commentAuthor->id,
        'commentable_type' => $article->getMorphClass(),
        'commentable_id' => $article->id,
        'content' => 'Bad comment',
    ]);

    $response = $this->postJson("/api/v1/comments/{$comment->id}/reports", [
        'reason' => 'harassment',
    ]);

    $response
        ->assertOk()
        ->assertJsonPath('data.report.reportable.type', 'comment')
        ->assertJsonPath('data.report.reportable.content', 'Bad comment');
});

it('allows reporting a comment on public media', function () {
    $owner = reportUser();
    $commentAuthor = reportUser();
    $reporter = reportUser();
    Sanctum::actingAs($reporter);

    $media = MediaItem::query()->create([
        'user_id' => $owner->id,
        'type' => 'video',
        'title' => 'Reportable media',
        'visibility' => 'public',
        'status' => 'ready',
        'peertube_video_id' => 'report-media-1',
        'peertube_uuid' => 'report-media-1',
        'uploaded_at' => now(),
        'published_at' => now(),
    ]);

    $comment = Comment::query()->create([
        'user_id' => $commentAuthor->id,
        'commentable_type' => $media->getMorphClass(),
        'commentable_id' => $media->id,
        'content' => 'Bad media comment',
    ]);

    $response = $this->postJson("/api/v1/comments/{$comment->id}/reports", [
        'reason' => 'harassment',
    ]);

    $response
        ->assertOk()
        ->assertJsonPath('data.report.reportable.type', 'comment')
        ->assertJsonPath('data.report.reportable.content', 'Bad media comment');
});

it('allows reporting a public media item', function () {
    $owner = reportUser();
    $reporter = reportUser();
    Sanctum::actingAs($reporter);

    $media = MediaItem::query()->create([
        'user_id' => $owner->id,
        'type' => 'video',
        'title' => 'Public media',
        'visibility' => 'public',
        'status' => 'ready',
        'peertube_video_id' => 'report-media-2',
        'peertube_uuid' => 'report-media-2',
        'uploaded_at' => now(),
        'published_at' => now(),
    ]);

    $response = $this->postJson("/api/v1/media/{$media->id}/reports", [
        'reason' => 'spam',
        'details' => 'Suspicious media',
    ]);

    $response
        ->assertOk()
        ->assertJsonPath('data.report.reportable.type', 'media')
        ->assertJsonPath('data.report.reportable.id', $media->id)
        ->assertJsonPath('data.report.reason', 'spam');
});

it('lists and updates reports for moderators', function () {
    $moderator = reportModerator();
    Sanctum::actingAs($moderator);

    $owner = reportUser();
    $reporter = reportUser();
    $poem = Poem::query()->create([
        'user_id' => $owner->id,
        'title' => 'Moderated report poem',
        'slug' => 'moderated-report-poem',
        'content' => 'Body',
        'language' => 'it',
        'status' => 'published',
        'is_public' => true,
        'published_at' => now(),
    ]);

    $report = Report::query()->create([
        'reporter_user_id' => $reporter->id,
        'reportable_type' => $poem->getMorphClass(),
        'reportable_id' => $poem->id,
        'reason' => 'spam',
        'status' => 'open',
    ]);

    $this->getJson('/api/v1/moderation/reports?status=open')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $report->id);

    $this->getJson("/api/v1/moderation/reports/{$report->id}")
        ->assertOk()
        ->assertJsonPath('data.report.id', $report->id)
        ->assertJsonPath('data.report.reportable.type', 'poem');

    $this->patchJson("/api/v1/moderation/reports/{$report->id}", [
        'status' => 'resolved',
        'resolution_note' => 'Handled by moderation',
    ])
        ->assertOk()
        ->assertJsonPath('data.report.status', 'resolved')
        ->assertJsonPath('data.report.resolution_note', 'Handled by moderation');

    $this->assertDatabaseHas('reports', [
        'id' => $report->id,
        'status' => 'resolved',
        'reviewed_by_user_id' => $moderator->id,
    ]);

    $this->assertDatabaseHas('notifications', [
        'user_id' => $reporter->id,
        'type' => 'report_updated',
    ]);
});
