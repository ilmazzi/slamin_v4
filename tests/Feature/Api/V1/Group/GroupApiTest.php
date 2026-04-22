<?php

use Domain\Event\Models\Event;
use Domain\Group\Enums\GroupMemberRole;
use Domain\Group\Enums\GroupMemberStatus;
use Domain\Group\Enums\GroupVisibility;
use Domain\Group\Models\Group;
use Domain\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

function groupOwner(): User
{
    return User::factory()->poetOrganizer()->create();
}

function createGroupWithOwner(User $owner, GroupVisibility $visibility = GroupVisibility::PublicOpen): Group
{
    $group = Group::query()->create([
        'name' => fake()->company(),
        'slug' => fake()->unique()->slug(),
        'description' => fake()->sentence(),
        'visibility' => $visibility,
        'is_active' => true,
    ]);

    $group->members()->create([
        'user_id' => $owner->id,
        'role' => GroupMemberRole::Owner,
        'status' => GroupMemberStatus::Active,
        'joined_at' => now(),
        'handled_by_user_id' => $owner->id,
        'handled_at' => now(),
    ]);

    return $group;
}

it('creates a group and assigns the creator as active owner', function () {
    $owner = groupOwner();
    Sanctum::actingAs($owner);

    $response = $this->postJson('/api/v1/groups', [
        'name' => 'Slam Roma Centro',
        'description' => 'Gruppo di prova',
        'visibility' => 'public_open',
        'website_url' => 'https://example.com',
    ]);

    $response
        ->assertOk()
        ->assertJsonPath('data.group.name', 'Slam Roma Centro')
        ->assertJsonPath('data.group.visibility', 'public_open')
        ->assertJsonPath('data.group.current_user_membership.role', 'owner')
        ->assertJsonPath('data.group.current_user_membership.status', 'active');

    $groupId = $response->json('data.group.id');

    $this->assertDatabaseHas('group_members', [
        'group_id' => $groupId,
        'user_id' => $owner->id,
        'role' => 'owner',
        'status' => 'active',
    ]);
});

it('allows direct join for public groups', function () {
    $owner = groupOwner();
    $member = User::factory()->create();
    $group = createGroupWithOwner($owner, GroupVisibility::PublicOpen);

    Sanctum::actingAs($member);

    $this->postJson("/api/v1/groups/{$group->slug}/join")
        ->assertOk()
        ->assertJsonPath('data.membership.role', 'member')
        ->assertJsonPath('data.membership.status', 'active');

    $this->assertDatabaseHas('group_members', [
        'group_id' => $group->id,
        'user_id' => $member->id,
        'role' => 'member',
        'status' => 'active',
    ]);
});

it('creates a pending request for private groups and notifies owners', function () {
    $owner = groupOwner();
    $requester = User::factory()->create();
    $group = createGroupWithOwner($owner, GroupVisibility::PrivateApproval);

    Sanctum::actingAs($requester);

    $this->postJson("/api/v1/groups/{$group->slug}/join")
        ->assertOk()
        ->assertJsonPath('data.membership.status', 'pending');

    $this->assertDatabaseHas('group_members', [
        'group_id' => $group->id,
        'user_id' => $requester->id,
        'status' => 'pending',
    ]);

    $this->assertDatabaseHas('notifications', [
        'user_id' => $owner->id,
        'type' => 'group_join_requested',
    ]);
});

it('allows owners to approve private join requests', function () {
    $owner = groupOwner();
    $requester = User::factory()->create();
    $group = createGroupWithOwner($owner, GroupVisibility::PrivateApproval);

    $membership = $group->members()->create([
        'user_id' => $requester->id,
        'role' => GroupMemberRole::Member,
        'status' => GroupMemberStatus::Pending,
    ]);

    Sanctum::actingAs($owner);

    $this->postJson("/api/v1/groups/{$group->slug}/requests/{$membership->id}/approve")
        ->assertOk()
        ->assertJsonPath('data.membership.status', 'active');

    $this->assertDatabaseHas('group_members', [
        'id' => $membership->id,
        'status' => 'active',
    ]);

    $this->assertDatabaseHas('notifications', [
        'user_id' => $requester->id,
        'type' => 'group_join_approved',
    ]);
});

it('supports multiple owners and lets an owner leave when another owner remains', function () {
    $firstOwner = groupOwner();
    $secondOwner = groupOwner();
    $group = createGroupWithOwner($firstOwner);

    $membership = $group->members()->create([
        'user_id' => $secondOwner->id,
        'role' => GroupMemberRole::Member,
        'status' => GroupMemberStatus::Active,
        'joined_at' => now(),
        'handled_by_user_id' => $firstOwner->id,
        'handled_at' => now(),
    ]);

    Sanctum::actingAs($firstOwner);

    $this->patchJson("/api/v1/groups/{$group->slug}/members/{$membership->id}/role", [
        'role' => 'owner',
    ])
        ->assertOk()
        ->assertJsonPath('data.membership.role', 'owner');

    $this->postJson("/api/v1/groups/{$group->slug}/leave")
        ->assertOk();

    $this->assertDatabaseHas('group_members', [
        'group_id' => $group->id,
        'user_id' => $firstOwner->id,
        'status' => 'left',
    ]);

    $this->assertDatabaseHas('group_members', [
        'group_id' => $group->id,
        'user_id' => $secondOwner->id,
        'role' => 'owner',
        'status' => 'active',
    ]);
});

it('allows moderators to publish announcements and notifies active members', function () {
    $owner = groupOwner();
    $moderator = groupOwner();
    $member = User::factory()->create();
    $group = createGroupWithOwner($owner);

    $group->members()->create([
        'user_id' => $moderator->id,
        'role' => GroupMemberRole::Moderator,
        'status' => GroupMemberStatus::Active,
        'joined_at' => now(),
        'handled_by_user_id' => $owner->id,
        'handled_at' => now(),
    ]);

    $group->members()->create([
        'user_id' => $member->id,
        'role' => GroupMemberRole::Member,
        'status' => GroupMemberStatus::Active,
        'joined_at' => now(),
        'handled_by_user_id' => $owner->id,
        'handled_at' => now(),
    ]);

    Sanctum::actingAs($moderator);

    $this->postJson("/api/v1/groups/{$group->slug}/announcements", [
        'title' => 'Nuova prova aperta',
        'content' => 'Ci vediamo venerdi alle 21.',
        'is_pinned' => true,
    ])
        ->assertOk()
        ->assertJsonPath('data.announcement.title', 'Nuova prova aperta')
        ->assertJsonPath('data.announcement.is_pinned', true);

    $this->assertDatabaseHas('group_announcements', [
        'group_id' => $group->id,
        'author_user_id' => $moderator->id,
        'title' => 'Nuova prova aperta',
    ]);

    $this->assertDatabaseHas('notifications', [
        'user_id' => $member->id,
        'type' => 'group_announcement_published',
    ]);
});

it('links events to groups and lists them for active group members', function () {
    $owner = groupOwner();
    $group = createGroupWithOwner($owner);

    Sanctum::actingAs($owner);

    $this->postJson('/api/v1/events', [
        'title' => 'Slam Night',
        'description' => 'Evento del gruppo',
        'schedule_mode' => 'fixed',
        'delivery_mode' => 'online',
        'status' => 'draft',
        'group_ids' => [$group->id],
        'occurrence' => [
            'starts_at' => now()->addDay()->toISOString(),
            'timezone' => 'UTC',
        ],
        'online' => [
            'meeting_url' => 'https://example.com/live',
            'timezone' => 'UTC',
        ],
    ])
        ->assertOk()
        ->assertJsonPath('data.event.group_ids.0', $group->id);

    $event = Event::query()->where('slug', 'slam-night')->firstOrFail();

    $this->assertDatabaseHas('event_group', [
        'event_id' => $event->id,
        'group_id' => $group->id,
    ]);

    $this->getJson("/api/v1/groups/{$group->slug}/events")
        ->assertOk()
        ->assertJsonCount(1, 'data.events')
        ->assertJsonPath('data.events.0.slug', 'slam-night');
});
