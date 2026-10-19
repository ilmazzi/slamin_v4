<?php

use App\Events\ChatMessageSent;
use Domain\Chat\Models\ChatMessageAttachment;
use Domain\Chat\Models\ChatThread;
use Domain\Group\Enums\GroupMemberRole;
use Domain\Group\Enums\GroupMemberStatus;
use Domain\Group\Enums\GroupVisibility;
use Domain\Group\Models\Group;
use Domain\Profile\Models\Profile;
use Domain\User\Models\User;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

function chatPoetOrganizer(): User
{
    return User::factory()->poetOrganizer()->create();
}

function createChatGroupWithOwner(User $owner, GroupVisibility $visibility = GroupVisibility::Public): Group
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

it('creates a direct thread once for the same user pair', function () {
    $firstUser = User::factory()->create();
    $secondUser = User::factory()->create();

    Sanctum::actingAs($firstUser);

    $firstResponse = $this->postJson('/api/v1/chat/direct-threads', [
        'user_id' => $secondUser->id,
    ]);

    $firstResponse
        ->assertCreated()
        ->assertJsonPath('data.thread.type', 'direct')
        ->assertJsonPath('data.thread.other_user.id', $secondUser->id);

    $threadId = $firstResponse->json('data.thread.id');

    $secondResponse = $this->postJson('/api/v1/chat/direct-threads', [
        'user_id' => $secondUser->id,
    ]);

    $secondResponse
        ->assertCreated()
        ->assertJsonPath('data.thread.id', $threadId);

    expect(ChatThread::query()->count())->toBe(1);
});

it('sends direct messages and marks the thread as read', function () {
    $firstUser = User::factory()->create();
    $secondUser = User::factory()->create();

    Sanctum::actingAs($firstUser);

    $threadId = $this->postJson('/api/v1/chat/direct-threads', [
        'user_id' => $secondUser->id,
    ])->json('data.thread.id');

    $this->postJson("/api/v1/chat/threads/{$threadId}/messages", [
        'content' => 'Ciao, ci sei?',
    ])
        ->assertCreated()
        ->assertJsonPath('data.message.content', 'Ciao, ci sei?');

    Sanctum::actingAs($secondUser);

    $this->getJson("/api/v1/chat/threads/{$threadId}/messages")
        ->assertOk()
        ->assertJsonCount(1, 'data.messages')
        ->assertJsonPath('data.messages.0.content', 'Ciao, ci sei?');

    $this->assertDatabaseMissing('notifications', [
        'user_id' => $secondUser->id,
        'type' => 'chat_message_received',
    ]);

    $this->getJson('/api/v1/chat/threads')
        ->assertOk()
        ->assertJsonPath('data.threads.0.unread_count', 1);

    $this->postJson("/api/v1/chat/threads/{$threadId}/read")
        ->assertOk()
        ->assertJsonPath('data.read', true);

    $this->getJson('/api/v1/chat/threads')
        ->assertOk()
        ->assertJsonPath('data.threads.0.unread_count', 0);
});

it('supports replies inside the same thread and notifies the original author', function () {
    $firstUser = User::factory()->create();
    $secondUser = User::factory()->create();

    Sanctum::actingAs($firstUser);

    $threadId = $this->postJson('/api/v1/chat/direct-threads', [
        'user_id' => $secondUser->id,
    ])->json('data.thread.id');

    $originalMessageId = $this->postJson("/api/v1/chat/threads/{$threadId}/messages", [
        'content' => 'Messaggio originale',
    ])->json('data.message.id');

    Sanctum::actingAs($secondUser);

    $this->postJson("/api/v1/chat/threads/{$threadId}/messages", [
        'content' => 'Questa e una risposta',
        'reply_to_message_id' => $originalMessageId,
    ])
        ->assertCreated()
        ->assertJsonPath('data.message.reply_to.id', $originalMessageId)
        ->assertJsonPath('data.message.reply_to.content', 'Messaggio originale');

    $this->assertDatabaseMissing('notifications', [
        'user_id' => $firstUser->id,
        'type' => 'chat_message_replied',
    ]);
});

it('supports emoji reactions on chat messages', function () {
    $firstUser = User::factory()->create();
    $secondUser = User::factory()->create();

    Sanctum::actingAs($firstUser);

    $threadId = $this->postJson('/api/v1/chat/direct-threads', [
        'user_id' => $secondUser->id,
    ])->json('data.thread.id');

    $messageId = $this->postJson("/api/v1/chat/threads/{$threadId}/messages", [
        'content' => 'Messaggio da reagire',
    ])->json('data.message.id');

    Sanctum::actingAs($secondUser);

    $this->postJson("/api/v1/chat/messages/{$messageId}/reactions", [
        'emoji' => '🔥',
    ])
        ->assertCreated()
        ->assertJsonPath('data.message.reactions.0.emoji', '🔥')
        ->assertJsonPath('data.message.reactions.0.count', 1)
        ->assertJsonPath('data.message.reactions.0.reacted_by_current_user', true);

    $this->assertDatabaseMissing('notifications', [
        'user_id' => $firstUser->id,
        'type' => 'chat_message_reacted',
    ]);

    $this->deleteJson("/api/v1/chat/messages/{$messageId}/reactions", [
        'emoji' => '🔥',
    ])
        ->assertOk()
        ->assertJsonCount(0, 'data.message.reactions');
});

it('supports attachments on chat messages', function () {
    Storage::fake('public');

    $firstUser = User::factory()->create();
    $secondUser = User::factory()->create();

    Sanctum::actingAs($firstUser);

    $threadId = $this->postJson('/api/v1/chat/direct-threads', [
        'user_id' => $secondUser->id,
    ])->json('data.thread.id');

    $file = UploadedFile::fake()->image('poster.png');

    $this->postJson("/api/v1/chat/threads/{$threadId}/messages", [
        'attachments' => [$file],
    ])
        ->assertCreated()
        ->assertJsonPath('data.message.attachments.0.original_name', 'poster.png')
        ->assertJsonPath('data.message.attachments.0.mime_type', 'image/png');

    $storedPath = ChatMessageAttachment::query()->value('path');

    expect($storedPath)->not->toBeNull();
    Storage::disk('public')->assertExists($storedPath);

    $this->assertDatabaseHas('chat_message_attachments', [
        'original_name' => 'poster.png',
    ]);
});

it('creates a free group chat thread independent from the group domain', function () {
    $owner = User::factory()->create();
    $memberA = User::factory()->create();
    $memberB = User::factory()->create();

    Sanctum::actingAs($owner);

    $response = $this->postJson('/api/v1/chat/group-threads', [
        'title' => 'Traduttori e organizer',
        'user_ids' => [$memberA->id, $memberB->id],
    ]);

    $response
        ->assertCreated()
        ->assertJsonPath('data.thread.type', 'group')
        ->assertJsonPath('data.thread.title', 'Traduttori e organizer')
        ->assertJsonPath('data.thread.is_group_attached', false)
        ->assertJsonPath('data.thread.created_by_user_id', $owner->id)
        ->assertJsonPath('data.thread.can_manage_members', true);

    $threadId = $response->json('data.thread.id');

    $this->assertDatabaseHas('chat_threads', [
        'id' => $threadId,
        'type' => 'group',
        'title' => 'Traduttori e organizer',
        'group_id' => null,
    ]);

    $this->assertDatabaseHas('chat_thread_members', [
        'chat_thread_id' => $threadId,
        'user_id' => $owner->id,
    ]);

    $this->assertDatabaseHas('chat_thread_members', [
        'chat_thread_id' => $threadId,
        'user_id' => $memberA->id,
    ]);

    $this->assertDatabaseHas('chat_thread_members', [
        'chat_thread_id' => $threadId,
        'user_id' => $memberB->id,
    ]);
});

it('allows members of a free group chat to read and send messages', function () {
    $owner = User::factory()->create();
    $member = User::factory()->create();
    $outsider = User::factory()->create();

    Sanctum::actingAs($owner);

    $threadId = $this->postJson('/api/v1/chat/group-threads', [
        'title' => 'Backstage',
        'user_ids' => [$member->id],
    ])->json('data.thread.id');

    Sanctum::actingAs($member);

    $this->getJson("/api/v1/chat/threads/{$threadId}")
        ->assertOk()
        ->assertJsonPath('data.thread.title', 'Backstage')
        ->assertJsonPath('data.thread.is_group_attached', false)
        ->assertJsonPath('data.thread.can_manage_members', false);

    $this->postJson("/api/v1/chat/threads/{$threadId}/messages", [
        'content' => 'Eccomi dentro la chat libera',
    ])
        ->assertCreated()
        ->assertJsonPath('data.message.content', 'Eccomi dentro la chat libera');

    $this->assertDatabaseMissing('notifications', [
        'user_id' => $owner->id,
        'type' => 'chat_message_received',
    ]);

    Sanctum::actingAs($outsider);

    $this->getJson("/api/v1/chat/threads/{$threadId}")
        ->assertForbidden();
});

it('allows the creator of a free group chat to add members', function () {
    $owner = User::factory()->create();
    $member = User::factory()->create();
    $newMember = User::factory()->create();

    Sanctum::actingAs($owner);

    $threadId = $this->postJson('/api/v1/chat/group-threads', [
        'title' => 'Organizzazione',
        'user_ids' => [$member->id],
    ])->json('data.thread.id');

    $this->postJson("/api/v1/chat/threads/{$threadId}/members", [
        'user_id' => $newMember->id,
    ])
        ->assertOk()
        ->assertJsonCount(3, 'data.thread.members');

    $this->assertDatabaseHas('chat_thread_members', [
        'chat_thread_id' => $threadId,
        'user_id' => $newMember->id,
    ]);

    Sanctum::actingAs($newMember);

    $this->getJson("/api/v1/chat/threads/{$threadId}")
        ->assertOk()
        ->assertJsonPath('data.thread.id', $threadId);
});

it('allows the creator of a free group chat to remove members', function () {
    $owner = User::factory()->create();
    $member = User::factory()->create();

    Sanctum::actingAs($owner);

    $threadId = $this->postJson('/api/v1/chat/group-threads', [
        'title' => 'Organizzazione',
        'user_ids' => [$member->id],
    ])->json('data.thread.id');

    $this->deleteJson("/api/v1/chat/threads/{$threadId}/members/{$member->id}")
        ->assertOk()
        ->assertJsonCount(1, 'data.thread.members');

    $this->assertDatabaseMissing('chat_thread_members', [
        'chat_thread_id' => $threadId,
        'user_id' => $member->id,
    ]);

    Sanctum::actingAs($member);

    $this->getJson("/api/v1/chat/threads/{$threadId}")
        ->assertForbidden();
});

it('prevents non creators from managing free group chat members', function () {
    $owner = User::factory()->create();
    $member = User::factory()->create();
    $newMember = User::factory()->create();

    Sanctum::actingAs($owner);

    $threadId = $this->postJson('/api/v1/chat/group-threads', [
        'title' => 'Organizzazione',
        'user_ids' => [$member->id],
    ])->json('data.thread.id');

    Sanctum::actingAs($member);

    $this->postJson("/api/v1/chat/threads/{$threadId}/members", [
        'user_id' => $newMember->id,
    ])
        ->assertForbidden();
});

it('prevents manual member management on group attached threads', function () {
    $owner = chatPoetOrganizer();
    $member = User::factory()->create();

    Sanctum::actingAs($owner);

    $groupResponse = $this->postJson('/api/v1/groups', [
        'name' => 'Chat Group Test',
        'description' => 'Gruppo con chat',
        'visibility' => 'public',
        'group_category_id' => groupTestCategory()->id,
        'chat_audiences' => ['public'],
    ])->assertOk();

    $threadId = ChatThread::query()->where('group_id', $groupResponse->json('data.group.id'))->value('id');

    $this->postJson("/api/v1/chat/threads/{$threadId}/members", [
        'user_id' => $member->id,
    ])
        ->assertForbidden();
});

it('does not auto create a group chat thread when a group is created', function () {
    $owner = chatPoetOrganizer();
    Sanctum::actingAs($owner);

    $response = $this->postJson('/api/v1/groups', [
        'name' => 'Chat Group Test',
        'description' => 'Gruppo senza chat',
        'visibility' => 'public',
        'group_category_id' => groupTestCategory()->id,
    ]);

    $response->assertOk();

    expect(ChatThread::query()->where('group_id', $response->json('data.group.id'))->count())->toBe(0);
});

it('creates selected group chat audiences when a group is created', function () {
    $owner = chatPoetOrganizer();
    Sanctum::actingAs($owner);

    $response = $this->postJson('/api/v1/groups', [
        'name' => 'Chat Group Test',
        'description' => 'Gruppo con chat',
        'visibility' => 'public',
        'group_category_id' => groupTestCategory()->id,
        'chat_audiences' => ['admin', 'public'],
    ]);

    $response->assertOk();

    $groupId = $response->json('data.group.id');

    $this->assertDatabaseHas('chat_threads', [
        'group_id' => $groupId,
        'audience' => 'admin',
        'is_enabled' => true,
    ]);
    $this->assertDatabaseHas('chat_threads', [
        'group_id' => $groupId,
        'audience' => 'public',
        'is_enabled' => true,
    ]);
    expect(ChatThread::query()->where('group_id', $groupId)->where('audience', 'editor')->exists())->toBeFalse();

    $adminThreadId = ChatThread::query()->where('group_id', $groupId)->where('audience', 'admin')->value('id');
    $this->assertDatabaseHas('chat_thread_members', [
        'chat_thread_id' => $adminThreadId,
        'user_id' => $owner->id,
    ]);
});

it('enables group chat audiences from the staff endpoint', function () {
    $owner = chatPoetOrganizer();
    Sanctum::actingAs($owner);

    $groupResponse = $this->postJson('/api/v1/groups', [
        'name' => 'Thread Esplicito',
        'description' => 'Gruppo con thread idempotente',
        'visibility' => 'public',
        'group_category_id' => groupTestCategory()->id,
    ])->assertOk();

    $group = Group::query()->findOrFail($groupResponse->json('data.group.id'));

    $first = $this->patchJson("/api/v1/groups/{$group->slug}/chat-threads", [
        'audiences' => ['public'],
    ])->assertOk();

    $threadId = $first->json('data.threads.0.id');

    $this->patchJson("/api/v1/groups/{$group->slug}/chat-threads", [
        'audiences' => ['public'],
    ])
        ->assertOk()
        ->assertJsonPath('data.threads.0.id', $threadId);
});

it('adds active public joiners to the public group chat thread', function () {
    $owner = chatPoetOrganizer();
    Sanctum::actingAs($owner);

    $groupResponse = $this->postJson('/api/v1/groups', [
        'name' => 'Join Chat Group',
        'description' => 'Gruppo con join e chat',
        'visibility' => 'public',
        'group_category_id' => groupTestCategory()->id,
        'chat_audiences' => ['admin', 'public'],
    ])->assertOk();

    $group = Group::query()->findOrFail($groupResponse->json('data.group.id'));
    $threadId = ChatThread::query()->where('group_id', $group->id)->where('audience', 'public')->value('id');
    $adminThreadId = ChatThread::query()->where('group_id', $group->id)->where('audience', 'admin')->value('id');

    $member = User::factory()->create();
    Sanctum::actingAs($member);

    $this->postJson("/api/v1/groups/{$group->slug}/join")
        ->assertOk()
        ->assertJsonPath('data.membership.status', 'active');

    $this->assertDatabaseHas('chat_thread_members', [
        'chat_thread_id' => $threadId,
        'user_id' => $member->id,
    ]);
    $this->assertDatabaseMissing('chat_thread_members', [
        'chat_thread_id' => $adminThreadId,
        'user_id' => $member->id,
    ]);

    $this->getJson('/api/v1/chat/threads')
        ->assertOk()
        ->assertJsonPath('data.threads.0.id', $threadId);

    $this->postJson("/api/v1/chat/threads/{$threadId}/messages", [
        'content' => 'Ciao gruppo!',
    ])
        ->assertCreated()
        ->assertJsonPath('data.message.content', 'Ciao gruppo!');

    $this->assertDatabaseMissing('notifications', [
        'user_id' => $owner->id,
        'type' => 'chat_message_received',
    ]);
});

it('prevents outsiders from reading a group thread', function () {
    $owner = chatPoetOrganizer();
    $outsider = User::factory()->create();
    $group = createChatGroupWithOwner($owner);

    Sanctum::actingAs($owner);
    $threadId = $this->patchJson("/api/v1/groups/{$group->slug}/chat-threads", [
        'audiences' => ['public'],
    ])->json('data.threads.0.id');

    Sanctum::actingAs($outsider);

    $this->getJson("/api/v1/chat/threads/{$threadId}")
        ->assertForbidden();
});

it('does not list admin group chats to followers', function () {
    $owner = chatPoetOrganizer();
    $group = createChatGroupWithOwner($owner);

    Sanctum::actingAs($owner);
    $this->patchJson("/api/v1/groups/{$group->slug}/chat-threads", [
        'audiences' => ['admin', 'public'],
    ])->assertOk();

    $follower = User::factory()->create();
    Sanctum::actingAs($follower);
    $this->postJson("/api/v1/groups/{$group->slug}/join")->assertOk();

    $listedIds = collect($this->getJson('/api/v1/chat/threads')->assertOk()->json('data.threads'))
        ->pluck('audience')
        ->all();

    expect($listedIds)->toContain('public')
        ->not->toContain('admin');
});

it('adds child group admins to the parent affiliates chat', function () {
    $parentOwner = chatPoetOrganizer();
    $childOwner = chatPoetOrganizer();
    $parent = createChatGroupWithOwner($parentOwner);
    $child = createChatGroupWithOwner($childOwner);

    Sanctum::actingAs($parentOwner);
    $this->patchJson("/api/v1/groups/{$parent->slug}/chat-threads", [
        'audiences' => ['affiliates'],
    ])->assertOk();

    Sanctum::actingAs($childOwner);
    $this->postJson("/api/v1/groups/{$child->slug}/affiliations", [
        'parent_group_id' => $parent->id,
    ])->assertOk();

    $affiliationId = \Domain\Group\Models\GroupAffiliation::query()
        ->where('parent_group_id', $parent->id)
        ->where('child_group_id', $child->id)
        ->value('id');

    Sanctum::actingAs($parentOwner);
    $this->postJson("/api/v1/groups/{$parent->slug}/affiliations/{$affiliationId}/accept")
        ->assertOk();

    $threadId = ChatThread::query()
        ->where('group_id', $parent->id)
        ->where('audience', 'affiliates')
        ->value('id');

    $this->assertDatabaseHas('chat_thread_members', [
        'chat_thread_id' => $threadId,
        'user_id' => $parentOwner->id,
    ]);
    $this->assertDatabaseHas('chat_thread_members', [
        'chat_thread_id' => $threadId,
        'user_id' => $childOwner->id,
    ]);

    Sanctum::actingAs($childOwner);
    $this->getJson('/api/v1/chat/threads')
        ->assertOk()
        ->assertJsonPath('data.threads.0.id', $threadId);
});

it('hides a group chat when the audience is disabled', function () {
    $owner = chatPoetOrganizer();
    $group = createChatGroupWithOwner($owner);

    Sanctum::actingAs($owner);
    $this->patchJson("/api/v1/groups/{$group->slug}/chat-threads", [
        'audiences' => ['public'],
    ])->assertOk();

    $this->getJson('/api/v1/chat/threads')
        ->assertOk()
        ->assertJsonCount(1, 'data.threads');

    $this->patchJson("/api/v1/groups/{$group->slug}/chat-threads", [
        'audiences' => [],
    ])->assertOk();

    $this->getJson('/api/v1/chat/threads')
        ->assertOk()
        ->assertJsonCount(0, 'data.threads');
});

it('dispatches a realtime event when a chat message is sent', function () {
    Event::fake([ChatMessageSent::class]);

    $firstUser = User::factory()->create();
    $secondUser = User::factory()->create();

    Sanctum::actingAs($firstUser);

    $threadId = $this->postJson('/api/v1/chat/direct-threads', [
        'user_id' => $secondUser->id,
    ])->json('data.thread.id');

    $this->postJson("/api/v1/chat/threads/{$threadId}/messages", [
        'content' => 'Realtime',
    ])->assertCreated();

    Event::assertDispatched(ChatMessageSent::class, function (ChatMessageSent $event) use ($threadId, $secondUser) {
        $channels = collect($event->broadcastOn())->map(fn ($channel) => $channel->name);

        return (int) $event->message->chat_thread_id === (int) $threadId
            && $event->message->content === 'Realtime'
            && $event instanceof ShouldBroadcastNow
            && $channels->contains('private-chat.'.$threadId)
            && $channels->contains('private-chat.user.'.$secondUser->id);
    });
});

it('opens a direct thread from a profile nickname', function () {
    $firstUser = User::factory()->create();
    $secondUser = User::factory()->create();

    Profile::query()->where('user_id', $secondUser->id)->update([
        'nickname' => 'poetessa',
    ]);

    Sanctum::actingAs($firstUser);

    $this->postJson('/api/v1/chat/direct-threads', [
        'nickname' => 'poetessa',
    ])
        ->assertCreated()
        ->assertJsonPath('data.thread.type', 'direct')
        ->assertJsonPath('data.thread.other_user.id', $secondUser->id);
});

it('returns realtime config from the authenticated api', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    config([
        'broadcasting.default' => 'null',
        'broadcasting.connections.reverb.key' => 'test-key',
        'broadcasting.connections.reverb.options.host' => 'ws.example.test',
    ]);

    $this->getJson('/api/v1/realtime')
        ->assertOk()
        ->assertJsonPath('data.realtime.enabled', false)
        ->assertJsonPath('data.realtime.key', null);

    config([
        'broadcasting.default' => 'reverb',
        'broadcasting.connections.reverb.key' => 'live-key',
        'broadcasting.connections.reverb.options.host' => 'ws.staging.slamin.it',
        'broadcasting.connections.reverb.options.port' => 443,
        'broadcasting.connections.reverb.options.scheme' => 'https',
    ]);

    $this->getJson('/api/v1/realtime')
        ->assertOk()
        ->assertJsonPath('data.realtime.enabled', true)
        ->assertJsonPath('data.realtime.key', 'live-key')
        ->assertJsonPath('data.realtime.host', 'ws.staging.slamin.it')
        ->assertJsonPath('data.realtime.port', 443)
        ->assertJsonPath('data.realtime.scheme', 'https');

    config([
        'app.url' => 'https://staging.slamin.it',
        'broadcasting.default' => 'reverb',
        'broadcasting.connections.reverb.key' => 'live-key',
        'broadcasting.connections.reverb.options.host' => '127.0.0.1',
        'broadcasting.connections.reverb.options.port' => 8080,
        'broadcasting.connections.reverb.options.scheme' => 'https',
    ]);

    $this->getJson('/api/v1/realtime')
        ->assertOk()
        ->assertJsonPath('data.realtime.enabled', true)
        ->assertJsonPath('data.realtime.host', 'staging.slamin.it')
        ->assertJsonPath('data.realtime.port', 8080);
});

it('searches public verified users for new chat threads', function () {
    $firstUser = User::factory()->create();
    $matching = User::factory()->create();
    $privateUser = User::factory()->create();
    $unverified = User::factory()->unverified()->create();

    Profile::query()->where('user_id', $matching->id)->update([
        'display_name' => 'Ada Chat',
        'nickname' => 'ada-chat',
        'is_public' => true,
    ]);
    Profile::query()->where('user_id', $privateUser->id)->update([
        'display_name' => 'Ada Private',
        'nickname' => 'ada-private',
        'is_public' => false,
    ]);
    Profile::query()->where('user_id', $unverified->id)->update([
        'display_name' => 'Ada Unverified',
        'nickname' => 'ada-unverified',
        'is_public' => true,
    ]);
    Profile::query()->where('user_id', $firstUser->id)->update([
        'display_name' => 'Ada Self',
        'nickname' => 'ada-self',
        'is_public' => true,
    ]);

    Sanctum::actingAs($firstUser);

    $this->getJson('/api/v1/chat/users?q=Ada')
        ->assertOk()
        ->assertJsonCount(1, 'data.users')
        ->assertJsonPath('data.users.0.id', $matching->id)
        ->assertJsonPath('data.users.0.nickname', 'ada-chat')
        ->assertJsonMissing(['email' => $matching->email]);

    $this->getJson('/api/v1/chat/users?q=a')->assertUnprocessable();
});

it('authorizes private chat channels for members only', function () {
    config(['broadcasting.default' => 'reverb']);
    Broadcast::purge();
    require base_path('routes/channels.php');

    $firstUser = User::factory()->create();
    $secondUser = User::factory()->create();
    $outsider = User::factory()->create();

    Sanctum::actingAs($firstUser);

    $threadId = $this->postJson('/api/v1/chat/direct-threads', [
        'user_id' => $secondUser->id,
    ])->json('data.thread.id');

    $this->getJson("/api/v1/chat/threads/{$threadId}")->assertOk();

    $this->postJson('/api/broadcasting/auth', [
        'socket_id' => '1234.5678',
        'channel_name' => 'private-chat.'.$threadId,
    ])->assertOk()->assertJsonStructure(['auth']);

    $this->postJson('/api/broadcasting/auth', [
        'socket_id' => '1234.5678',
        'channel_name' => 'private-chat.user.'.$firstUser->id,
    ])->assertOk()->assertJsonStructure(['auth']);

    Sanctum::actingAs($outsider);

    $this->postJson('/api/broadcasting/auth', [
        'socket_id' => '1234.5678',
        'channel_name' => 'private-chat.'.$threadId,
    ])->assertForbidden();

    $this->postJson('/api/broadcasting/auth', [
        'socket_id' => '1234.5678',
        'channel_name' => 'private-chat.user.'.$firstUser->id,
    ])->assertForbidden();
});
