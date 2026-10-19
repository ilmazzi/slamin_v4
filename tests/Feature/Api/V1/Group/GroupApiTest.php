<?php

use Domain\Article\Models\Article;
use Domain\Event\Models\Event;
use Domain\Group\Enums\GroupMemberRole;
use Domain\Group\Enums\GroupMemberStatus;
use Domain\Group\Enums\GroupVisibility;
use Domain\Group\Models\Group;
use Domain\Poetry\Models\Poem;
use Domain\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

function groupOwner(): User
{
    return User::factory()->poetOrganizer()->create();
}

function createGroupWithOwner(User $owner, GroupVisibility $visibility = GroupVisibility::Public): Group
{
    $group = Group::query()->create([
        'name' => fake()->company(),
        'slug' => fake()->unique()->slug(),
        'description' => fake()->sentence(),
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

function groupEventPayload(array $overrides = []): array
{
    return array_replace_recursive([
        'title' => 'Slam Night',
        'description' => 'Evento del gruppo',
        'schedule_mode' => 'fixed',
        'delivery_mode' => 'online',
        'status' => 'draft',
        'occurrence' => [
            'starts_at' => now()->addDay()->toISOString(),
            'timezone' => 'UTC',
        ],
        'online' => [
            'meeting_url' => 'https://example.com/live',
            'timezone' => 'UTC',
        ],
    ], $overrides);
}

it('counts active group memberships on the dashboard', function () {
    $owner = groupOwner();
    createGroupWithOwner($owner);
    createGroupWithOwner($owner);

    Sanctum::actingAs($owner);
    $card = collect($this->getJson('/api/v1/me/dashboard-widgets')->assertOk()->json('data.community_cards'))
        ->firstWhere('id', 'groups');

    expect($card['count'])->toBe(2);
});

it('creates a group and assigns the creator as active admin', function () {
    $owner = groupOwner();
    Sanctum::actingAs($owner);

    $response = $this->postJson('/api/v1/groups', [
        'name' => 'Slam Roma Centro',
        'description' => 'Gruppo di prova',
        'visibility' => 'public',
        'group_category_id' => groupTestCategory()->id,
        'website_url' => 'https://example.com',
    ]);

    $response
        ->assertOk()
        ->assertJsonPath('data.group.name', 'Slam Roma Centro')
        ->assertJsonPath('data.group.visibility', 'public')
        ->assertJsonPath('data.group.is_listed', true)
        ->assertJsonPath('data.group.current_user_membership.role', 'admin')
        ->assertJsonPath('data.group.current_user_membership.status', 'active');

    $groupId = $response->json('data.group.id');

    $this->assertDatabaseHas('group_members', [
        'group_id' => $groupId,
        'user_id' => $owner->id,
        'role' => 'admin',
        'status' => 'active',
    ]);
});

it('stores a group image and returns its public url', function () {
    Storage::fake('public');

    $owner = groupOwner();
    Sanctum::actingAs($owner);

    $response = $this->post('/api/v1/groups', [
        'name' => 'Slam con immagine',
        'description' => 'Gruppo di prova',
        'visibility' => 'public',
        'group_category_id' => groupTestCategory()->id,
        'image' => UploadedFile::fake()->image('cover.jpg', 800, 500),
    ]);

    $response
        ->assertOk()
        ->assertJsonPath('data.group.name', 'Slam con immagine');

    $imagePath = $response->json('data.group.image_path');
    $imageUrl = $response->json('data.group.image_url');

    expect($imagePath)->toBeString()->not->toBeEmpty();
    expect($imageUrl)->toBeString()->toContain($imagePath);
    expect(Storage::disk('public')->exists($imagePath))->toBeTrue();
});

it('allows a group admin to update the group', function () {
    $owner = groupOwner();
    $group = createGroupWithOwner($owner, GroupVisibility::Public);
    Sanctum::actingAs($owner);

    $this->patchJson("/api/v1/groups/{$group->slug}", [
        'name' => 'Slam Roma aggiornato',
        'description' => 'Nuova descrizione',
        'visibility' => 'private',
        'is_listed' => true,
        'group_category_id' => $group->group_category_id,
    ])
        ->assertOk()
        ->assertJsonPath('data.group.name', 'Slam Roma aggiornato')
        ->assertJsonPath('data.group.visibility', 'private')
        ->assertJsonPath('data.group.is_listed', true)
        ->assertJsonPath('data.group.description', 'Nuova descrizione');
});

it('forbids followers from updating a group', function () {
    $owner = groupOwner();
    $follower = User::factory()->create();
    $group = createGroupWithOwner($owner, GroupVisibility::Public);

    $group->members()->create([
        'user_id' => $follower->id,
        'role' => GroupMemberRole::Follower,
        'status' => GroupMemberStatus::Active,
        'joined_at' => now(),
    ]);

    Sanctum::actingAs($follower);

    $this->patchJson("/api/v1/groups/{$group->slug}", [
        'name' => 'Non dovresti',
        'visibility' => 'public',
        'group_category_id' => $group->group_category_id,
    ])->assertForbidden();
});

it('allows direct follow for public groups', function () {
    $owner = groupOwner();
    $member = User::factory()->create();
    $group = createGroupWithOwner($owner, GroupVisibility::Public);

    Sanctum::actingAs($member);

    $this->postJson("/api/v1/groups/{$group->slug}/follow")
        ->assertOk()
        ->assertJsonPath('data.membership.role', 'follower')
        ->assertJsonPath('data.membership.status', 'active')
        ->assertJsonPath('data.membership.requested_role', null);

    $this->assertDatabaseHas('group_members', [
        'group_id' => $group->id,
        'user_id' => $member->id,
        'role' => 'follower',
        'status' => 'active',
    ]);
});

it('creates a pending request for private groups and notifies admins', function () {
    $owner = groupOwner();
    $requester = User::factory()->create();
    $group = createGroupWithOwner($owner, GroupVisibility::Private);

    Sanctum::actingAs($requester);

    $this->postJson("/api/v1/groups/{$group->slug}/follow")
        ->assertOk()
        ->assertJsonPath('data.membership.status', 'pending');

    $this->assertDatabaseHas('group_members', [
        'group_id' => $group->id,
        'user_id' => $requester->id,
        'status' => 'pending',
    ]);

    $this->assertDatabaseHas('notifications', [
        'user_id' => $owner->id,
        'type' => 'group_follow_requested',
    ]);
});

it('allows admins to approve private join requests', function () {
    $owner = groupOwner();
    $requester = User::factory()->create();
    $group = createGroupWithOwner($owner, GroupVisibility::Private);

    $membership = $group->members()->create([
        'user_id' => $requester->id,
        'role' => GroupMemberRole::Follower,
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

it('supports multiple admins and lets an admin leave when another admin remains', function () {
    $firstOwner = groupOwner();
    $secondOwner = groupOwner();
    $group = createGroupWithOwner($firstOwner);

    $membership = $group->members()->create([
        'user_id' => $secondOwner->id,
        'role' => GroupMemberRole::Follower,
        'status' => GroupMemberStatus::Active,
        'joined_at' => now(),
        'handled_by_user_id' => $firstOwner->id,
        'handled_at' => now(),
    ]);

    Sanctum::actingAs($firstOwner);

    $this->patchJson("/api/v1/groups/{$group->slug}/members/{$membership->id}/role", [
        'role' => 'admin',
    ])
        ->assertOk()
        ->assertJsonPath('data.membership.role', 'admin');

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
        'role' => 'admin',
        'status' => 'active',
    ]);
});

it('allows editors to publish announcements and notifies active members', function () {
    $owner = groupOwner();
    $moderator = groupOwner();
    $member = User::factory()->create();
    $group = createGroupWithOwner($owner);

    $group->members()->create([
        'user_id' => $moderator->id,
        'role' => GroupMemberRole::Editor,
        'status' => GroupMemberStatus::Active,
        'joined_at' => now(),
        'handled_by_user_id' => $owner->id,
        'handled_at' => now(),
    ]);

    $group->members()->create([
        'user_id' => $member->id,
        'role' => GroupMemberRole::Follower,
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

    $this->postJson('/api/v1/events', groupEventPayload([
        'group_ids' => [$group->id],
    ]))
        ->assertOk()
        ->assertJsonPath('data.event.group_ids.0', $group->id);

    $event = Event::query()->where('slug', 'slam-night')->firstOrFail();

    $this->assertDatabaseHas('group_content_links', [
        'content_type' => 'event',
        'content_id' => $event->id,
        'group_id' => $group->id,
        'status' => 'accepted',
        'source' => 'staff',
    ]);

    $this->getJson("/api/v1/groups/{$group->slug}/events")
        ->assertOk()
        ->assertJsonCount(1, 'data.events')
        ->assertJsonPath('data.events.0.slug', 'slam-night');

    $this->getJson("/api/v1/events/{$event->slug}")
        ->assertOk()
        ->assertJsonPath('data.event.groups.0.slug', $group->slug)
        ->assertJsonPath('data.event.groups.0.name', $group->name);
});

it('creates an active follower with a pending staff role request on public groups', function () {
    $owner = groupOwner();
    $requester = User::factory()->create();
    $group = createGroupWithOwner($owner, GroupVisibility::Public);

    Sanctum::actingAs($requester);

    $this->postJson("/api/v1/groups/{$group->slug}/role-requests", [
        'requested_role' => 'editor',
    ])
        ->assertOk()
        ->assertJsonPath('data.membership.role', 'follower')
        ->assertJsonPath('data.membership.status', 'active')
        ->assertJsonPath('data.membership.requested_role', 'editor');

    $this->assertDatabaseHas('notifications', [
        'user_id' => $owner->id,
        'type' => 'group_role_requested',
    ]);

    Sanctum::actingAs($owner);
    $membershipId = $group->members()->where('user_id', $requester->id)->value('id');

    $this->postJson("/api/v1/groups/{$group->slug}/requests/{$membershipId}/approve")
        ->assertOk()
        ->assertJsonPath('data.membership.role', 'editor')
        ->assertJsonPath('data.membership.requested_role', null);
});

it('rejects a staff role request without removing an active follower', function () {
    $owner = groupOwner();
    $follower = User::factory()->create();
    $group = createGroupWithOwner($owner, GroupVisibility::Public);

    $membership = $group->members()->create([
        'user_id' => $follower->id,
        'role' => GroupMemberRole::Follower,
        'status' => GroupMemberStatus::Active,
        'joined_at' => now(),
    ]);

    Sanctum::actingAs($follower);
    $this->postJson("/api/v1/groups/{$group->slug}/role-requests", [
        'requested_role' => 'admin',
    ])->assertOk();

    Sanctum::actingAs($owner);
    $this->postJson("/api/v1/groups/{$group->slug}/requests/{$membership->id}/reject")
        ->assertOk()
        ->assertJsonPath('data.membership.status', 'active')
        ->assertJsonPath('data.membership.role', 'follower')
        ->assertJsonPath('data.membership.requested_role', null);
});

it('forbids follow on unlisted private groups and allows admin invites', function () {
    $owner = groupOwner();
    $outsider = User::factory()->create();
    $group = createGroupWithOwner($owner, GroupVisibility::Private);
    $group->update(['is_listed' => false]);

    Sanctum::actingAs($outsider);
    $this->postJson("/api/v1/groups/{$group->slug}/follow")->assertForbidden();
    $this->postJson("/api/v1/groups/{$group->slug}/role-requests", [
        'requested_role' => 'editor',
    ])->assertForbidden();

    Sanctum::actingAs($owner);
    $this->postJson("/api/v1/groups/{$group->slug}/invites", [
        'user_id' => $outsider->id,
        'role' => 'editor',
    ])
        ->assertOk()
        ->assertJsonPath('data.membership.role', 'editor')
        ->assertJsonPath('data.membership.status', 'active');

    $this->assertDatabaseHas('notifications', [
        'user_id' => $outsider->id,
        'type' => 'group_invited',
    ]);
});

it('lists active members to group members and hides them from outsiders', function () {
    $owner = groupOwner();
    $outsider = User::factory()->create();
    $group = createGroupWithOwner($owner, GroupVisibility::Public);

    Sanctum::actingAs($owner);
    $this->getJson("/api/v1/groups/{$group->slug}/members")
        ->assertOk()
        ->assertJsonPath('data.members.0.user.id', $owner->id)
        ->assertJsonPath('data.members.0.role', 'admin');

    Sanctum::actingAs($outsider);
    $this->getJson("/api/v1/groups/{$group->slug}/members")->assertForbidden();
});

it('lets admins search users to invite without exposing email', function () {
    $owner = groupOwner();
    $matching = User::factory()->create();
    $matching->profile()->update([
        'display_name' => 'Ada Test',
        'nickname' => 'ada-test',
        'is_public' => true,
    ]);
    $group = createGroupWithOwner($owner, GroupVisibility::Public);

    Sanctum::actingAs($owner);
    $this->getJson("/api/v1/groups/{$group->slug}/invite-users?q=Ada")
        ->assertOk()
        ->assertJsonPath('data.users.0.id', $matching->id)
        ->assertJsonPath('data.users.0.display_name', 'Ada Test')
        ->assertJsonMissing(['email' => $matching->email]);

    Sanctum::actingAs($matching);
    $this->getJson("/api/v1/groups/{$group->slug}/invite-users?q=Ada")->assertForbidden();
});

it('hides the private group feed from non members', function () {
    $owner = groupOwner();
    $outsider = User::factory()->create();
    $group = createGroupWithOwner($owner, GroupVisibility::Private);

    $group->announcements()->create([
        'author_user_id' => $owner->id,
        'title' => 'Solo per membri',
        'content' => 'Testo interno',
        'published_at' => now(),
    ]);

    Sanctum::actingAs($outsider);
    $this->getJson("/api/v1/groups/{$group->slug}")->assertOk();
    $this->getJson("/api/v1/groups/{$group->slug}/announcements")->assertForbidden();

    Sanctum::actingAs($owner);
    $this->getJson("/api/v1/groups/{$group->slug}/announcements")
        ->assertOk()
        ->assertJsonPath('data.announcements.0.title', 'Solo per membri');
});

it('keeps outsider event proposals pending until staff accepts them', function () {
    $owner = groupOwner();
    $organizer = groupOwner();
    $group = createGroupWithOwner($owner, GroupVisibility::Public);

    Sanctum::actingAs($organizer);
    $this->postJson('/api/v1/events', groupEventPayload([
        'title' => 'Proposta esterna',
        'group_ids' => [$group->id],
    ]))
        ->assertOk()
        ->assertJsonCount(0, 'data.event.groups');

    $event = Event::query()->where('slug', 'proposta-esterna')->firstOrFail();

    $this->assertDatabaseHas('group_content_links', [
        'group_id' => $group->id,
        'content_id' => $event->id,
        'status' => 'pending',
        'source' => 'direct',
    ]);

    $this->assertDatabaseHas('notifications', [
        'user_id' => $owner->id,
        'type' => 'group_event_proposed',
    ]);

    Sanctum::actingAs($owner);
    $this->getJson("/api/v1/groups/{$group->slug}/events")
        ->assertOk()
        ->assertJsonCount(0, 'data.events');

    $linkId = $this->getJson("/api/v1/groups/{$group->slug}/content-links?status=pending")
        ->assertOk()
        ->assertJsonCount(1, 'data.links')
        ->json('data.links.0.id');

    $this->postJson("/api/v1/groups/{$group->slug}/content-links/{$linkId}/accept")
        ->assertOk()
        ->assertJsonPath('data.link.status', 'accepted');

    $this->getJson("/api/v1/groups/{$group->slug}/events")
        ->assertOk()
        ->assertJsonPath('data.events.0.slug', 'proposta-esterna');

    $this->assertDatabaseHas('notifications', [
        'user_id' => $organizer->id,
        'type' => 'group_event_accepted',
    ]);
});

it('auto accepts event proposals when the group setting is enabled', function () {
    $owner = groupOwner();
    $organizer = groupOwner();
    $group = createGroupWithOwner($owner, GroupVisibility::Public);
    $group->update(['auto_accept_events' => true]);

    Sanctum::actingAs($organizer);
    $this->postJson('/api/v1/events', groupEventPayload([
        'title' => 'Auto accettato',
        'group_ids' => [$group->id],
    ]))->assertOk();

    $event = Event::query()->where('slug', 'auto-accettato')->firstOrFail();

    $this->assertDatabaseHas('group_content_links', [
        'group_id' => $group->id,
        'content_id' => $event->id,
        'status' => 'accepted',
        'source' => 'direct',
    ]);

    Sanctum::actingAs($owner);
    $this->getJson("/api/v1/groups/{$group->slug}/events")
        ->assertOk()
        ->assertJsonPath('data.events.0.slug', 'auto-accettato');
});

it('forbids proposing events to unlisted private groups and hides them from search', function () {
    $owner = groupOwner();
    $organizer = groupOwner();
    $group = createGroupWithOwner($owner, GroupVisibility::Private);
    $group->update([
        'name' => 'Circolo Segreto',
        'is_listed' => false,
    ]);

    Sanctum::actingAs($organizer);
    $this->postJson('/api/v1/events', groupEventPayload([
        'group_ids' => [$group->id],
    ]))->assertUnprocessable();

    $this->getJson('/api/v1/groups/propose?q=Circolo')
        ->assertOk()
        ->assertJsonCount(0, 'data.groups');
});

it('finds groups by a partial case-insensitive name in event and affiliation search', function () {
    $owner = groupOwner();
    $otherOwner = groupOwner();
    $group = createGroupWithOwner($owner);
    $group->update(['name' => 'Gruppo di Test']);
    $source = createGroupWithOwner($otherOwner);
    $source->update(['name' => 'Altro Circolo']);

    Sanctum::actingAs($otherOwner);
    $this->getJson('/api/v1/groups/propose?q=test')
        ->assertOk()
        ->assertJsonPath('data.groups.0.id', $group->id);

    $this->getJson('/api/v1/groups/propose?q=gruppo test')
        ->assertOk()
        ->assertJsonPath('data.groups.0.id', $group->id);

    $this->getJson("/api/v1/groups/{$source->slug}/affiliable?q=test")
        ->assertOk()
        ->assertJsonPath('data.groups.0.id', $group->id);
});

it('lets staff reject and remove affiliated events', function () {
    $owner = groupOwner();
    $organizer = groupOwner();
    $group = createGroupWithOwner($owner, GroupVisibility::Public);

    Sanctum::actingAs($organizer);
    $this->postJson('/api/v1/events', groupEventPayload([
        'title' => 'Da rifiutare',
        'group_ids' => [$group->id],
    ]))->assertOk();

    Sanctum::actingAs($owner);
    $linkId = $this->getJson("/api/v1/groups/{$group->slug}/content-links?status=pending")
        ->assertOk()
        ->json('data.links.0.id');

    $this->postJson("/api/v1/groups/{$group->slug}/content-links/{$linkId}/reject")
        ->assertOk()
        ->assertJsonPath('data.link.status', 'rejected');

    $this->getJson("/api/v1/groups/{$group->slug}/events")
        ->assertOk()
        ->assertJsonCount(0, 'data.events');

    Sanctum::actingAs($owner);
    $this->postJson('/api/v1/events', groupEventPayload([
        'title' => 'Da togliere',
        'group_ids' => [$group->id],
    ]))->assertOk();

    $acceptedId = $this->getJson("/api/v1/groups/{$group->slug}/content-links?status=accepted")
        ->assertOk()
        ->json('data.links.0.id');

    $this->deleteJson("/api/v1/groups/{$group->slug}/content-links/{$acceptedId}")
        ->assertOk()
        ->assertJsonPath('data.link.status', 'removed');

    $this->getJson("/api/v1/groups/{$group->slug}/events")
        ->assertOk()
        ->assertJsonCount(0, 'data.events');
});

it('hides unlisted private groups from the public event page', function () {
    $owner = groupOwner();
    $outsider = groupOwner();
    $hidden = createGroupWithOwner($owner, GroupVisibility::Private);
    $hidden->update([
        'name' => 'Circolo Nascosto',
        'is_listed' => false,
    ]);
    $listed = createGroupWithOwner($owner, GroupVisibility::Private);
    $listed->update(['name' => 'Circolo In Elenco']);

    Sanctum::actingAs($owner);
    $this->postJson('/api/v1/events', groupEventPayload([
        'title' => 'Serata mista',
        'status' => 'published',
        'is_public' => true,
        'group_ids' => [$hidden->id, $listed->id],
    ]))->assertOk();

    $event = Event::query()->where('slug', 'serata-mista')->firstOrFail();
    $event->update([
        'status' => 'published',
        'is_public' => true,
        'published_at' => now(),
    ]);

    $this->getJson("/api/v1/events/{$event->slug}")
        ->assertOk()
        ->assertJsonCount(2, 'data.event.groups');

    Sanctum::actingAs($outsider);
    $this->getJson("/api/v1/events/{$event->slug}")
        ->assertOk()
        ->assertJsonCount(1, 'data.event.groups')
        ->assertJsonPath('data.event.groups.0.slug', $listed->slug);
});

it('lets a child request affiliation and the parent counterpart accept it', function () {
    $parentOwner = groupOwner();
    $childOwner = groupOwner();
    $parent = createGroupWithOwner($parentOwner);
    $child = createGroupWithOwner($childOwner);
    $parent->update(['name' => 'Lega Nazionale']);
    $child->update(['name' => 'Slam Milano']);

    Sanctum::actingAs($childOwner);
    $this->postJson("/api/v1/groups/{$child->slug}/affiliations", [
        'parent_group_id' => $parent->id,
    ])
        ->assertOk()
        ->assertJsonPath('data.affiliation.status', 'pending')
        ->assertJsonPath('data.affiliation.parent_group_id', $parent->id)
        ->assertJsonPath('data.affiliation.child_group_id', $child->id)
        ->assertJsonPath('data.affiliation.initiated_by_group_id', $child->id);

    $this->assertDatabaseHas('notifications', [
        'user_id' => $parentOwner->id,
        'type' => 'group_affiliation_requested',
    ]);

    $affiliationId = $this->getJson("/api/v1/groups/{$child->slug}/affiliations")
        ->assertOk()
        ->assertJsonCount(1, 'data.outgoing')
        ->json('data.outgoing.0.id');

    Sanctum::actingAs($childOwner);
    $this->postJson("/api/v1/groups/{$child->slug}/affiliations/{$affiliationId}/accept")
        ->assertUnprocessable();

    Sanctum::actingAs($parentOwner);
    $this->getJson("/api/v1/groups/{$parent->slug}/affiliations")
        ->assertOk()
        ->assertJsonCount(1, 'data.incoming');

    $this->postJson("/api/v1/groups/{$parent->slug}/affiliations/{$affiliationId}/accept")
        ->assertOk()
        ->assertJsonPath('data.affiliation.status', 'accepted');

    $this->getJson("/api/v1/groups/{$parent->slug}/affiliations")
        ->assertOk()
        ->assertJsonCount(1, 'data.children')
        ->assertJsonCount(0, 'data.incoming');
});

it('lets a parent invite a child and the child counterpart accept it', function () {
    $parentOwner = groupOwner();
    $childOwner = groupOwner();
    $parent = createGroupWithOwner($parentOwner);
    $child = createGroupWithOwner($childOwner);

    Sanctum::actingAs($parentOwner);
    $affiliationId = $this->postJson("/api/v1/groups/{$parent->slug}/affiliations", [
        'child_group_id' => $child->id,
    ])
        ->assertOk()
        ->assertJsonPath('data.affiliation.initiated_by_group_id', $parent->id)
        ->json('data.affiliation.id');

    Sanctum::actingAs($childOwner);
    $this->postJson("/api/v1/groups/{$child->slug}/affiliations/{$affiliationId}/accept")
        ->assertOk()
        ->assertJsonPath('data.affiliation.status', 'accepted');
});

it('rejects self affiliations and cycles', function () {
    $owner = groupOwner();
    $a = createGroupWithOwner($owner);
    $b = createGroupWithOwner($owner);
    $c = createGroupWithOwner($owner);

    Sanctum::actingAs($owner);
    $this->postJson("/api/v1/groups/{$a->slug}/affiliations", [
        'parent_group_id' => $a->id,
    ])->assertUnprocessable();

    $ab = $this->postJson("/api/v1/groups/{$a->slug}/affiliations", [
        'parent_group_id' => $b->id,
    ])->assertOk()->json('data.affiliation.id');

    $this->postJson("/api/v1/groups/{$b->slug}/affiliations/{$ab}/accept")->assertOk();

    $bc = $this->postJson("/api/v1/groups/{$b->slug}/affiliations", [
        'parent_group_id' => $c->id,
    ])->assertOk()->json('data.affiliation.id');

    $this->postJson("/api/v1/groups/{$c->slug}/affiliations/{$bc}/accept")->assertOk();

    $this->postJson("/api/v1/groups/{$c->slug}/affiliations", [
        'parent_group_id' => $a->id,
    ])->assertUnprocessable();
});

it('forwards accepted child events to the parent as pending', function () {
    $parentOwner = groupOwner();
    $childOwner = groupOwner();
    $parent = createGroupWithOwner($parentOwner);
    $child = createGroupWithOwner($childOwner);

    Sanctum::actingAs($childOwner);
    $this->postJson('/api/v1/events', groupEventPayload([
        'title' => 'Serata figlia',
        'group_ids' => [$child->id],
    ]))->assertOk();

    $event = Event::query()->where('slug', 'serata-figlia')->firstOrFail();

    $affiliationId = $this->postJson("/api/v1/groups/{$child->slug}/affiliations", [
        'parent_group_id' => $parent->id,
    ])->assertOk()->json('data.affiliation.id');

    Sanctum::actingAs($parentOwner);
    $this->postJson("/api/v1/groups/{$parent->slug}/affiliations/{$affiliationId}/accept")->assertOk();

    $this->assertDatabaseHas('group_content_links', [
        'group_id' => $parent->id,
        'content_id' => $event->id,
        'status' => 'pending',
        'source' => 'child_forward',
        'source_group_id' => $child->id,
    ]);

    $this->getJson("/api/v1/groups/{$parent->slug}/events")
        ->assertOk()
        ->assertJsonCount(0, 'data.events');

    $linkId = $this->getJson("/api/v1/groups/{$parent->slug}/content-links?status=pending")
        ->assertOk()
        ->assertJsonPath('data.links.0.source', 'child_forward')
        ->json('data.links.0.id');

    $this->postJson("/api/v1/groups/{$parent->slug}/content-links/{$linkId}/accept")->assertOk();

    $this->getJson("/api/v1/groups/{$parent->slug}/events")
        ->assertOk()
        ->assertJsonPath('data.events.0.slug', 'serata-figlia');
});

it('auto accepts forwarded child events when the parent setting is enabled', function () {
    $parentOwner = groupOwner();
    $childOwner = groupOwner();
    $grandOwner = groupOwner();
    $grand = createGroupWithOwner($grandOwner);
    $parent = createGroupWithOwner($parentOwner);
    $child = createGroupWithOwner($childOwner);
    $grand->update(['auto_accept_events' => true]);
    $parent->update(['auto_accept_events' => true]);

    Sanctum::actingAs($parentOwner);
    $parentToGrand = $this->postJson("/api/v1/groups/{$parent->slug}/affiliations", [
        'parent_group_id' => $grand->id,
    ])->assertOk()->json('data.affiliation.id');

    Sanctum::actingAs($grandOwner);
    $this->postJson("/api/v1/groups/{$grand->slug}/affiliations/{$parentToGrand}/accept")->assertOk();

    Sanctum::actingAs($childOwner);
    $this->postJson('/api/v1/events', groupEventPayload([
        'title' => 'Cascata',
        'group_ids' => [$child->id],
    ]))->assertOk();

    $event = Event::query()->where('slug', 'cascata')->firstOrFail();

    $childToParent = $this->postJson("/api/v1/groups/{$child->slug}/affiliations", [
        'parent_group_id' => $parent->id,
    ])->assertOk()->json('data.affiliation.id');

    Sanctum::actingAs($parentOwner);
    $this->postJson("/api/v1/groups/{$parent->slug}/affiliations/{$childToParent}/accept")->assertOk();

    $this->assertDatabaseHas('group_content_links', [
        'group_id' => $parent->id,
        'content_id' => $event->id,
        'status' => 'accepted',
        'source' => 'child_forward',
    ]);

    $this->assertDatabaseHas('group_content_links', [
        'group_id' => $grand->id,
        'content_id' => $event->id,
        'status' => 'accepted',
        'source' => 'child_forward',
    ]);
});

it('does not remove forwarded parent links when the event group list is updated', function () {
    $parentOwner = groupOwner();
    $childOwner = groupOwner();
    $parent = createGroupWithOwner($parentOwner);
    $child = createGroupWithOwner($childOwner);

    Sanctum::actingAs($childOwner);
    $this->postJson('/api/v1/events', groupEventPayload([
        'title' => 'Da aggiornare',
        'group_ids' => [$child->id],
    ]))->assertOk();

    $event = Event::query()->where('slug', 'da-aggiornare')->firstOrFail();

    $affiliationId = $this->postJson("/api/v1/groups/{$child->slug}/affiliations", [
        'parent_group_id' => $parent->id,
    ])->assertOk()->json('data.affiliation.id');

    Sanctum::actingAs($parentOwner);
    $this->postJson("/api/v1/groups/{$parent->slug}/affiliations/{$affiliationId}/accept")->assertOk();

    Sanctum::actingAs($childOwner);
    $this->patchJson("/api/v1/events/{$event->id}", groupEventPayload([
        'title' => 'Da aggiornare',
        'group_ids' => [$child->id],
    ]))->assertOk();

    $this->assertDatabaseHas('group_content_links', [
        'group_id' => $parent->id,
        'content_id' => $event->id,
        'status' => 'pending',
        'source' => 'child_forward',
    ]);
});

it('lets editors view affiliations but not request them', function () {
    $owner = groupOwner();
    $editor = groupOwner();
    $other = createGroupWithOwner(groupOwner());
    $group = createGroupWithOwner($owner);
    $group->members()->create([
        'user_id' => $editor->id,
        'role' => GroupMemberRole::Editor,
        'status' => GroupMemberStatus::Active,
        'joined_at' => now(),
    ]);

    Sanctum::actingAs($editor);
    $this->getJson("/api/v1/groups/{$group->slug}/affiliations")->assertOk();
    $this->postJson("/api/v1/groups/{$group->slug}/affiliations", [
        'parent_group_id' => $other->id,
    ])->assertForbidden();
});

it('hides already related groups from affiliable search', function () {
    $owner = groupOwner();
    $parent = createGroupWithOwner($owner);
    $child = createGroupWithOwner($owner);
    $other = createGroupWithOwner($owner);
    $parent->update(['name' => 'Padre Search']);
    $child->update(['name' => 'Figlio Search']);
    $other->update(['name' => 'Altro Search']);

    Sanctum::actingAs($owner);
    $affiliationId = $this->postJson("/api/v1/groups/{$child->slug}/affiliations", [
        'parent_group_id' => $parent->id,
    ])->assertOk()->json('data.affiliation.id');

    $this->getJson("/api/v1/groups/{$child->slug}/affiliable?q=Search")
        ->assertOk()
        ->assertJsonCount(1, 'data.groups')
        ->assertJsonPath('data.groups.0.id', $other->id);

    $this->postJson("/api/v1/groups/{$parent->slug}/affiliations/{$affiliationId}/accept")->assertOk();

    $this->getJson("/api/v1/groups/{$child->slug}/affiliable?q=Search")
        ->assertOk()
        ->assertJsonCount(1, 'data.groups')
        ->assertJsonPath('data.groups.0.id', $other->id);
});

it('keeps outsider poem proposals pending until staff accepts them', function () {
    $owner = groupOwner();
    $author = groupOwner();
    $group = createGroupWithOwner($owner, GroupVisibility::Public);

    Sanctum::actingAs($author);
    $this->postJson('/api/v1/poems', [
        'title' => 'Poesia proposta',
        'content' => 'Verso di prova',
        'status' => 'published',
        'language' => 'it',
        'is_public' => true,
        'group_ids' => [$group->id],
    ])
        ->assertOk()
        ->assertJsonCount(0, 'data.poem.groups');

    $poem = Poem::query()->where('slug', 'poesia-proposta')->firstOrFail();

    $this->assertDatabaseHas('group_content_links', [
        'group_id' => $group->id,
        'content_id' => $poem->id,
        'content_type' => 'poem',
        'status' => 'pending',
        'source' => 'direct',
    ]);

    Sanctum::actingAs($owner);
    $this->getJson("/api/v1/groups/{$group->slug}/publications")
        ->assertOk()
        ->assertJsonCount(0, 'data.poems');

    $linkId = $this->getJson("/api/v1/groups/{$group->slug}/content-links?status=pending")
        ->assertOk()
        ->assertJsonPath('data.links.0.content_type', 'poem')
        ->json('data.links.0.id');

    $this->postJson("/api/v1/groups/{$group->slug}/content-links/{$linkId}/accept")
        ->assertOk()
        ->assertJsonPath('data.link.status', 'accepted');

    $this->getJson("/api/v1/groups/{$group->slug}/publications")
        ->assertOk()
        ->assertJsonPath('data.poems.0.slug', 'poesia-proposta');

    $this->getJson("/api/v1/poems/{$poem->slug}")
        ->assertOk()
        ->assertJsonPath('data.poem.groups.0.slug', $group->slug);
});

it('auto accepts poem proposals from group staff', function () {
    $owner = groupOwner();
    $group = createGroupWithOwner($owner, GroupVisibility::Public);

    Sanctum::actingAs($owner);
    $this->postJson('/api/v1/poems', [
        'title' => 'Poesia staff',
        'content' => 'Verso interno',
        'status' => 'published',
        'language' => 'it',
        'is_public' => true,
        'group_ids' => [$group->id],
    ])->assertOk();

    $poem = Poem::query()->where('slug', 'poesia-staff')->firstOrFail();

    $this->assertDatabaseHas('group_content_links', [
        'group_id' => $group->id,
        'content_id' => $poem->id,
        'content_type' => 'poem',
        'status' => 'accepted',
        'source' => 'staff',
    ]);
});

it('keeps outsider article proposals pending until staff accepts them', function () {
    $owner = groupOwner();
    $author = groupOwner();
    $group = createGroupWithOwner($owner, GroupVisibility::Public);

    Sanctum::actingAs($author);
    $this->postJson('/api/v1/articles', [
        'title' => 'Articolo proposto',
        'content' => 'Testo articolo',
        'status' => 'published',
        'language' => 'it',
        'is_public' => true,
        'group_ids' => [$group->id],
    ])
        ->assertOk()
        ->assertJsonCount(0, 'data.article.groups');

    $article = Article::query()->where('slug', 'articolo-proposto')->firstOrFail();

    $this->assertDatabaseHas('group_content_links', [
        'group_id' => $group->id,
        'content_id' => $article->id,
        'content_type' => 'article',
        'status' => 'pending',
    ]);

    Sanctum::actingAs($owner);
    $linkId = $this->getJson("/api/v1/groups/{$group->slug}/content-links?status=pending")
        ->assertOk()
        ->json('data.links.0.id');

    $this->postJson("/api/v1/groups/{$group->slug}/content-links/{$linkId}/accept")->assertOk();

    $this->getJson("/api/v1/groups/{$group->slug}/publications")
        ->assertOk()
        ->assertJsonPath('data.articles.0.slug', 'articolo-proposto');
});

it('likes comments and reports a public group', function () {
    $owner = groupOwner();
    $reader = groupOwner();
    $group = createGroupWithOwner($owner);

    Sanctum::actingAs($reader);

    $this->postJson("/api/v1/groups/{$group->id}/like")
        ->assertOk()
        ->assertJsonPath('data.group.is_liked', true)
        ->assertJsonPath('data.group.likes_count', 1);

    $this->postJson("/api/v1/groups/{$group->slug}/like")
        ->assertOk()
        ->assertJsonPath('data.group.likes_count', 1);

    $this->postJson("/api/v1/groups/{$group->id}/comments", [
        'content' => 'Bella pagina',
    ])->assertOk();

    $this->getJson("/api/v1/groups/{$group->slug}/comments")
        ->assertOk()
        ->assertJsonPath('data.0.content', 'Bella pagina');

    $this->deleteJson("/api/v1/groups/{$group->id}/like")
        ->assertOk()
        ->assertJsonPath('data.group.is_liked', false);

    $this->postJson("/api/v1/groups/{$group->id}/reports", [
        'reason' => 'spam',
    ])->assertOk();

    $this->getJson("/api/v1/groups/{$group->slug}")
        ->assertOk()
        ->assertJsonPath('data.group.likes_count', 0)
        ->assertJsonPath('data.group.comments_count', 1)
        ->assertJsonPath('data.group.is_liked', false);
});

it('lets listed private visitors like but not comment', function () {
    $owner = groupOwner();
    $visitor = groupOwner();
    $group = createGroupWithOwner($owner, GroupVisibility::Private);

    Sanctum::actingAs($visitor);
    $this->postJson("/api/v1/groups/{$group->id}/like")->assertOk();
    $this->postJson("/api/v1/groups/{$group->id}/comments", [
        'content' => 'Ciao',
    ])->assertForbidden();
    $this->getJson("/api/v1/groups/{$group->slug}/comments")->assertForbidden();
});

it('hides social actions on unlisted private groups', function () {
    $owner = groupOwner();
    $visitor = groupOwner();
    $group = createGroupWithOwner($owner, GroupVisibility::Private);
    $group->update(['is_listed' => false]);

    Sanctum::actingAs($visitor);
    $this->postJson("/api/v1/groups/{$group->id}/like")->assertForbidden();
    $this->postJson("/api/v1/groups/{$group->id}/comments", [
        'content' => 'Ciao',
    ])->assertForbidden();
});

it('forbids proposing poems to unlisted private groups', function () {
    $owner = groupOwner();
    $author = groupOwner();
    $group = createGroupWithOwner($owner, GroupVisibility::Private);
    $group->update(['is_listed' => false]);

    Sanctum::actingAs($author);
    $this->postJson('/api/v1/poems', [
        'title' => 'Poesia nascosta',
        'content' => 'Verso',
        'group_ids' => [$group->id],
    ])->assertUnprocessable();
});

