<?php

use App\Http\Controllers\Api\V1\I18n\ExportI18nTranslationsController;
use App\Http\Controllers\Api\V1\I18n\ImportI18nTranslationsController;
use App\Http\Controllers\Api\V1\I18n\ListI18nLanguagesController;
use App\Http\Controllers\Api\V1\I18n\ListI18nTranslationKeysController;
use App\Http\Controllers\Api\V1\I18n\ShowI18nController;
use App\Http\Controllers\Api\V1\I18n\StoreI18nLanguageController;
use App\Http\Controllers\Api\V1\I18n\StoreI18nTranslationKeyController;
use App\Http\Controllers\Api\V1\I18n\UpdateI18nLanguageController;
use App\Http\Controllers\Api\V1\I18n\UpsertI18nTranslationController;
use Illuminate\Support\Facades\Route;

Route::get('i18n/{locale}.json', ShowI18nController::class)->name('api.v1.i18n.show');

Route::middleware([
    'auth:sanctum',
    'account.verified',
    'role:admin,translator',
])->prefix('admin/i18n')->group(function (): void {
    Route::get('/languages', ListI18nLanguagesController::class)->name('api.v1.admin.i18n.languages.index');
    Route::get('/keys', ListI18nTranslationKeysController::class)->name('api.v1.admin.i18n.keys.index');
    Route::get('/export', ExportI18nTranslationsController::class)->name('api.v1.admin.i18n.export');
    Route::post('/import', ImportI18nTranslationsController::class)->name('api.v1.admin.i18n.import');
    Route::put('/translations', UpsertI18nTranslationController::class)->name('api.v1.admin.i18n.translations.upsert');
});

Route::middleware([
    'auth:sanctum',
    'account.verified',
    'role:admin',
])->prefix('admin/i18n')->group(function (): void {
    Route::post('/languages', StoreI18nLanguageController::class)->name('api.v1.admin.i18n.languages.store');
    Route::patch('/languages/{language}', UpdateI18nLanguageController::class)->name('api.v1.admin.i18n.languages.update');
    Route::post('/keys', StoreI18nTranslationKeyController::class)->name('api.v1.admin.i18n.keys.store');
});
