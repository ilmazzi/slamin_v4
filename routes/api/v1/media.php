<?php

use App\Http\Controllers\Api\V1\Comment\ListMediaCommentsController;
use App\Http\Controllers\Api\V1\Comment\StoreMediaCommentController;
use App\Http\Controllers\Api\V1\Media\CreateStripeCheckoutSessionController;
use App\Http\Controllers\Api\V1\Media\DeleteMediaItemController;
use App\Http\Controllers\Api\V1\Media\DeleteMediaSnapController;
use App\Http\Controllers\Api\V1\Media\GrantUserMediaEntitlementController;
use App\Http\Controllers\Api\V1\Media\HandleStripeWebhookController;
use App\Http\Controllers\Api\V1\Media\LikeMediaItemController;
use App\Http\Controllers\Api\V1\Media\ListMediaController;
use App\Http\Controllers\Api\V1\Media\ListMediaFeaturePacksController;
use App\Http\Controllers\Api\V1\Media\ListMediaSettingsController;
use App\Http\Controllers\Api\V1\Media\ListMediaSnapsController;
use App\Http\Controllers\Api\V1\Media\ListMyMediaCheckoutOrdersController;
use App\Http\Controllers\Api\V1\Media\ListMyMediaController;
use App\Http\Controllers\Api\V1\Media\ListMyMediaLimitsController;
use App\Http\Controllers\Api\V1\Media\ListUserMediaEntitlementsController;
use App\Http\Controllers\Api\V1\Media\RevokeUserMediaEntitlementController;
use App\Http\Controllers\Api\V1\Media\ShowMediaController;
use App\Http\Controllers\Api\V1\Media\StoreMediaCheckoutOrderController;
use App\Http\Controllers\Api\V1\Media\StoreMediaFeaturePackController;
use App\Http\Controllers\Api\V1\Media\StoreMediaSnapController;
use App\Http\Controllers\Api\V1\Media\StorePhotoMediaController;
use App\Http\Controllers\Api\V1\Media\StoreVideoMediaController;
use App\Http\Controllers\Api\V1\Media\SyncPeerTubeVideoStatusController;
use App\Http\Controllers\Api\V1\Media\TestPeerTubeConnectionController;
use App\Http\Controllers\Api\V1\Media\UnlikeMediaItemController;
use App\Http\Controllers\Api\V1\Media\UpdateMediaFeaturePackController;
use App\Http\Controllers\Api\V1\Media\UpdateMediaItemController;
use App\Http\Controllers\Api\V1\Media\UpdateMediaSettingsController;
use App\Http\Controllers\Api\V1\Media\UpdateMediaSnapController;
use App\Http\Controllers\Share\ShowMediaShareCardController;
use Illuminate\Support\Facades\Route;

Route::get('/media', ListMediaController::class)->name('media.index');
Route::get('/media/{id}/share-card', ShowMediaShareCardController::class)
    ->whereNumber('id')
    ->name('media.share-card');
Route::get('/media/{media}', ShowMediaController::class)->name('media.show');
Route::get('/media/{media}/video-status', SyncPeerTubeVideoStatusController::class)->name('media.video-status');
Route::get('/media/{media}/comments', ListMediaCommentsController::class)->name('media.comments.index');
Route::get('/media/{media}/snaps', ListMediaSnapsController::class)->name('media.snaps.index');
Route::post('/webhooks/stripe', HandleStripeWebhookController::class)->name('media.webhooks.stripe');

Route::middleware([
    'auth:sanctum',
    'account.verified',
])->group(function () {
    Route::post('/media/{media}/like', LikeMediaItemController::class)->name('media.like');
    Route::delete('/media/{media}/like', UnlikeMediaItemController::class)->name('media.unlike');
    Route::post('/media/{media}/comments', StoreMediaCommentController::class)->name('media.comments.store');
    Route::post('/media/{media}/snaps', StoreMediaSnapController::class)->name('media.snaps.store');
    Route::patch('/media/{media}/snaps/{snap}', UpdateMediaSnapController::class)->name('media.snaps.update');
    Route::delete('/media/{media}/snaps/{snap}', DeleteMediaSnapController::class)->name('media.snaps.delete');
});

Route::middleware([
    'auth:sanctum',
    'account.verified',
    'account.type:poet_organizer',
])->group(function () {
    Route::get('/my/media', ListMyMediaController::class)->name('media.my');
    Route::get('/my/media/limits', ListMyMediaLimitsController::class)->name('media.my.limits');
    Route::get('/my/media/checkout-orders', ListMyMediaCheckoutOrdersController::class)->name('media.my.checkout-orders');
    Route::post('/my/media/checkout-orders', StoreMediaCheckoutOrderController::class)->name('media.my.checkout-orders.store');
    Route::post('/my/media/checkout-orders/{order}/stripe-session', CreateStripeCheckoutSessionController::class)->name('media.my.checkout-orders.stripe-session');
    Route::post('/media/photos', StorePhotoMediaController::class)->name('media.photos.store');
    Route::post('/media/videos', StoreVideoMediaController::class)->name('media.videos.store');
    Route::patch('/media/{media}', UpdateMediaItemController::class)->name('media.update');
    Route::delete('/media/{media}', DeleteMediaItemController::class)->name('media.delete');
});

Route::middleware([
    'auth:sanctum',
    'account.verified',
    'role:admin',
])->prefix('admin/media')->group(function () {
    Route::get('/settings', ListMediaSettingsController::class)->name('admin.media.settings.index');
    Route::patch('/settings', UpdateMediaSettingsController::class)->name('admin.media.settings.update');
    Route::post('/settings/test-peertube', TestPeerTubeConnectionController::class)->name('admin.media.settings.test-peertube');
    Route::get('/feature-packs', ListMediaFeaturePacksController::class)->name('admin.media.feature-packs.index');
    Route::post('/feature-packs', StoreMediaFeaturePackController::class)->name('admin.media.feature-packs.store');
    Route::patch('/feature-packs/{featurePack}', UpdateMediaFeaturePackController::class)->name('admin.media.feature-packs.update');
});

Route::middleware([
    'auth:sanctum',
    'account.verified',
    'role:admin',
])->prefix('admin/users/{user}')->group(function () {
    Route::get('/media-entitlements', ListUserMediaEntitlementsController::class)->name('admin.users.media-entitlements.index');
    Route::post('/media-entitlements', GrantUserMediaEntitlementController::class)->name('admin.users.media-entitlements.store');
    Route::delete('/media-entitlements/{entitlement}', RevokeUserMediaEntitlementController::class)->name('admin.users.media-entitlements.delete');
});
