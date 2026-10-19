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
    GroupVisibility $visibility = GroupVisibility::Public,
    ?string $slug = null
): Group {
    /** @var Group $group */
    $group = Group::query()->create([
        'name' => 'Group ' . fake()->unique()->word(),
        'slug' => $slug ?? fake()->unique()->slug(),
        'description' => 'Descrizione gruppo',
        'visibility' => $visibility,
        'is_listed' => true,
        'group_category_id' => groupTestCategory()->id,
        'is_active' => true,
    ]);

    $group->members()->create([
        'user_id' => $owner->id,
        'role' => GroupMemberRole::Admin,
        'status' => GroupMemberStatus::Active,
        'joined_at' => now(),
        'handled_by_user_id' => $owner->id,
        'handled_at' => now(),
    ]);

    return $group;
}

it('requires authentication to list groups', function () {
    $this->getJson('/api/v1/groups')->assertUnauthorized();
    $this->getJson('/api/v1/groups/slam-rome')->assertUnauthorized();
});

it('lists listed groups and supports search query', function () {
    $owner = groupWorkflowOwner();
    $publicGroup = groupWorkflowCreateWithOwner($owner, GroupVisibility::Public, 'slam-rome');
    $publicGroup->update([
        'name' => 'Slam Rome Centro',
        'description' => 'Open mic poetry',
    ]);
    $unlisted = groupWorkflowCreateWithOwner($owner, GroupVisibility::Private, 'private-slam');
    $unlisted->update(['is_listed' => false]);

    Sanctum::actingAs($owner);

    $response = $this->getJson('/api/v1/groups?q=rome');

    $response
        ->assertOk()
        ->assertJsonCount(1, 'data.groups')
        ->assertJsonPath('data.groups.0.slug', 'slam-rome');
});

it('shows listed private groups to authenticated users and hides unlisted ones', function () {
    $owner = groupWorkflowOwner();
    $other = groupWorkflowUser();
    $listed = groupWorkflowCreateWithOwner($owner, GroupVisibility::Private, 'private-listed-group');
    $unlisted = groupWorkflowCreateWithOwner($owner, GroupVisibility::Private, 'private-unlisted-group');
    $unlisted->update(['is_listed' => false]);

    Sanctum::actingAs($other);
    $this->getJson('/api/v1/groups/private-listed-group')
        ->assertOk()
        ->assertJsonPath('data.group.slug', $listed->slug);
    $this->getJson('/api/v1/groups/private-unlisted-group')->assertNotFound();

    Sanctum::actingAs($owner);
    $this->getJson('/api/v1/groups/private-unlisted-group')
        ->assertOk()
        ->assertJsonPath('data.group.slug', $unlisted->slug);
});

it('lists my groups including pending memberships', function () {
    $owner = groupWorkflowOwner();
    $user = groupWorkflowUser();
    $group = groupWorkflowCreateWithOwner($owner, GroupVisibility::Private);

    $group->members()->create([
        'user_id' => $user->id,
        'role' => GroupMemberRole::Follower,
        'status' => GroupMemberStatus::Pending,
    ]);

    Sanctum::actingAs($user);
    $this->getJson('/api/v1/my/groups')
        ->assertOk()
        ->assertJsonCount(1, 'data.groups')
        ->assertJsonPath('data.groups.0.current_user_membership.status', 'pending');
});

it('rejects leave for last active admin', function () {
    $owner = groupWorkflowOwner();
    $group = groupWorkflowCreateWithOwner($owner);

    Sanctum::actingAs($owner);
    $this->postJson("/api/v1/groups/{$group->slug}/leave")
        ->assertStatus(422)
        ->assertJsonPath('errors.fields.group.0', __('group.errors.last_admin'));
});

it('lists pending requests and supports reject flow by owner', function () {
    $owner = groupWorkflowOwner();
    $requester = groupWorkflowUser();
    $group = groupWorkflowCreateWithOwner($owner, GroupVisibility::Private);

    /** @var GroupMember $membership */
    $membership = $group->members()->create([
        'user_id' => $requester->id,
        'role' => GroupMemberRole::Follower,
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
        'role' => GroupMemberRole::Follower,
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
        'role' => GroupMemberRole::Editor,
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
    $groupA = groupWorkflowCreateWithOwner($owner, GroupVisibility::Public, 'group-a');
    $groupB = groupWorkflowCreateWithOwner($owner, GroupVisibility::Public, 'group-b');

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
    $response = $this->patchJson("/api/v1/groups/{$group->slug}/chat-threads", [
        'audiences' => ['public'],
    ]);

    $response
        ->assertOk()
        ->assertJsonPath('data.threads.0.group.slug', $group->slug);

    $threadId = $response->json('data.threads.0.id');
    $thread = ChatThread::query()->with('memberRecords')->findOrFail($threadId);

    expect($thread->memberRecords->pluck('user_id')->sort()->values()->all())
        ->toBe(collect([$admin->id, $owner->id])->sort()->values()->all());
});
