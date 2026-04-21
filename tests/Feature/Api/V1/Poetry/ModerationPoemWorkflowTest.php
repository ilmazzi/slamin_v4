<?php

use Domain\Admin\Models\ContentPublicationSetting;
use Domain\Notification\Models\Notification;
use Domain\Poetry\Models\Poem;
use Domain\Shared\Enums\PublicationFlow;
use Domain\User\Models\Role;
use Domain\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

function moderatorUser(): User
{
    $user = User::factory()->poetOrganizer()->create();
    $role = Role::query()->firstOrCreate(
        ['name' => 'moderator'],
        ['label' => 'Moderator']
    );
    $user->roles()->syncWithoutDetaching([$role->id]);

    return $user;
}

function plainPoetOrganizer(): User
{
    return User::factory()->poetOrganizer()->create();
}

function createPendingReviewPoemForModeration(): Poem
{
    $author = plainPoetOrganizer();

    return Poem::query()->create([
        'user_id' => $author->id,
        'title' => 'Pending review poem',
        'slug' => 'pending-review-poem',
        'content' => 'Body',
        'language' => 'it',
        'status' => 'pending_review',
        'is_public' => true,
        'published_at' => null,
    ]);
}

it('lists poems that are pending review for moderators', function () {
    Sanctum::actingAs(moderatorUser());

    createPendingReviewPoemForModeration();

    Poem::query()->create([
        'user_id' => plainPoetOrganizer()->id,
        'title' => 'Published poem',
        'slug' => 'published-poem',
        'content' => 'Body',
        'language' => 'it',
        'status' => 'published',
        'is_public' => true,
        'published_at' => now(),
    ]);

    $response = $this->getJson('/api/v1/moderation/poems/pending-review');

    $response
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.slug', 'pending-review-poem')
        ->assertJsonPath('data.0.status', 'pending_review');
});

it('allows a moderator to approve a pending review poem', function () {
    Sanctum::actingAs(moderatorUser());

    $poem = createPendingReviewPoemForModeration();

    $response = $this->postJson("/api/v1/moderation/poems/{$poem->id}/approve", [
        'note' => 'Contenuto conforme alle linee guida.',
    ]);

    $response
        ->assertOk()
        ->assertJsonPath('data.poem.status', 'published');

    $this->assertDatabaseHas('poems', [
        'id' => $poem->id,
        'status' => 'published',
    ]);

    $this->assertDatabaseHas('moderation_decisions', [
        'moderatable_id' => $poem->id,
        'decision' => 'approved',
        'note' => 'Contenuto conforme alle linee guida.',
    ]);

    $this->assertDatabaseHas('notifications', [
        'user_id' => $poem->user_id,
        'type' => 'poem_approved',
    ]);
});

it('allows a moderator to reject a pending review poem', function () {
    Sanctum::actingAs(moderatorUser());

    $poem = createPendingReviewPoemForModeration();

    $response = $this->postJson("/api/v1/moderation/poems/{$poem->id}/reject", [
        'note' => 'Testo non ancora pronto per la pubblicazione.',
    ]);

    $response
        ->assertOk()
        ->assertJsonPath('data.poem.status', 'rejected')
        ->assertJsonPath('data.poem.published_at', null);

    $this->assertDatabaseHas('poems', [
        'id' => $poem->id,
        'status' => 'rejected',
    ]);

    $this->assertDatabaseHas('moderation_decisions', [
        'moderatable_id' => $poem->id,
        'decision' => 'rejected',
        'note' => 'Testo non ancora pronto per la pubblicazione.',
    ]);

    $this->assertDatabaseHas('notifications', [
        'user_id' => $poem->user_id,
        'type' => 'poem_rejected',
    ]);
});

it('forbids a poet organizer without moderation role from accessing moderation routes', function () {
    Sanctum::actingAs(plainPoetOrganizer());

    $response = $this->getJson('/api/v1/moderation/poems/pending-review');

    $response->assertForbidden();
});

it('creates pending review poems when publication flow is manual review and then moderator can approve them', function () {
    ContentPublicationSetting::query()->create([
        'content_type' => 'poem',
        'publication_flow' => PublicationFlow::ManualReview,
        'is_active' => true,
    ]);

    $author = plainPoetOrganizer();
    Sanctum::actingAs($author);

    $createResponse = $this->postJson('/api/v1/poems', [
        'title' => 'Moderated publication',
        'content' => 'Body',
        'status' => 'published',
        'is_public' => true,
        'language' => 'it',
    ]);

    $createResponse
        ->assertOk()
        ->assertJsonPath('data.poem.status', 'pending_review');

    $poemId = $createResponse->json('data.poem.id');

    Sanctum::actingAs(moderatorUser());

    $approveResponse = $this->postJson("/api/v1/moderation/poems/{$poemId}/approve", [
        'note' => 'Approvata dopo revisione.',
    ]);

    $approveResponse
        ->assertOk()
        ->assertJsonPath('data.poem.status', 'published');
});

it('requires a rejection note and exposes moderation history', function () {
    $moderator = moderatorUser();
    Sanctum::actingAs($moderator);

    $poem = createPendingReviewPoemForModeration();

    $badResponse = $this->postJson("/api/v1/moderation/poems/{$poem->id}/reject");
    $badResponse->assertStatus(422);

    $this->postJson("/api/v1/moderation/poems/{$poem->id}/reject", [
        'note' => 'Motivazione moderazione.',
    ])->assertOk();

    $historyResponse = $this->getJson("/api/v1/moderation/poems/{$poem->id}/history");

    $historyResponse
        ->assertOk()
        ->assertJsonCount(1, 'data.history')
        ->assertJsonPath('data.history.0.decision', 'rejected')
        ->assertJsonPath('data.history.0.note', 'Motivazione moderazione.');
});
