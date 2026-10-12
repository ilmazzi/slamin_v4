<?php

use App\Http\Controllers\Api\V1\Help\AdminDeleteHelpArticleController;
use App\Http\Controllers\Api\V1\Help\AdminDeleteHelpCategoryController;
use App\Http\Controllers\Api\V1\Help\AdminListHelpArticlesController;
use App\Http\Controllers\Api\V1\Help\AdminListHelpCategoriesController;
use App\Http\Controllers\Api\V1\Help\AdminStoreHelpArticleController;
use App\Http\Controllers\Api\V1\Help\AdminStoreHelpCategoryController;
use App\Http\Controllers\Api\V1\Help\AdminUpdateHelpArticleController;
use App\Http\Controllers\Api\V1\Help\AdminUpdateHelpCategoryController;
use App\Http\Controllers\Api\V1\Help\ListHelpController;
use Illuminate\Support\Facades\Route;

Route::get('/help', ListHelpController::class)->name('help.index');

Route::middleware([
    'auth:sanctum',
    'account.verified',
    'role:admin',
])->prefix('admin')->group(function () {
    Route::get('/help/categories', AdminListHelpCategoriesController::class)->name('admin.help.categories.index');
    Route::post('/help/categories', AdminStoreHelpCategoryController::class)->name('admin.help.categories.store');
    Route::patch('/help/categories/{helpCategory}', AdminUpdateHelpCategoryController::class)->name('admin.help.categories.update');
    Route::delete('/help/categories/{helpCategory}', AdminDeleteHelpCategoryController::class)->name('admin.help.categories.destroy');

    Route::get('/help/articles', AdminListHelpArticlesController::class)->name('admin.help.articles.index');
    Route::post('/help/articles', AdminStoreHelpArticleController::class)->name('admin.help.articles.store');
    Route::patch('/help/articles/{helpArticle}', AdminUpdateHelpArticleController::class)->name('admin.help.articles.update');
    Route::delete('/help/articles/{helpArticle}', AdminDeleteHelpArticleController::class)->name('admin.help.articles.destroy');
});
