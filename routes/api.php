<?php

use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    require __DIR__.'/api/v1/i18n.php';
    require __DIR__.'/api/v1/auth.php';
    require __DIR__.'/api/v1/users.php';
    require __DIR__.'/api/v1/profiles.php';
    require __DIR__.'/api/v1/poems.php';
    require __DIR__.'/api/v1/articles.php';
    require __DIR__.'/api/v1/events.php';
    require __DIR__.'/api/v1/groups.php';
    require __DIR__.'/api/v1/forum.php';
    require __DIR__.'/api/v1/comments.php';
    require __DIR__.'/api/v1/reports.php';
    require __DIR__.'/api/v1/reactions.php';
    require __DIR__.'/api/v1/notifications.php';
    require __DIR__.'/api/v1/moderation.php';
    require __DIR__.'/api/v1/chat.php';
    require __DIR__.'/api/v1/media.php';
    require __DIR__.'/api/v1/search.php';
});
