<?php

use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->prefix('admin')->group(function (): void {
    // Admin routes.
});
