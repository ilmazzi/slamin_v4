<?php

use Domain\Event\Models\Event;
use Domain\Moderation\Enums\ModerationDecisionType;
use Domain\Moderation\Models\ModerationDecision;
use Domain\User\Models\Role;
use Domain\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

function eventModerationUser(): User
{
    $user = User::factory()->poetOrganizer()->create();
    $role = Role::query()->firstOrCreate(
        ['name' => 'moderator'],
        ['label' => 'Moderator']
    );
    $user->roles()->syncWithoutDetaching([$role->id]);

    return $user;
}

function eventModerationAuthor(): User
{
    return User::factory()->poetOrganizer()->create();
}

function makeEventForModeration(string $title, string $slug, string $status, int $userId): Event
{
    return Event::query()->create([
        'user_id' => $userId,
        'title' => $title,
        'slug' => $slug,
        'description' => 'Body',
        'schedule_mode' => 'fixed',
        'delivery_mode' => 'online',
        'language' => 'it',
        'status' => $status,
        'is_public' => true,
        'published_at' => $status === 'published' ? now() : null,
    ]);
}

it('returns moderation dashboard stats for events too', function () {
    Sanctum::actingAs(eventModerationUser());

    $author = eventModerationAuthor();
    makeEventForModeration('Pending event', 'pending-event-panel', 'pending_review', $author->id);
    makeEventForModeration('Rejected event', 'rejected-event-panel', 'rejected', $author->id);
    makeEventForModeration('Published event', 'published-event-panel', 'published', $author->id);
    makeEventForModeration('Draft event', 'draft-event-panel', 'draft', $author->id);

    $event = makeEventForModeration('Approved source event', 'approved-source-event', 'published', $author->id);
    ModerationDecision::query()->create([
        'moderator_user_id' => eventModerationUser()->id,
        'moderatable_type' => $event->getMorphClass(),
        'moderatable_id' => $event->id,
        'decision' => ModerationDecisionType::Approved,
        'note' => 'Approved today',
    ]);

    $response = $this->getJson('/api/v1/moderation/dashboard');

    $response
        ->assertOk()
        ->assertJsonPath('data.stats.events.pending_review_count', 1)
        ->assertJsonPath('data.stats.events.rejected_count', 1)
        ->assertJsonPath('data.stats.events.published_count', 2)
        ->assertJsonPath('data.stats.events.draft_count', 1)
        ->assertJsonPath('data.stats.events.approved_today_count', 1);
});

it('lists moderation events with filters', function () {
    Sanctum::actingAs(eventModerationUser());

    $author = eventModerationAuthor();
    makeEventForModeration('Alpha moderation event', 'alpha-moderation-event', 'pending_review', $author->id);
    makeEventForModeration('Beta moderation event', 'beta-moderation-event', 'rejected', $author->id);

    $response = $this->getJson('/api/v1/moderation/events?status=pending_review&q=Alpha');

    $response
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.slug', 'alpha-moderation-event')
        ->assertJsonPath('data.0.status', 'pending_review');
});

it('shows a moderation event with its moderation history', function () {
    Sanctum::actingAs(eventModerationUser());

    $author = eventModerationAuthor();
    $moderator = eventModerationUser();
    $event = makeEventForModeration('History event', 'history-event', 'rejected', $author->id);

    ModerationDecision::query()->create([
        'moderator_user_id' => $moderator->id,
        'moderatable_type' => $event->getMorphClass(),
        'moderatable_id' => $event->id,
        'decision' => ModerationDecisionType::Rejected,
        'note' => 'Needs revision',
    ]);

    $response = $this->getJson("/api/v1/moderation/events/{$event->id}");

    $response
        ->assertOk()
        ->assertJsonPath('data.event.slug', 'history-event')
        ->assertJsonPath('data.event.status', 'rejected')
        ->assertJsonPath('data.moderation_history.0.decision', 'rejected')
        ->assertJsonPath('data.moderation_history.0.note', 'Needs revision');
});

it('allows moderators to approve a pending review event', function () {
    Sanctum::actingAs(eventModerationUser());

    $event = makeEventForModeration('Pending moderation event', 'pending-moderation-event', 'pending_review', eventModerationAuthor()->id);

    $response = $this->postJson("/api/v1/moderation/events/{$event->id}/approve", [
        'note' => 'Ready to go live',
    ]);

    $response
        ->assertOk()
        ->assertJsonPath('data.event.status', 'published');

    $this->assertDatabaseHas('events', [
        'id' => $event->id,
        'status' => 'published',
    ]);
});

it('allows moderators to reject a pending review event', function () {
    Sanctum::actingAs(eventModerationUser());

    $event = makeEventForModeration('Pending reject event', 'pending-reject-event', 'pending_review', eventModerationAuthor()->id);

    $response = $this->postJson("/api/v1/moderation/events/{$event->id}/reject", [
        'note' => 'Not enough details',
    ]);

    $response
        ->assertOk()
        ->assertJsonPath('data.event.status', 'rejected');

    $this->assertDatabaseHas('events', [
        'id' => $event->id,
        'status' => 'rejected',
    ]);
});
