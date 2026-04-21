<?php

use App\Http\Controllers\Api\V1\Comment\DeleteCommentController;
use App\Http\Controllers\Api\V1\Comment\ListArticleCommentsController;
use App\Http\Controllers\Api\V1\Comment\ListPoemCommentsController;
use App\Http\Controllers\Api\V1\Comment\StoreArticleCommentController;
use App\Http\Controllers\Api\V1\Comment\StorePoemCommentController;
use App\Http\Controllers\Api\V1\Comment\UpdateCommentController;
use Illuminate\Support\Facades\Route;

Route::get('/poems/{poem}/comments', ListPoemCommentsController::class)->name('poems.comments.index');
Route::get('/articles/{article}/comments', ListArticleCommentsController::class)->name('articles.comments.index');

Route::middleware([
    'auth:sanctum',
    'account.verified',
])->group(function () {
    Route::post('/poems/{poem}/comments', StorePoemCommentController::class)->name('poems.comments.store');
    Route::post('/articles/{article}/comments', StoreArticleCommentController::class)->name('articles.comments.store');
    Route::patch('/comments/{comment}', UpdateCommentController::class)->name('comments.update');
    Route::delete('/comments/{comment}', DeleteCommentController::class)->name('comments.delete');
});
