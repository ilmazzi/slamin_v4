<?php

use Domain\Chat\Models\ChatThread;
use Domain\User\Models\User;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('chat.{threadId}', function (User $user, int $threadId) {
    $thread = ChatThread::query()->find($threadId);

    return $thread !== null && $user->can('view', $thread);
}, ['guards' => ['sanctum']]);

Broadcast::channel('chat.user.{userId}', function (User $user, int $userId) {
    return (int) $user->id === $userId;
}, ['guards' => ['sanctum']]);

Broadcast::channel('notifications.{userId}', function (User $user, int $userId) {
    return (int) $user->id === $userId;
}, ['guards' => ['sanctum']]);
