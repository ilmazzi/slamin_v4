<?php

use App\Http\Controllers\Api\V1\Notification\DeleteAllNotificationsController;
use App\Http\Controllers\Api\V1\Notification\DeleteNotificationController;
use App\Http\Controllers\Api\V1\Notification\GetUnreadNotificationsCountController;
use App\Http\Controllers\Api\V1\Notification\ListNotificationsController;
use App\Http\Controllers\Api\V1\Notification\MarkAllNotificationsAsReadController;
use App\Http\Controllers\Api\V1\Notification\MarkNotificationAsReadController;
use App\Http\Controllers\Api\V1\Notification\ShowNotificationController;
use App\Http\Controllers\Api\V1\Notification\ShowNotificationEmailPreferencesController;
use App\Http\Controllers\Api\V1\Notification\UnsubscribeNotificationEmailController;
use App\Http\Controllers\Api\V1\Notification\UpdateNotificationEmailPreferencesController;
use Illuminate\Support\Facades\Route;

Route::middleware([
    'auth:sanctum',
    'account.verified',
])->group(function () {
    Route::get('/me/notification-preferences', ShowNotificationEmailPreferencesController::class)->name('notifications.preferences.show');
    Route::put('/me/notification-preferences', UpdateNotificationEmailPreferencesController::class)->name('notifications.preferences.update');
    Route::get('/notifications/unread-count', GetUnreadNotificationsCountController::class)->name('notifications.unread-count');
    Route::get('/notifications', ListNotificationsController::class)->name('notifications.index');
    Route::get('/notifications/{notification}', ShowNotificationController::class)->name('notifications.show');
    Route::patch('/notifications/{notification}/read', MarkNotificationAsReadController::class)->name('notifications.read');
    Route::patch('/notifications/read-all', MarkAllNotificationsAsReadController::class)->name('notifications.read-all');
    Route::delete('/notifications/{notification}', DeleteNotificationController::class)->name('notifications.delete');
    Route::delete('/notifications', DeleteAllNotificationsController::class)->name('notifications.delete-all');
});

Route::match(['get', 'post'], '/email/unsubscribe/{user:public_id}/{category}', UnsubscribeNotificationEmailController::class)
    ->middleware('signed')
    ->name('notifications.email.unsubscribe');
