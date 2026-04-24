<?php

use App\Http\Controllers\Api\V1\Home\ShowHomeMediaController;
use App\Http\Controllers\Api\V1\Home\ShowHomeStatsController;
use Illuminate\Support\Facades\Route;

Route::get('/home/media', ShowHomeMediaController::class)->name('home.media');
Route::get('/home/stats', ShowHomeStatsController::class)->name('home.stats');

