<?php

use Domain\Notification\Models\Notification;
use Domain\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

function notificationUser(): User
{
    return User::factory()->poetOrganizer()->create();
}

it('lists notifications for the authenticated user', function () {
    $user = notificationUser();
    Sanctum::actingAs($user);

    Notification::query()->create([
        'user_id' => $user->id,
        'type' => 'poem_approved',
        'title' => 'Poesia approvata',
        'message' => 'Messaggio 1',
        'data' => ['poem_id' => 1],
    ]);

    Notification::query()->create([
        'user_id' => $user->id,
        'type' => 'poem_rejected',
        'title' => 'Poesia rifiutata',
        'message' => 'Messaggio 2',
        'data' => ['poem_id' => 2],
        'read_at' => now(),
    ]);

    Notification::query()->create([
        'user_id' => notificationUser()->id,
        'type' => 'poem_approved',
        'title' => 'Altra notifica',
        'message' => 'Messaggio 3',
    ]);

    $response = $this->getJson('/api/v1/notifications');

    $response
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('data.0.type', 'poem_approved')
        ->assertJsonPath('data.1.type', 'poem_rejected');
});

it('shows a single notification for its owner', function () {
    $user = notificationUser();
    Sanctum::actingAs($user);

    $notification = Notification::query()->create([
        'user_id' => $user->id,
        'type' => 'poem_approved',
        'title' => 'Dettaglio',
        'message' => 'Messaggio dettaglio',
        'data' => ['poem_id' => 10],
    ]);

    $response = $this->getJson("/api/v1/notifications/{$notification->id}");

    $response
        ->assertOk()
        ->assertJsonPath('data.notification.id', $notification->id)
        ->assertJsonPath('data.notification.title', 'Dettaglio');
});

it('returns the unread notifications count for the authenticated user', function () {
    $user = notificationUser();
    Sanctum::actingAs($user);

    Notification::query()->create([
        'user_id' => $user->id,
        'type' => 'poem_approved',
        'title' => 'Unread 1',
        'message' => 'Message',
    ]);

    Notification::query()->create([
        'user_id' => $user->id,
        'type' => 'poem_rejected',
        'title' => 'Unread 2',
        'message' => 'Message',
    ]);

    Notification::query()->create([
        'user_id' => $user->id,
        'type' => 'poem_approved',
        'title' => 'Read',
        'message' => 'Message',
        'read_at' => now(),
    ]);

    $response = $this->getJson('/api/v1/notifications/unread-count');

    $response
        ->assertOk()
        ->assertJsonPath('data.unread_count', 2);
});

it('filters unread notifications only', function () {
    $user = notificationUser();
    Sanctum::actingAs($user);

    Notification::query()->create([
        'user_id' => $user->id,
        'type' => 'poem_approved',
        'title' => 'Unread',
        'message' => 'Unread message',
    ]);

    Notification::query()->create([
        'user_id' => $user->id,
        'type' => 'poem_rejected',
        'title' => 'Read',
        'message' => 'Read message',
        'read_at' => now(),
    ]);

    $response = $this->getJson('/api/v1/notifications?unread_only=1');

    $response
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.title', 'Unread');
});

it('marks a single notification as read', function () {
    $user = notificationUser();
    Sanctum::actingAs($user);

    $notification = Notification::query()->create([
        'user_id' => $user->id,
        'type' => 'poem_approved',
        'title' => 'Unread',
        'message' => 'Message',
    ]);

    $response = $this->patchJson("/api/v1/notifications/{$notification->id}/read");

    $response
        ->assertOk()
        ->assertJsonPath('data.notification.id', $notification->id);

    expect($notification->fresh()->read_at)->not->toBeNull();
});

it('does not allow reading another users notification', function () {
    Sanctum::actingAs(notificationUser());

    $notification = Notification::query()->create([
        'user_id' => notificationUser()->id,
        'type' => 'poem_approved',
        'title' => 'Other user',
        'message' => 'Message',
    ]);

    $this->patchJson("/api/v1/notifications/{$notification->id}/read")
        ->assertNotFound();
});

it('does not allow showing another users notification', function () {
    Sanctum::actingAs(notificationUser());

    $notification = Notification::query()->create([
        'user_id' => notificationUser()->id,
        'type' => 'poem_approved',
        'title' => 'Other user',
        'message' => 'Message',
    ]);

    $this->getJson("/api/v1/notifications/{$notification->id}")
        ->assertNotFound();
});

it('marks all unread notifications as read', function () {
    $user = notificationUser();
    Sanctum::actingAs($user);

    Notification::query()->create([
        'user_id' => $user->id,
        'type' => 'poem_approved',
        'title' => 'Unread 1',
        'message' => 'Message',
    ]);

    Notification::query()->create([
        'user_id' => $user->id,
        'type' => 'poem_rejected',
        'title' => 'Unread 2',
        'message' => 'Message',
    ]);

    Notification::query()->create([
        'user_id' => $user->id,
        'type' => 'poem_approved',
        'title' => 'Read',
        'message' => 'Message',
        'read_at' => now(),
    ]);

    $response = $this->patchJson('/api/v1/notifications/read-all');

    $response
        ->assertOk()
        ->assertJsonPath('data.updated_count', 2);

    expect(
        Notification::query()
            ->where('user_id', $user->id)
            ->whereNull('read_at')
            ->count()
    )->toBe(0);
});

it('deletes a single notification owned by the authenticated user', function () {
    $user = notificationUser();
    Sanctum::actingAs($user);

    $notification = Notification::query()->create([
        'user_id' => $user->id,
        'type' => 'poem_approved',
        'title' => 'Delete me',
        'message' => 'Message',
    ]);

    $response = $this->deleteJson("/api/v1/notifications/{$notification->id}");

    $response->assertOk();

    $this->assertDatabaseMissing('notifications', [
        'id' => $notification->id,
    ]);
});

it('does not allow deleting another users notification', function () {
    Sanctum::actingAs(notificationUser());

    $notification = Notification::query()->create([
        'user_id' => notificationUser()->id,
        'type' => 'poem_approved',
        'title' => 'Other user',
        'message' => 'Message',
    ]);

    $this->deleteJson("/api/v1/notifications/{$notification->id}")
        ->assertNotFound();
});

it('deletes all notifications for the authenticated user', function () {
    $user = notificationUser();
    Sanctum::actingAs($user);

    Notification::query()->create([
        'user_id' => $user->id,
        'type' => 'poem_approved',
        'title' => 'One',
        'message' => 'Message',
    ]);

    Notification::query()->create([
        'user_id' => $user->id,
        'type' => 'poem_rejected',
        'title' => 'Two',
        'message' => 'Message',
    ]);

    Notification::query()->create([
        'user_id' => notificationUser()->id,
        'type' => 'poem_approved',
        'title' => 'Other user',
        'message' => 'Message',
    ]);

    $response = $this->deleteJson('/api/v1/notifications');

    $response
        ->assertOk()
        ->assertJsonPath('data.deleted_count', 2);

    expect(
        Notification::query()
            ->where('user_id', $user->id)
            ->count()
    )->toBe(0);
});
