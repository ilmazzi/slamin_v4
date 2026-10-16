<?php

use App\Http\Controllers\Api\V1\Admin\ListNewsletterCampaignsController;
use App\Http\Controllers\Api\V1\Admin\SendNewsletterCampaignController;
use App\Http\Controllers\Api\V1\Newsletter\ConfirmNewsletterController;
use App\Http\Controllers\Api\V1\Newsletter\SubscribeNewsletterController;
use App\Http\Controllers\Api\V1\Newsletter\UnsubscribeNewsletterController;
use Illuminate\Support\Facades\Route;

Route::prefix('newsletter')->middleware('throttle:6,1')->group(function (): void {
    Route::post('/subscribe', SubscribeNewsletterController::class)->name('newsletter.subscribe');
    Route::post('/confirm', ConfirmNewsletterController::class)->name('newsletter.confirm');
    Route::post('/unsubscribe', UnsubscribeNewsletterController::class)->name('newsletter.unsubscribe');
});

Route::middleware(['auth:sanctum', 'account.verified', 'role:admin'])
    ->prefix('admin/newsletter')
    ->group(function (): void {
        Route::get('/', ListNewsletterCampaignsController::class)->name('admin.newsletter.index');
        Route::post('/send', SendNewsletterCampaignController::class)->name('admin.newsletter.send');
    });
