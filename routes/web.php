<?php

use App\Http\Controllers\Share\ShowPoemShareCardController;
use App\Http\Controllers\Share\ShowPoemSharePageController;
use Illuminate\Support\Facades\Route;

Route::get('/share/poems/{slug}/card.png', ShowPoemShareCardController::class)
    ->name('share.poems.card');
Route::get('/share/poems/{slug}', ShowPoemSharePageController::class)
    ->name('share.poems.show');
