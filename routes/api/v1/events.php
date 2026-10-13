<?php

use App\Http\Controllers\Api\V1\Event\AdminDeleteEventCategoryController;
use App\Http\Controllers\Api\V1\Event\AdminListEventCategoriesController;
use App\Http\Controllers\Api\V1\Event\AdminShowEventCategoryController;
use App\Http\Controllers\Api\V1\Event\AdminStoreEventCategoryController;
use App\Http\Controllers\Api\V1\Event\AdminUpdateEventCategoryController;
use App\Http\Controllers\Api\V1\Event\BookmarkEventController;
use App\Http\Controllers\Api\V1\Event\DeleteEventController;
use App\Http\Controllers\Api\V1\Event\FinalizeEventAvailabilityOptionController;
use App\Http\Controllers\Api\V1\Event\LikeEventController;
use App\Http\Controllers\Api\V1\Event\ListEventCategoriesController;
use App\Http\Controllers\Api\V1\Event\ListEventController;
use App\Http\Controllers\Api\V1\Event\ListMyAudienceInvitesController;
use App\Http\Controllers\Api\V1\Event\ListMyEventsController;
use App\Http\Controllers\Api\V1\Event\ListMyParticipantInvitesController;
use App\Http\Controllers\Api\V1\Event\ListRecentEventPlacesController;
use App\Http\Controllers\Api\V1\Event\RemoveBookmarkEventController;
use App\Http\Controllers\Api\V1\Event\RemoveEventAvailabilityVoteController;
use App\Http\Controllers\Api\V1\Event\RespondToAudienceInviteController;
use App\Http\Controllers\Api\V1\Event\RespondToParticipantInviteController;
use App\Http\Controllers\Api\V1\Event\SearchEventInviteUsersController;
use App\Http\Controllers\Api\V1\Event\ShowEventController;
use App\Http\Controllers\Api\V1\Event\StoreEventController;
use App\Http\Controllers\Api\V1\Event\UnlikeEventController;
use App\Http\Controllers\Api\V1\Event\UpdateEventController;
use App\Http\Controllers\Api\V1\Event\VoteForEventAvailabilityOptionController;
use App\Http\Controllers\Api\V1\Gig\CloseGigController;
use App\Http\Controllers\Api\V1\Gig\DeleteEventGigController;
use App\Http\Controllers\Api\V1\Gig\DeleteGigAnnouncementController;
use App\Http\Controllers\Api\V1\Gig\ListEventGigsController;
use App\Http\Controllers\Api\V1\Gig\ListGigAnnouncementsController;
use App\Http\Controllers\Api\V1\Gig\ListGigApplicationsController;
use App\Http\Controllers\Api\V1\Gig\ListMyGigApplicationsController;
use App\Http\Controllers\Api\V1\Gig\ListOpenGigsController;
use App\Http\Controllers\Api\V1\Gig\ShowGigController;
use App\Http\Controllers\Api\V1\Gig\StoreEventGigController;
use App\Http\Controllers\Api\V1\Gig\StoreGigAnnouncementController;
use App\Http\Controllers\Api\V1\Gig\StoreGigApplicationController;
use App\Http\Controllers\Api\V1\Gig\UpdateEventGigController;
use App\Http\Controllers\Api\V1\Gig\UpdateGigAnnouncementController;
use App\Http\Controllers\Api\V1\Gig\UpdateGigApplicationStatusController;
use App\Http\Controllers\Api\V1\Gig\WithdrawGigApplicationController;
use App\Http\Controllers\Api\V1\PoetrySlam\DeletePoetrySlamEntryController;
use App\Http\Controllers\Api\V1\PoetrySlam\DeletePoetrySlamRoundController;
use App\Http\Controllers\Api\V1\PoetrySlam\ListPoetrySlamEntriesController;
use App\Http\Controllers\Api\V1\PoetrySlam\ListPoetrySlamLeaderboardController;
use App\Http\Controllers\Api\V1\PoetrySlam\ListPoetrySlamRoundAdvancementDecisionsController;
use App\Http\Controllers\Api\V1\PoetrySlam\ListPoetrySlamRoundLeaderboardController;
use App\Http\Controllers\Api\V1\PoetrySlam\ListPoetrySlamRoundsController;
use App\Http\Controllers\Api\V1\PoetrySlam\ListPoetrySlamRoundScoresController;
use App\Http\Controllers\Api\V1\PoetrySlam\ShowPoetrySlamController;
use App\Http\Controllers\Api\V1\PoetrySlam\StorePoetrySlamController;
use App\Http\Controllers\Api\V1\PoetrySlam\StorePoetrySlamEntryController;
use App\Http\Controllers\Api\V1\PoetrySlam\StorePoetrySlamRoundController;
use App\Http\Controllers\Api\V1\PoetrySlam\UpdatePoetrySlamEntryController;
use App\Http\Controllers\Api\V1\PoetrySlam\UpdatePoetrySlamRoundController;
use App\Http\Controllers\Api\V1\PoetrySlam\UpsertPoetrySlamEntryScoresController;
use App\Http\Controllers\Api\V1\PoetrySlam\UpsertPoetrySlamRoundAdvancementDecisionController;
use Illuminate\Support\Facades\Route;

Route::middleware([
    'auth:sanctum',
    'account.verified',
    'account.type:poet_organizer',
])->group(function () {
    Route::post('/events', StoreEventController::class)->name('events.store');
    Route::get('/events/invite-users', SearchEventInviteUsersController::class)->name('events.invite-users');
    Route::patch('/events/{event}', UpdateEventController::class)->name('events.update');
    Route::delete('/events/{event}', DeleteEventController::class)->name('events.delete');
    Route::get('/events/{event}/gigs', ListEventGigsController::class)->name('api.v1.events.gigs.index');
    Route::post('/events/{event}/gigs', StoreEventGigController::class)->name('api.v1.events.gigs.store');
    Route::patch('/events/{event}/gigs/{gig}', UpdateEventGigController::class)->name('api.v1.events.gigs.update');
    Route::delete('/events/{event}/gigs/{gig}', DeleteEventGigController::class)->name('api.v1.events.gigs.delete');
    Route::get('/events/recent-places', ListRecentEventPlacesController::class)->name('events.recent-places');
    Route::post('/events/{event}/availability-options/{option}/finalize', FinalizeEventAvailabilityOptionController::class)->name('events.availability.finalize');
    Route::get('events/{event}/poetry-slam', ShowPoetrySlamController::class)
        ->name('api.v1.poetry-slam.show');

    Route::post('events/{event}/poetry-slam', StorePoetrySlamController::class)
        ->name('api.v1.poetry-slam.store');
    Route::get('events/{event}/poetry-slam/entries', ListPoetrySlamEntriesController::class)
        ->name('api.v1.poetry-slam.entries.index');
    Route::get('events/{event}/poetry-slam/leaderboard', ListPoetrySlamLeaderboardController::class)
        ->name('api.v1.poetry-slam.leaderboard');
    Route::post('events/{event}/poetry-slam/entries', StorePoetrySlamEntryController::class)
        ->name('api.v1.poetry-slam.entries.store');
    Route::patch('events/{event}/poetry-slam/entries/{entry}', UpdatePoetrySlamEntryController::class)
        ->name('api.v1.poetry-slam.entries.update');
    Route::delete('events/{event}/poetry-slam/entries/{entry}', DeletePoetrySlamEntryController::class)
        ->name('api.v1.poetry-slam.entries.delete');
    Route::get('events/{event}/poetry-slam/rounds', ListPoetrySlamRoundsController::class)
        ->name('api.v1.poetry-slam.rounds.index');
    Route::post('events/{event}/poetry-slam/rounds', StorePoetrySlamRoundController::class)
        ->name('api.v1.poetry-slam.rounds.store');
    Route::patch('events/{event}/poetry-slam/rounds/{round}', UpdatePoetrySlamRoundController::class)
        ->name('api.v1.poetry-slam.rounds.update');
    Route::delete('events/{event}/poetry-slam/rounds/{round}', DeletePoetrySlamRoundController::class)
        ->name('api.v1.poetry-slam.rounds.delete');
    Route::get('events/{event}/poetry-slam/rounds/{round}/leaderboard', ListPoetrySlamRoundLeaderboardController::class)
        ->name('api.v1.poetry-slam.rounds.leaderboard');
    Route::get('events/{event}/poetry-slam/rounds/{round}/scores', ListPoetrySlamRoundScoresController::class)
        ->name('api.v1.poetry-slam.rounds.scores.index');
    Route::put('events/{event}/poetry-slam/rounds/{round}/entries/{entry}/scores', UpsertPoetrySlamEntryScoresController::class)
        ->name('api.v1.poetry-slam.rounds.entries.scores.upsert');
    Route::get('events/{event}/poetry-slam/rounds/{round}/advancement-decisions', ListPoetrySlamRoundAdvancementDecisionsController::class)
        ->name('api.v1.poetry-slam.rounds.advancement-decisions.index');
    Route::put('events/{event}/poetry-slam/rounds/{round}/entries/{entry}/advancement-decision', UpsertPoetrySlamRoundAdvancementDecisionController::class)
        ->name('api.v1.poetry-slam.rounds.entries.advancement-decision.upsert');
});

Route::middleware([
    'auth:sanctum',
    'account.verified',
])->group(function () {
    Route::get('/gigs/open', ListOpenGigsController::class)->name('api.v1.gigs.open');
    Route::get('/gigs/{gig}', ShowGigController::class)->name('api.v1.gigs.show');
    Route::get('/my/gig-applications', ListMyGigApplicationsController::class)->name('api.v1.gigs.applications.my');
    Route::post('/gigs/{gig}/applications', StoreGigApplicationController::class)->name('api.v1.gigs.applications.store');
    Route::get('/gigs/{gig}/applications', ListGigApplicationsController::class)->name('api.v1.gigs.applications.index');
    Route::patch('/gigs/{gig}/applications/{application}', UpdateGigApplicationStatusController::class)->name('api.v1.gigs.applications.update');
    Route::post('/gigs/{gig}/applications/{application}/withdraw', WithdrawGigApplicationController::class)->name('api.v1.gigs.applications.withdraw');
    Route::post('/gigs/{gig}/close', CloseGigController::class)->name('api.v1.gigs.close');
    Route::get('/gigs/{gig}/announcements', ListGigAnnouncementsController::class)->name('api.v1.gigs.announcements.index');
    Route::post('/gigs/{gig}/announcements', StoreGigAnnouncementController::class)->name('api.v1.gigs.announcements.store');
    Route::patch('/gigs/{gig}/announcements/{announcement}', UpdateGigAnnouncementController::class)->name('api.v1.gigs.announcements.update');
    Route::delete('/gigs/{gig}/announcements/{announcement}', DeleteGigAnnouncementController::class)->name('api.v1.gigs.announcements.delete');
    Route::get('/my/events', ListMyEventsController::class)->name('events.my');
    Route::get('/my/event-participant-invites', ListMyParticipantInvitesController::class)->name('events.participant-invites.my');
    Route::get('/my/event-audience-invites', ListMyAudienceInvitesController::class)->name('events.audience-invites.my');
    Route::patch('/event-participant-invites/{invite}/respond', RespondToParticipantInviteController::class)->name('events.participant-invites.respond');
    Route::patch('/event-audience-invites/{invite}/respond', RespondToAudienceInviteController::class)->name('events.audience-invites.respond');
    Route::post('/events/{event}/availability-options/{option}/vote', VoteForEventAvailabilityOptionController::class)->name('events.availability.vote');
    Route::delete('/events/{event}/availability-options/{option}/vote', RemoveEventAvailabilityVoteController::class)->name('events.availability.unvote');
    Route::post('/events/{event}/like', LikeEventController::class)->name('events.like');
    Route::delete('/events/{event}/like', UnlikeEventController::class)->name('events.unlike');
    Route::post('/events/{event}/bookmark', BookmarkEventController::class)->name('events.bookmark');
    Route::delete('/events/{event}/bookmark', RemoveBookmarkEventController::class)->name('events.unbookmark');
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
