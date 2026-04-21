<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\Poetry\StorePoemController;
use App\Http\Controllers\Api\V1\Poetry\AdminDeletePoemCategoryController;
use App\Http\Controllers\Api\V1\Poetry\AdminDeletePoemTypeController;
use App\Http\Controllers\Api\V1\Poetry\AdminListPoemCategoriesController;
use App\Http\Controllers\Api\V1\Poetry\AdminListPoemTypesController;
use App\Http\Controllers\Api\V1\Poetry\AdminShowPoemCategoryController;
use App\Http\Controllers\Api\V1\Poetry\AdminShowPoemTypeController;
use App\Http\Controllers\Api\V1\Poetry\AdminStorePoemCategoryController;
use App\Http\Controllers\Api\V1\Poetry\AdminStorePoemTypeController;
use App\Http\Controllers\Api\V1\Poetry\AdminUpdatePoemCategoryController;
use App\Http\Controllers\Api\V1\Poetry\AdminUpdatePoemTypeController;
use App\Http\Controllers\Api\V1\Admin\ListContentPublicationSettingsController;
use App\Http\Controllers\Api\V1\Admin\UpdateContentPublicationSettingController;
use App\Http\Controllers\Api\V1\Poetry\ListPoemController;
use App\Http\Controllers\Api\V1\Poetry\ListMyPoemsController;
use App\Http\Controllers\Api\V1\Poetry\ShowPoemController;
use App\Http\Controllers\Api\V1\Poetry\UpdatePoemController;
use App\Http\Controllers\Api\V1\Poetry\DeletePoemController;
use App\Http\Controllers\Api\V1\Poetry\BookmarkPoemController;
use App\Http\Controllers\Api\V1\Poetry\LikePoemController;
use App\Http\Controllers\Api\V1\Poetry\ListPoemCategoriesController;
use App\Http\Controllers\Api\V1\Poetry\ListPoemTypesController;
use App\Http\Controllers\Api\V1\Poetry\RemoveBookmarkPoemController;
use App\Http\Controllers\Api\V1\Poetry\UnlikePoemController;
/* Poem routes for poet organizer */
Route::middleware([
    'auth:sanctum',
    'account.verified',
    'account.type:poet_organizer',
])->group(function () {
    Route::post('/poems', StorePoemController::class)->name('poems.store');
    Route::get('/my/poems', ListMyPoemsController::class)->name('poems.my');
    Route::patch('/poems/{poem}', UpdatePoemController::class)->name('poems.update');
    Route::delete('/poems/{poem}', DeletePoemController::class)->name('poems.delete');
});

/* Poem routes for public */
Route::middleware([
    'auth:sanctum',
    'account.verified',
])->group(function () {
    Route::post('/poems/{poem}/like', LikePoemController::class)->name('poems.like');
Route::delete('/poems/{poem}/like', UnlikePoemController::class)->name('poems.unlike');

Route::post('/poems/{poem}/bookmark', BookmarkPoemController::class)->name('poems.bookmark');
Route::delete('/poems/{poem}/bookmark', RemoveBookmarkPoemController::class)->name('poems.unbookmark');
});

Route::middleware([
    'auth:sanctum',
    'account.verified',
    'role:admin',
])->prefix('admin')->group(function () {
    Route::get('/poem-categories', AdminListPoemCategoriesController::class)->name('admin.poem-categories.index');
    Route::post('/poem-categories', AdminStorePoemCategoryController::class)->name('admin.poem-categories.store');
    Route::get('/poem-categories/{poemCategory}', AdminShowPoemCategoryController::class)->name('admin.poem-categories.show');
    Route::patch('/poem-categories/{poemCategory}', AdminUpdatePoemCategoryController::class)->name('admin.poem-categories.update');
    Route::delete('/poem-categories/{poemCategory}', AdminDeletePoemCategoryController::class)->name('admin.poem-categories.delete');

    Route::get('/poem-types', AdminListPoemTypesController::class)->name('admin.poem-types.index');
    Route::post('/poem-types', AdminStorePoemTypeController::class)->name('admin.poem-types.store');
    Route::get('/poem-types/{poemType}', AdminShowPoemTypeController::class)->name('admin.poem-types.show');
    Route::patch('/poem-types/{poemType}', AdminUpdatePoemTypeController::class)->name('admin.poem-types.update');
    Route::delete('/poem-types/{poemType}', AdminDeletePoemTypeController::class)->name('admin.poem-types.delete');

    Route::get('/content-publication-settings', ListContentPublicationSettingsController::class)->name('admin.content-publication-settings.index');
    Route::patch('/content-publication-settings/{contentPublicationSetting}', UpdateContentPublicationSettingController::class)->name('admin.content-publication-settings.update');
});


/* Poem routes for public */
Route::get('/poems', ListPoemController::class)->name('poems.index');
Route::get('/poems/{slug}', ShowPoemController::class)->name('poems.show');
Route::get('/poem-categories', ListPoemCategoriesController::class)->name('poem-categories.index');
Route::get('/poem-types', ListPoemTypesController::class)->name('poem-types.index');
