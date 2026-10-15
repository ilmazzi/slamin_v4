<?php

use App\Http\Controllers\Api\V1\Admin\ListMailSettingsController;
use App\Http\Controllers\Api\V1\Admin\SendTestNotificationEmailsController;
use App\Http\Controllers\Api\V1\Admin\TestMailSettingsController;
use App\Http\Controllers\Api\V1\Admin\UpdateMailSettingsController;
use Illuminate\Support\Facades\Route;

Route::middleware([
    'auth:sanctum',
    'account.verified',
    'role:admin',
])->prefix('admin/mail-settings')->group(function (): void {
    Route::get('/', ListMailSettingsController::class)->name('admin.mail-settings.index');
    Route::patch('/', UpdateMailSettingsController::class)->name('admin.mail-settings.update');
    Route::post('/test', TestMailSettingsController::class)->name('admin.mail-settings.test');
});

Route::middleware([
    'auth:sanctum',
    'account.verified',
    'role:admin',
])->post('admin/notification-emails/test', SendTestNotificationEmailsController::class)
    ->name('admin.notification-emails.test');
