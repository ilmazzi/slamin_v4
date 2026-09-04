<?php

use Domain\Chat\Models\ChatThread;
use Domain\Group\Enums\GroupMemberRole;
use Domain\Group\Enums\GroupMemberStatus;
use Domain\Group\Enums\GroupVisibility;
use Domain\Group\Models\Group;
use Domain\Group\Models\GroupAnnouncement;
use Domain\Group\Models\GroupMember;
use Domain\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

function groupWorkflowOwner(): User
{
    return User::factory()->poetOrganizer()->create([
        'email_verified_at' => now(),
    ]);
}

function groupWorkflowUser(): User
{
    return User::factory()->create([
        'email_verified_at' => now(),
    ]);
}

function groupWorkflowCreateWithOwner(
    User $owner,
    GroupVisibility $visibility = GroupVisibility::PublicOpen,
    ?string $slug = null
): Group {
    /** @var Group $group */
    $group = Group::query()->create([
        'name' => 'Group ' . fake()->unique()->word(),
        'slug' => $slug ?? fake()->unique()->slug(),
        'description' => 'Descrizione gruppo',
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

it('lists only public active groups and supports search query', function () {
    $owner = groupWorkflowOwner();
    $publicGroup = groupWorkflowCreateWithOwner($owner, GroupVisibility::PublicOpen, 'slam-rome');
    $publicGroup->update([
        'name' => 'Slam Rome Centro',
        'description' => 'Open mic poetry',
    ]);
    groupWorkflowCreateWithOwner($owner, GroupVisibility::PrivateApproval, 'private-slam');

    $response = $this->getJson('/api/v1/groups?q=rome');

    $response
        ->assertOk()
        ->assertJsonCount(1, 'data.groups')
        ->assertJsonPath('data.groups.0.slug', 'slam-rome');
});

it('shows private group only to active members', function () {
    $owner = groupWorkflowOwner();
    $other = groupWorkflowUser();
    $group = groupWorkflowCreateWithOwner($owner, GroupVisibility::PrivateApproval, 'private-visible-group');

    // Non member cannot see private group.
    Sanctum::actingAs($other);
    $this->getJson('/api/v1/groups/private-visible-group')->assertNotFound();

    // Active member can see private group.
    Sanctum::actingAs($owner);
    $this->getJson('/api/v1/groups/private-visible-group')
        ->assertOk()
        ->assertJsonPath('data.group.slug', $group->slug);
});

it('lists my groups including pending memberships', function () {
    $owner = groupWorkflowOwner();
    $user = groupWorkflowUser();
    $group = groupWorkflowCreateWithOwner($owner, GroupVisibility::PrivateApproval);

    $group->members()->create([
        'user_id' => $user->id,
        'role' => GroupMemberRole::Member,
        'status' => GroupMemberStatus::Pending,
    ]);

    Sanctum::actingAs($user);
    $this->getJson('/api/v1/my/groups')
        ->assertOk()
        ->assertJsonCount(1, 'data.groups')
        ->assertJsonPath('data.groups.0.current_user_membership.status', 'pending');
});

it('rejects leave for last active owner', function () {
    $owner = groupWorkflowOwner();
    $group = groupWorkflowCreateWithOwner($owner);

    Sanctum::actingAs($owner);
    $this->postJson("/api/v1/groups/{$group->slug}/leave")
        ->assertStatus(422)
        ->assertJsonPath('errors.fields.group.0', __('group.errors.last_owner'));
});

it('lists pending requests and supports reject flow by owner', function () {
    $owner = groupWorkflowOwner();
    $requester = groupWorkflowUser();
    $group = groupWorkflowCreateWithOwner($owner, GroupVisibility::PrivateApproval);

    /** @var GroupMember $membership */
    $membership = $group->members()->create([
        'user_id' => $requester->id,
        'role' => GroupMemberRole::Member,
        'status' => GroupMemberStatus::Pending,
    ]);

    Sanctum::actingAs($owner);

    $this->getJson("/api/v1/groups/{$group->slug}/requests")
        ->assertOk()
        ->assertJsonCount(1, 'data.requests')
        ->assertJsonPath('data.requests.0.id', $membership->id);

    $this->postJson("/api/v1/groups/{$group->slug}/requests/{$membership->id}/reject")
        ->assertOk()
        ->assertJsonPath('data.membership.status', 'rejected');
});

it('removes a member and notifies them', function () {
    $owner = groupWorkflowOwner();
    $member = groupWorkflowUser();
    $group = groupWorkflowCreateWithOwner($owner);

    /** @var GroupMember $membership */
    $membership = $group->members()->create([
        'user_id' => $member->id,
        'role' => GroupMemberRole::Member,
        'status' => GroupMemberStatus::Active,
        'joined_at' => now(),
    ]);

    Sanctum::actingAs($owner);
    $this->deleteJson("/api/v1/groups/{$group->slug}/members/{$membership->id}")
        ->assertOk()
        ->assertJsonPath('data.membership.status', 'removed');

    $this->assertDatabaseHas('notifications', [
        'user_id' => $member->id,
        'type' => 'group_member_removed',
    ]);
});

it('supports list and delete for announcements', function () {
    $owner = groupWorkflowOwner();
    $moderator = groupWorkflowOwner();
    $group = groupWorkflowCreateWithOwner($owner);

    $group->members()->create([
        'user_id' => $moderator->id,
        'role' => GroupMemberRole::Moderator,
        'status' => GroupMemberStatus::Active,
        'joined_at' => now(),
        'handled_by_user_id' => $owner->id,
        'handled_at' => now(),
    ]);

    Sanctum::actingAs($moderator);

    $created = $this->postJson("/api/v1/groups/{$group->slug}/announcements", [
        'title' => 'Titolo Annuncio',
        'content' => 'Testo annuncio',
        'is_pinned' => true,
    ])->assertOk();

    $announcementId = $created->json('data.announcement.id');

    $this->getJson("/api/v1/groups/{$group->slug}/announcements")
        ->assertOk()
        ->assertJsonCount(1, 'data.announcements')
        ->assertJsonPath('data.announcements.0.id', $announcementId);

    $this->deleteJson("/api/v1/groups/{$group->slug}/announcements/{$announcementId}")
        ->assertOk();

    $this->assertSoftDeleted('group_announcements', [
        'id' => $announcementId,
    ]);
});

it('returns 404 when announcement does not belong to the group route', function () {
    $owner = groupWorkflowOwner();
    $groupA = groupWorkflowCreateWithOwner($owner, GroupVisibility::PublicOpen, 'group-a');
    $groupB = groupWorkflowCreateWithOwner($owner, GroupVisibility::PublicOpen, 'group-b');

    /** @var GroupAnnouncement $announcement */
    $announcement = $groupA->announcements()->create([
        'author_user_id' => $owner->id,
        'title' => 'A',
        'content' => 'A content',
        'is_pinned' => false,
        'published_at' => now(),
    ]);

    Sanctum::actingAs($owner);
    $this->deleteJson("/api/v1/groups/{$groupB->slug}/announcements/{$announcement->id}")
        ->assertNotFound();
});

it('ensures group chat thread and includes active members', function () {
    $owner = groupWorkflowOwner();
    $admin = groupWorkflowOwner();
    $group = groupWorkflowCreateWithOwner($owner);

    $group->members()->create([
        'user_id' => $admin->id,
        'role' => GroupMemberRole::Admin,
        'status' => GroupMemberStatus::Active,
        'joined_at' => now(),
        'handled_by_user_id' => $owner->id,
        'handled_at' => now(),
    ]);

    Sanctum::actingAs($owner);
    $response = $this->postJson("/api/v1/groups/{$group->slug}/chat-thread");

    $response
        ->assertCreated()
        ->assertJsonPath('data.thread.group.slug', $group->slug);

    $threadId = $response->json('data.thread.id');
    $thread = ChatThread::query()->with('memberRecords')->findOrFail($threadId);

    expect($thread->memberRecords->pluck('user_id')->sort()->values()->all())
        ->toBe(collect([$admin->id, $owner->id])->sort()->values()->all());
});
