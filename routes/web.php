<?php

use App\Http\Controllers\Share\ShowEventShareCardController;
use App\Http\Controllers\Share\ShowEventSharePageController;
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
