<?php

use App\Http\Controllers\Api\V1\Auth\RegisterController;
use App\Http\Controllers\Api\V1\Auth\ForgotPasswordController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\Auth\LoginController;
use App\Http\Controllers\Api\V1\Auth\MeController;
use App\Http\Controllers\Api\V1\Auth\LogoutController;
use App\Http\Controllers\Api\V1\Auth\ResetPasswordController;
use App\Http\Controllers\Api\V1\Auth\VerifyEmailController;
use App\Http\Controllers\Api\V1\Auth\SendEmailVerificationController;
use App\Http\Controllers\Api\V1\Auth\GetDashboardWidgetsController;


/**
 * Auth routes
 */
Route::middleware('guest:sanctum')->group(function () {

    /**
     * Login
     */
    Route::middleware(['throttle:login'])->group(function () {
        Route::post('/login', LoginController::class)->name('api.v1.login');
    });

    /**
     * Register
     */
    Route::middleware(['throttle:register'])->group(function () {
        Route::post('/register', RegisterController::class)->name('api.v1.register');
    });

    /**
     * Forgot password
     */
    Route::middleware(['throttle:forgot-password'])->group(function () {
        Route::post('/forgot-password', ForgotPasswordController::class)->name('api.v1.forgot-password');
    });

    /**
     * Reset password
     */

     Route::post('/reset-password', ResetPasswordController::class)
     ->name('api.v1.reset-password');

     /**
      * Verify email
      */
      Route::middleware(['throttle:verify-email'])->group(function () {
        Route::post('/email/verify', VerifyEmailController::class)->name('api.v1.email.verify');
    });
});


Route::middleware('auth:sanctum')->group(function () {
    /**
     * Get the authenticated user
     */
    Route::get('/me', MeController::class)->name('api.v1.me');
    Route::get('/me/dashboard-widgets', GetDashboardWidgetsController::class)->name('api.v1.me.dashboard-widgets');
    Route::middleware(['throttle:verification-notification'])->group(function () {
        Route::post('/email/verification-notification', SendEmailVerificationController::class)->name('api.v1.email.verification-notification');
    });
    Route::post('/logout', LogoutController::class)->name('api.v1.logout');
});

