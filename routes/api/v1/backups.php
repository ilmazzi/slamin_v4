<?php

use App\Http\Controllers\Api\V1\Admin\ListAdminBackupsController;
use App\Http\Controllers\Api\V1\Admin\RunAdminBackupController;
use Illuminate\Support\Facades\Route;

Route::middleware([
    'auth:sanctum',
    'account.verified',
    'role:admin',
])->prefix('admin/backups')->group(function (): void {
    Route::get('/', ListAdminBackupsController::class)->name('api.v1.admin.backups.index');
    Route::post('/', RunAdminBackupController::class)->name('api.v1.admin.backups.run');
});
