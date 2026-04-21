<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\Profile\UpdateProfileController;

/**
 * Profile routes
 */
Route::middleware('auth:sanctum')->group(function () {
    /**
     * Update the authenticated user's profile
     */
    Route::patch('/me/profile', UpdateProfileController::class)->name('api.v1.profile.update');
});
