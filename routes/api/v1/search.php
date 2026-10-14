<?php

use App\Http\Controllers\Api\V1\Search\ShowSearchController;
use Illuminate\Support\Facades\Route;

Route::get('/search', ShowSearchController::class)->name('search.show');
