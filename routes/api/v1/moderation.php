<?php

use App\Http\Controllers\Api\V1\Moderation\ApprovePoemController;
use App\Http\Controllers\Api\V1\Moderation\ApproveArticleController;
use App\Http\Controllers\Api\V1\Moderation\ApproveEventController;
use App\Http\Controllers\Api\V1\Moderation\GetModerationDashboardStatsController;
use App\Http\Controllers\Api\V1\Moderation\ListArticleModerationHistoryController;
use App\Http\Controllers\Api\V1\Moderation\ListEventModerationHistoryController;
use App\Http\Controllers\Api\V1\Moderation\ListModerationArticlesController;
use App\Http\Controllers\Api\V1\Moderation\ListModerationEventsController;
use App\Http\Controllers\Api\V1\Moderation\ListModerationPoemsController;
use App\Http\Controllers\Api\V1\Moderation\ListPendingReviewArticlesController;
use App\Http\Controllers\Api\V1\Moderation\ListPendingReviewEventsController;
use App\Http\Controllers\Api\V1\Moderation\ListPendingReviewPoemsController;
use App\Http\Controllers\Api\V1\Moderation\ListPoemModerationHistoryController;
use App\Http\Controllers\Api\V1\Moderation\ListReportsController;
use App\Http\Controllers\Api\V1\Moderation\RejectArticleController;
use App\Http\Controllers\Api\V1\Moderation\RejectEventController;
use App\Http\Controllers\Api\V1\Moderation\RejectPoemController;
use App\Http\Controllers\Api\V1\Moderation\ShowModerationArticleController;
use App\Http\Controllers\Api\V1\Moderation\ShowModerationEventController;
use App\Http\Controllers\Api\V1\Moderation\ShowModerationPoemController;
use App\Http\Controllers\Api\V1\Moderation\ShowReportController;
use App\Http\Controllers\Api\V1\Moderation\UpdateReportController;
use Illuminate\Support\Facades\Route;

Route::middleware([
    'auth:sanctum',
    'account.verified',
    'role:admin,moderator',
])->prefix('moderation')->group(function () {
    Route::get('/dashboard', GetModerationDashboardStatsController::class)->name('moderation.dashboard');
    Route::get('/poems', ListModerationPoemsController::class)->name('moderation.poems.index');
    Route::get('/poems/pending-review', ListPendingReviewPoemsController::class)->name('moderation.poems.pending-review');
    Route::get('/poems/{poem}', ShowModerationPoemController::class)->name('moderation.poems.show');
    Route::get('/poems/{poem}/history', ListPoemModerationHistoryController::class)->name('moderation.poems.history');
    Route::post('/poems/{poem}/approve', ApprovePoemController::class)->name('moderation.poems.approve');
    Route::post('/poems/{poem}/reject', RejectPoemController::class)->name('moderation.poems.reject');

    Route::get('/articles', ListModerationArticlesController::class)->name('moderation.articles.index');
    Route::get('/articles/pending-review', ListPendingReviewArticlesController::class)->name('moderation.articles.pending-review');
    Route::get('/articles/{article}', ShowModerationArticleController::class)->name('moderation.articles.show');
    Route::get('/articles/{article}/history', ListArticleModerationHistoryController::class)->name('moderation.articles.history');
    Route::post('/articles/{article}/approve', ApproveArticleController::class)->name('moderation.articles.approve');
    Route::post('/articles/{article}/reject', RejectArticleController::class)->name('moderation.articles.reject');

    Route::get('/events', ListModerationEventsController::class)->name('moderation.events.index');
    Route::get('/events/pending-review', ListPendingReviewEventsController::class)->name('moderation.events.pending-review');
    Route::get('/events/{event}', ShowModerationEventController::class)->name('moderation.events.show');
    Route::get('/events/{event}/history', ListEventModerationHistoryController::class)->name('moderation.events.history');
    Route::post('/events/{event}/approve', ApproveEventController::class)->name('moderation.events.approve');
    Route::post('/events/{event}/reject', RejectEventController::class)->name('moderation.events.reject');

    Route::get('/reports', ListReportsController::class)->name('moderation.reports.index');
    Route::get('/reports/{report}', ShowReportController::class)->name('moderation.reports.show');
    Route::patch('/reports/{report}', UpdateReportController::class)->name('moderation.reports.update');
});
