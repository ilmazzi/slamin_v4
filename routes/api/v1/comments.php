<?php

use App\Http\Controllers\Api\V1\Comment\DeleteCommentController;
use App\Http\Controllers\Api\V1\Comment\ListArticleCommentsController;
use App\Http\Controllers\Api\V1\Comment\ListEventCommentsController;
use App\Http\Controllers\Api\V1\Comment\ListGroupCommentsController;
use App\Http\Controllers\Api\V1\Comment\ListPoemCommentsController;
use App\Http\Controllers\Api\V1\Comment\StoreArticleCommentController;
use App\Http\Controllers\Api\V1\Comment\StoreEventCommentController;
use App\Http\Controllers\Api\V1\Comment\StoreGroupCommentController;
use App\Http\Controllers\Api\V1\Comment\StorePoemCommentController;
use App\Http\Controllers\Api\V1\Comment\UpdateCommentController;
use Illuminate\Support\Facades\Route;

Route::get('/poems/{poem}/comments', ListPoemCommentsController::class)->name('poems.comments.index');
Route::get('/articles/{article}/comments', ListArticleCommentsController::class)->name('articles.comments.index');
Route::get('/events/{event}/comments', ListEventCommentsController::class)->name('events.comments.index');

Route::middleware([
    'auth:sanctum',
    'account.verified',
])->group(function () {
    Route::get('/groups/{group}/comments', ListGroupCommentsController::class)->name('groups.comments.index');
    Route::post('/poems/{poem}/comments', StorePoemCommentController::class)->name('poems.comments.store');
    Route::post('/articles/{article}/comments', StoreArticleCommentController::class)->name('articles.comments.store');
    Route::post('/events/{event}/comments', StoreEventCommentController::class)->name('events.comments.store');
    Route::post('/groups/{group}/comments', StoreGroupCommentController::class)->name('groups.comments.store');
    Route::patch('/comments/{comment}', UpdateCommentController::class)->name('comments.update');
    Route::delete('/comments/{comment}', DeleteCommentController::class)->name('comments.delete');
});
