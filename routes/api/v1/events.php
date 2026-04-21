<?php

use App\Http\Controllers\Api\V1\Event\AdminDeleteEventCategoryController;
use App\Http\Controllers\Api\V1\Event\AdminListEventCategoriesController;
use App\Http\Controllers\Api\V1\Event\AdminShowEventCategoryController;
use App\Http\Controllers\Api\V1\Event\AdminStoreEventCategoryController;
use App\Http\Controllers\Api\V1\Event\AdminUpdateEventCategoryController;
use App\Http\Controllers\Api\V1\Event\DeleteEventController;
use App\Http\Controllers\Api\V1\Event\ListEventCategoriesController;
use App\Http\Controllers\Api\V1\Event\ListEventController;
use App\Http\Controllers\Api\V1\Event\ListMyEventsController;
use App\Http\Controllers\Api\V1\Event\ListMyAudienceInvitesController;
use App\Http\Controllers\Api\V1\Event\ListMyParticipantInvitesController;
use App\Http\Controllers\Api\V1\Event\ListRecentEventPlacesController;
use App\Http\Controllers\Api\V1\Event\RespondToAudienceInviteController;
use App\Http\Controllers\Api\V1\Event\RespondToParticipantInviteController;
use App\Http\Controllers\Api\V1\Event\ShowEventController;
use App\Http\Controllers\Api\V1\Event\StoreEventController;
use App\Http\Controllers\Api\V1\Event\UpdateEventController;
use Illuminate\Support\Facades\Route;

Route::middleware([
    'auth:sanctum',
    'account.verified',
    'account.type:poet_organizer',
])->group(function () {
    Route::post('/events', StoreEventController::class)->name('events.store');
    Route::get('/my/events', ListMyEventsController::class)->name('events.my');
    Route::patch('/events/{event}', UpdateEventController::class)->name('events.update');
    Route::delete('/events/{event}', DeleteEventController::class)->name('events.delete');
    Route::get('/events/recent-places', ListRecentEventPlacesController::class)->name('events.recent-places');
});

Route::middleware([
    'auth:sanctum',
    'account.verified',
])->group(function () {
    Route::get('/my/event-participant-invites', ListMyParticipantInvitesController::class)->name('events.participant-invites.my');
    Route::get('/my/event-audience-invites', ListMyAudienceInvitesController::class)->name('events.audience-invites.my');
    Route::patch('/event-participant-invites/{invite}/respond', RespondToParticipantInviteController::class)->name('events.participant-invites.respond');
    Route::patch('/event-audience-invites/{invite}/respond', RespondToAudienceInviteController::class)->name('events.audience-invites.respond');
});

Route::middleware([
    'auth:sanctum',
    'account.verified',
    'role:admin',
])->prefix('admin')->group(function () {
    Route::get('/event-categories', AdminListEventCategoriesController::class)->name('admin.event-categories.index');
    Route::post('/event-categories', AdminStoreEventCategoryController::class)->name('admin.event-categories.store');
    Route::get('/event-categories/{eventCategory}', AdminShowEventCategoryController::class)->name('admin.event-categories.show');
    Route::patch('/event-categories/{eventCategory}', AdminUpdateEventCategoryController::class)->name('admin.event-categories.update');
    Route::delete('/event-categories/{eventCategory}', AdminDeleteEventCategoryController::class)->name('admin.event-categories.delete');
});

Route::get('/events', ListEventController::class)->name('events.index');
Route::get('/events/{slug}', ShowEventController::class)->name('events.show');
Route::get('/event-categories', ListEventCategoriesController::class)->name('event-categories.index');
