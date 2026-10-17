<?php

use App\Http\Controllers\Share\ShowArticleShareCardController;
use App\Http\Controllers\Share\ShowArticleSharePageController;
use App\Http\Controllers\Share\ShowEventShareCardController;
use App\Http\Controllers\Share\ShowEventSharePageController;
use App\Http\Controllers\Share\ShowGigShareCardController;
use App\Http\Controllers\Share\ShowGigSharePageController;
use App\Http\Controllers\Share\ShowMediaShareCardController;
use App\Http\Controllers\Share\ShowMediaSharePageController;
use App\Http\Controllers\Share\ShowPoemShareCardController;
use App\Http\Controllers\Share\ShowPoemSharePageController;
use Domain\Share\Support\ShareCardFormat;
use Illuminate\Support\Facades\Route;

Route::get('/share/poems/{slug}/card.png', ShowPoemShareCardController::class)
    ->name('share.poems.card');
Route::get('/share/poems/{slug}/preview.png', ShowPoemShareCardController::class)
    ->defaults('format', ShareCardFormat::Landscape->value)
    ->name('share.poems.preview');
Route::get('/share/poems/{slug}', ShowPoemSharePageController::class)
    ->name('share.poems.show');

Route::get('/share/events/{slug}/card.png', ShowEventShareCardController::class)
    ->name('share.events.card');
Route::get('/share/events/{slug}/preview.png', ShowEventShareCardController::class)
    ->defaults('format', ShareCardFormat::Landscape->value)
    ->name('share.events.preview');
Route::get('/share/events/{slug}', ShowEventSharePageController::class)
    ->name('share.events.show');

Route::get('/share/articles/{slug}', ShowArticleSharePageController::class)
    ->name('share.articles.show');
Route::get('/share/articles/{slug}/card.png', ShowArticleShareCardController::class)
    ->name('share.articles.card');
Route::get('/share/articles/{slug}/preview.png', ShowArticleShareCardController::class)
    ->defaults('format', ShareCardFormat::Landscape->value)
    ->name('share.articles.preview');

Route::get('/share/media/{id}', ShowMediaSharePageController::class)
    ->whereNumber('id')
    ->name('share.media.show');
Route::get('/share/media/{id}/card.png', ShowMediaShareCardController::class)
    ->whereNumber('id')
    ->name('share.media.card');
Route::get('/share/media/{id}/preview.png', ShowMediaShareCardController::class)
    ->whereNumber('id')
    ->defaults('format', ShareCardFormat::Landscape->value)
    ->name('share.media.preview');

Route::get('/share/gigs/{id}', ShowGigSharePageController::class)
    ->whereNumber('id')
    ->name('share.gigs.show');
Route::get('/share/gigs/{id}/card.png', ShowGigShareCardController::class)
    ->whereNumber('id')
    ->name('share.gigs.card');
Route::get('/share/gigs/{id}/preview.png', ShowGigShareCardController::class)
    ->whereNumber('id')
    ->defaults('format', ShareCardFormat::Landscape->value)
    ->name('share.gigs.preview');
