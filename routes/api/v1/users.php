<?php

use App\Http\Controllers\Api\V1\Admin\ListAdminUsersController;
use App\Http\Controllers\Api\V1\Admin\UpdateAdminUserController;
use Illuminate\Support\Facades\Route;

Route::middleware([
    'auth:sanctum',
    'account.verified',
    'role:admin',
])->prefix('admin/users')->group(function () {
    Route::get('/', ListAdminUsersController::class)->name('admin.users.index');
    Route::patch('/{user}', UpdateAdminUserController::class)->name('admin.users.update');
});
