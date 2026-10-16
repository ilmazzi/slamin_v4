<?php

use App\Http\Controllers\Api\V1\Support\StoreSupportRequestController;
use Illuminate\Support\Facades\Route;

Route::post('support', StoreSupportRequestController::class)
    ->middleware('throttle:5,1')
    ->name('api.v1.support.store');
