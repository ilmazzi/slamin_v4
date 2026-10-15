<?php

use App\Http\Controllers\Api\V1\Profile\FollowProfileController;
use App\Http\Controllers\Api\V1\Profile\ListPublicProfileActivitiesController;
use App\Http\Controllers\Api\V1\Profile\ShowPublicProfileController;
use App\Http\Controllers\Api\V1\Profile\UnfollowProfileController;
use App\Http\Controllers\Api\V1\Profile\UpdateProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/profiles/{nickname}', ShowPublicProfileController::class)->name('api.v1.profile.show');
Route::get('/profiles/{nickname}/activities', ListPublicProfileActivitiesController::class)->name('api.v1.profile.activities');

Route::middleware('auth:sanctum')->group(function () {
    Route::patch('/me/profile', UpdateProfileController::class)->name('api.v1.profile.update');
});

Route::middleware(['auth:sanctum', 'account.verified'])->group(function () {
    Route::post('/profiles/{nickname}/follow', FollowProfileController::class)->name('api.v1.profile.follow');
    Route::delete('/profiles/{nickname}/follow', UnfollowProfileController::class)->name('api.v1.profile.unfollow');
});
