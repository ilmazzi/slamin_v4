<?php

use App\Http\Controllers\Api\V1\Notification\DeleteAllNotificationsController;
use App\Http\Controllers\Api\V1\Notification\DeleteNotificationController;
use App\Http\Controllers\Api\V1\Notification\ListNotificationsController;
use App\Http\Controllers\Api\V1\Notification\GetUnreadNotificationsCountController;
use App\Http\Controllers\Api\V1\Notification\MarkAllNotificationsAsReadController;
use App\Http\Controllers\Api\V1\Notification\MarkNotificationAsReadController;
use App\Http\Controllers\Api\V1\Notification\ShowNotificationController;
use Illuminate\Support\Facades\Route;

Route::middleware([
    'auth:sanctum',
    'account.verified',
])->group(function () {
    Route::get('/notifications/unread-count', GetUnreadNotificationsCountController::class)->name('notifications.unread-count');
    Route::get('/notifications', ListNotificationsController::class)->name('notifications.index');
    Route::get('/notifications/{notification}', ShowNotificationController::class)->name('notifications.show');
    Route::patch('/notifications/{notification}/read', MarkNotificationAsReadController::class)->name('notifications.read');
    Route::patch('/notifications/read-all', MarkAllNotificationsAsReadController::class)->name('notifications.read-all');
    Route::delete('/notifications/{notification}', DeleteNotificationController::class)->name('notifications.delete');
    Route::delete('/notifications', DeleteAllNotificationsController::class)->name('notifications.delete-all');
});
