<?php

use Domain\Moderation\Enums\ModerationDecisionType;
use Domain\Moderation\Models\ModerationDecision;
use Domain\Poetry\Models\Poem;
use Domain\User\Models\Role;
use Domain\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

function moderationPanelUser(): User
{
    $user = User::factory()->poetOrganizer()->create();
    $role = Role::query()->firstOrCreate(
        ['name' => 'moderator'],
        ['label' => 'Moderator']
    );
    $user->roles()->syncWithoutDetaching([$role->id]);

    return $user;
}

function moderationPanelAuthor(): User
{
    return User::factory()->poetOrganizer()->create();
}

function makePoemForPanel(string $title, string $slug, string $status, int $userId): Poem
{
    return Poem::query()->create([
        'user_id' => $userId,
        'title' => $title,
        'slug' => $slug,
        'content' => 'Body',
        'language' => 'it',
        'status' => $status,
        'is_public' => true,
        'published_at' => $status === 'published' ? now() : null,
    ]);
}

it('returns moderation dashboard stats', function () {
    Sanctum::actingAs(moderationPanelUser());

    $author = moderationPanelAuthor();
    makePoemForPanel('Pending', 'pending-panel', 'pending_review', $author->id);
    makePoemForPanel('Rejected', 'rejected-panel', 'rejected', $author->id);
    makePoemForPanel('Published', 'published-panel', 'published', $author->id);
    makePoemForPanel('Draft', 'draft-panel', 'draft', $author->id);

    $poem = makePoemForPanel('Approved source', 'approved-source', 'published', $author->id);
    ModerationDecision::query()->create([
        'moderator_user_id' => moderationPanelUser()->id,
        'moderatable_type' => $poem->getMorphClass(),
        'moderatable_id' => $poem->id,
        'decision' => ModerationDecisionType::Approved,
        'note' => 'Approved today',
    ]);

    $response = $this->getJson('/api/v1/moderation/dashboard');

    $response
        ->assertOk()
        ->assertJsonPath('data.stats.pending_review_count', 1)
        ->assertJsonPath('data.stats.rejected_count', 1)
        ->assertJsonPath('data.stats.published_count', 2)
        ->assertJsonPath('data.stats.draft_count', 1)
        ->assertJsonPath('data.stats.approved_today_count', 1);
});

it('lists moderation poems with filters', function () {
    Sanctum::actingAs(moderationPanelUser());

    $author = moderationPanelAuthor();
    makePoemForPanel('Alpha moderation poem', 'alpha-moderation-poem', 'pending_review', $author->id);
    makePoemForPanel('Beta moderation poem', 'beta-moderation-poem', 'rejected', $author->id);

    $response = $this->getJson('/api/v1/moderation/poems?status=pending_review&q=Alpha');

    $response
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.slug', 'alpha-moderation-poem')
        ->assertJsonPath('data.0.status', 'pending_review');
});

it('shows a moderation poem with its moderation history', function () {
    Sanctum::actingAs(moderationPanelUser());

    $author = moderationPanelAuthor();
    $moderator = moderationPanelUser();
    $poem = makePoemForPanel('History poem', 'history-poem', 'rejected', $author->id);

    ModerationDecision::query()->create([
        'moderator_user_id' => $moderator->id,
        'moderatable_type' => $poem->getMorphClass(),
        'moderatable_id' => $poem->id,
        'decision' => ModerationDecisionType::Rejected,
        'note' => 'Needs revision',
    ]);

    $response = $this->getJson("/api/v1/moderation/poems/{$poem->id}");

    $response
        ->assertOk()
        ->assertJsonPath('data.poem.slug', 'history-poem')
        ->assertJsonPath('data.poem.status', 'rejected')
        ->assertJsonPath('data.moderation_history.0.decision', 'rejected')
        ->assertJsonPath('data.moderation_history.0.note', 'Needs revision');
});
