<?php

use App\Http\Controllers\Share\ShowArticleSharePageController;
use App\Http\Controllers\Share\ShowEventShareCardController;
use App\Http\Controllers\Share\ShowEventSharePageController;
use App\Http\Controllers\Share\ShowMediaSharePageController;
use App\Http\Controllers\Share\ShowPoemShareCardController;
use App\Http\Controllers\Share\ShowPoemSharePageController;
use Illuminate\Support\Facades\Route;

Route::get('/share/poems/{slug}/card.png', ShowPoemShareCardController::class)
    ->name('share.poems.card');
Route::get('/share/poems/{slug}', ShowPoemSharePageController::class)
    ->name('share.poems.show');

Route::get('/share/events/{slug}/card.png', ShowEventShareCardController::class)
    ->name('share.events.card');
Route::get('/share/events/{slug}', ShowEventSharePageController::class)
    ->name('share.events.show');

Route::get('/share/articles/{slug}', ShowArticleSharePageController::class)
    ->name('share.articles.show');

Route::get('/share/media/{id}', ShowMediaSharePageController::class)
    ->whereNumber('id')
    ->name('share.media.show');
