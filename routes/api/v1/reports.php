<?php

use App\Http\Controllers\Api\V1\Report\StoreArticleReportController;
use App\Http\Controllers\Api\V1\Report\StoreCommentReportController;
use App\Http\Controllers\Api\V1\Report\StoreMediaReportController;
use App\Http\Controllers\Api\V1\Report\StorePoemReportController;
use Illuminate\Support\Facades\Route;

Route::middleware([
    'auth:sanctum',
    'account.verified',
])->group(function () {
    Route::post('/poems/{poem}/reports', StorePoemReportController::class)->name('poems.reports.store');
    Route::post('/articles/{article}/reports', StoreArticleReportController::class)->name('articles.reports.store');
    Route::post('/media/{media}/reports', StoreMediaReportController::class)->name('media.reports.store');
    Route::post('/comments/{comment}/reports', StoreCommentReportController::class)->name('comments.reports.store');
});
