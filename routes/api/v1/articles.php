<?php

use App\Http\Controllers\Api\V1\Article\AdminDeleteArticleCategoryController;
use App\Http\Controllers\Api\V1\Article\AdminListArticleCategoriesController;
use App\Http\Controllers\Api\V1\Article\AdminShowArticleCategoryController;
use App\Http\Controllers\Api\V1\Article\AdminStoreArticleCategoryController;
use App\Http\Controllers\Api\V1\Article\AdminUpdateArticleCategoryController;
use App\Http\Controllers\Api\V1\Article\BookmarkArticleController;
use App\Http\Controllers\Api\V1\Article\DeleteArticleController;
use App\Http\Controllers\Api\V1\Article\DeleteArticleTranslationController;
use App\Http\Controllers\Api\V1\Article\LikeArticleController;
use App\Http\Controllers\Api\V1\Article\ListArticleCategoriesController;
use App\Http\Controllers\Api\V1\Article\ListArticleController;
use App\Http\Controllers\Api\V1\Article\ListFeaturedArticlesController;
use App\Http\Controllers\Api\V1\Article\ListMyArticlesController;
use App\Http\Controllers\Api\V1\Article\RemoveBookmarkArticleController;
use App\Http\Controllers\Api\V1\Article\ShowArticleController;
use App\Http\Controllers\Api\V1\Article\ShowArticleTranslationController;
use App\Http\Controllers\Api\V1\Article\StoreArticleController;
use App\Http\Controllers\Api\V1\Article\UnlikeArticleController;
use App\Http\Controllers\Api\V1\Article\UpdateArticleController;
use App\Http\Controllers\Api\V1\Article\UpsertArticleTranslationController;
use Domain\ContentTranslation\Models\ContentTranslation;
use Illuminate\Support\Facades\Route;

Route::middleware([
    'auth:sanctum',
    'account.verified',
    'account.type:poet_organizer',
])->group(function () {
    Route::post('/articles', StoreArticleController::class)->name('articles.store');
    Route::get('/my/articles', ListMyArticlesController::class)->name('articles.my');
    Route::patch('/articles/{article}', UpdateArticleController::class)->name('articles.update');
    Route::delete('/articles/{article}', DeleteArticleController::class)->name('articles.delete');
    Route::put('/articles/{article}/translations/{locale}', UpsertArticleTranslationController::class)
        ->where('locale', ContentTranslation::LOCALE_PATTERN)
        ->name('articles.translations.upsert');
    Route::delete('/articles/{article}/translations/{locale}', DeleteArticleTranslationController::class)
        ->where('locale', ContentTranslation::LOCALE_PATTERN)
        ->name('articles.translations.delete');
});

Route::middleware([
    'auth:sanctum',
    'account.verified',
])->group(function () {
    Route::post('/articles/{article}/like', LikeArticleController::class)->name('articles.like');
    Route::delete('/articles/{article}/like', UnlikeArticleController::class)->name('articles.unlike');
    Route::post('/articles/{article}/bookmark', BookmarkArticleController::class)->name('articles.bookmark');
    Route::delete('/articles/{article}/bookmark', RemoveBookmarkArticleController::class)->name('articles.unbookmark');
});

Route::middleware([
    'auth:sanctum',
    'account.verified',
    'role:admin',
])->prefix('admin')->group(function () {
    Route::get('/article-categories', AdminListArticleCategoriesController::class)->name('admin.article-categories.index');
    Route::post('/article-categories', AdminStoreArticleCategoryController::class)->name('admin.article-categories.store');
    Route::get('/article-categories/{articleCategory}', AdminShowArticleCategoryController::class)->name('admin.article-categories.show');
    Route::patch('/article-categories/{articleCategory}', AdminUpdateArticleCategoryController::class)->name('admin.article-categories.update');
    Route::delete('/article-categories/{articleCategory}', AdminDeleteArticleCategoryController::class)->name('admin.article-categories.delete');
});

Route::get('/articles', ListArticleController::class)->name('articles.index');
Route::get('/articles/{slug}', ShowArticleController::class)->name('articles.show');
Route::get('/articles/{slug}/translations/{locale}', ShowArticleTranslationController::class)
    ->where('locale', ContentTranslation::LOCALE_PATTERN)
    ->name('articles.translations.show');
Route::get('/article-categories', ListArticleCategoriesController::class)->name('article-categories.index');
Route::get('/featured-articles', ListFeaturedArticlesController::class)->name('featured-articles.index');
