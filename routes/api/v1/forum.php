<?php

use App\Http\Controllers\Api\V1\Forum\AdminDeleteForumCategoryController;
use App\Http\Controllers\Api\V1\Forum\AdminListForumCategoriesController;
use App\Http\Controllers\Api\V1\Forum\AdminShowForumCategoryController;
use App\Http\Controllers\Api\V1\Forum\AdminStoreForumCategoryController;
use App\Http\Controllers\Api\V1\Forum\AdminUpdateForumCategoryController;
use App\Http\Controllers\Api\V1\Forum\DeleteForumPostController;
use App\Http\Controllers\Api\V1\Forum\ListForumCategoriesController;
use App\Http\Controllers\Api\V1\Forum\ListForumPostCommentsController;
use App\Http\Controllers\Api\V1\Forum\ListForumPostsController;
use App\Http\Controllers\Api\V1\Forum\ShowForumCategoryController;
use App\Http\Controllers\Api\V1\Forum\ShowForumPostBySlugController;
use App\Http\Controllers\Api\V1\Forum\ShowForumPostController;
use App\Http\Controllers\Api\V1\Forum\StoreForumPostCommentController;
use App\Http\Controllers\Api\V1\Forum\StoreForumPostController;
use App\Http\Controllers\Api\V1\Forum\StoreForumPostReportController;
use App\Http\Controllers\Api\V1\Forum\UpdateForumPostController;
use App\Http\Controllers\Api\V1\Forum\VoteForumCommentController;
use App\Http\Controllers\Api\V1\Forum\VoteForumPostController;
use Illuminate\Support\Facades\Route;

Route::get('/forum/categories', ListForumCategoriesController::class)->name('forum.categories.index');
Route::get('/forum/categories/{categorySlug}/posts/{postSlug}', ShowForumPostBySlugController::class)
    ->name('forum.posts.show-by-slug');
Route::get('/forum/categories/{categorySlug}', ShowForumCategoryController::class)->name('forum.categories.show');
Route::get('/forum/posts', ListForumPostsController::class)->name('forum.posts.index');
Route::get('/forum/posts/{forumPost}', ShowForumPostController::class)->name('forum.posts.show');
Route::get('/forum/posts/{forumPost}/comments', ListForumPostCommentsController::class)->name('forum.posts.comments.index');

Route::middleware([
    'auth:sanctum',
    'account.verified',
])->group(function () {
    Route::post('/forum/posts', StoreForumPostController::class)->name('forum.posts.store');
    Route::patch('/forum/posts/{forumPost}', UpdateForumPostController::class)->name('forum.posts.update');
    Route::delete('/forum/posts/{forumPost}', DeleteForumPostController::class)->name('forum.posts.delete');
    Route::post('/forum/posts/{forumPost}/vote', VoteForumPostController::class)->name('forum.posts.vote');
    Route::post('/forum/posts/{forumPost}/comments', StoreForumPostCommentController::class)->name('forum.posts.comments.store');
    Route::post('/forum/posts/{forumPost}/reports', StoreForumPostReportController::class)->name('forum.posts.reports.store');
    Route::post('/comments/{comment}/vote', VoteForumCommentController::class)->name('comments.vote');
});

Route::middleware([
    'auth:sanctum',
    'account.verified',
    'role:admin',
])->prefix('admin')->group(function () {
    Route::get('/forum-categories', AdminListForumCategoriesController::class)->name('admin.forum-categories.index');
    Route::post('/forum-categories', AdminStoreForumCategoryController::class)->name('admin.forum-categories.store');
    Route::get('/forum-categories/{forumCategory}', AdminShowForumCategoryController::class)->name('admin.forum-categories.show');
    Route::patch('/forum-categories/{forumCategory}', AdminUpdateForumCategoryController::class)->name('admin.forum-categories.update');
    Route::delete('/forum-categories/{forumCategory}', AdminDeleteForumCategoryController::class)->name('admin.forum-categories.delete');
});
