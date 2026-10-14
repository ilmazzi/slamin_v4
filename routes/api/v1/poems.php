<?php

use App\Http\Controllers\Api\V1\Admin\ListContentPublicationSettingsController;
use App\Http\Controllers\Api\V1\Admin\UpdateContentPublicationSettingController;
use App\Http\Controllers\Api\V1\Gig\AgreeGigNegotiationController;
use App\Http\Controllers\Api\V1\Gig\ApproveTranslationJobController;
use App\Http\Controllers\Api\V1\Gig\DeletePoemTranslationGigController;
use App\Http\Controllers\Api\V1\Gig\ListGigNegotiationsController;
use App\Http\Controllers\Api\V1\Gig\ListPoemTranslationGigsController;
use App\Http\Controllers\Api\V1\Gig\ListTranslationCommentsController;
use App\Http\Controllers\Api\V1\Gig\OpenGigNegotiationController;
use App\Http\Controllers\Api\V1\Gig\RequestTranslationChangesController;
use App\Http\Controllers\Api\V1\Gig\ResolveTranslationCommentController;
use App\Http\Controllers\Api\V1\Gig\ShowGigNegotiationController;
use App\Http\Controllers\Api\V1\Gig\ShowTranslationJobController;
use App\Http\Controllers\Api\V1\Gig\StoreGigNegotiationEventController;
use App\Http\Controllers\Api\V1\Gig\StorePoemTranslationGigController;
use App\Http\Controllers\Api\V1\Gig\StoreTranslationCommentController;
use App\Http\Controllers\Api\V1\Gig\SubmitTranslationJobController;
use App\Http\Controllers\Api\V1\Gig\UpdatePoemTranslationGigController;
use App\Http\Controllers\Api\V1\Gig\UpdateTranslationCommentController;
use App\Http\Controllers\Api\V1\Gig\UpdateTranslationJobController;
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
use App\Http\Controllers\Api\V1\Poetry\BookmarkPoemController;
use App\Http\Controllers\Api\V1\Poetry\DeletePoemController;
use App\Http\Controllers\Api\V1\Poetry\LikePoemController;
use App\Http\Controllers\Api\V1\Poetry\ListFeaturedPoemsController;
use App\Http\Controllers\Api\V1\Poetry\ListMyPoemsController;
use App\Http\Controllers\Api\V1\Poetry\ListPoemCategoriesController;
use App\Http\Controllers\Api\V1\Poetry\ListPoemController;
use App\Http\Controllers\Api\V1\Poetry\ListPoemTypesController;
use App\Http\Controllers\Api\V1\Poetry\RemoveBookmarkPoemController;
use App\Http\Controllers\Api\V1\Poetry\ShowPoemController;
use App\Http\Controllers\Api\V1\Poetry\StorePoemController;
use App\Http\Controllers\Api\V1\Poetry\UnlikePoemController;
use App\Http\Controllers\Api\V1\Poetry\UpdatePoemController;
use App\Http\Controllers\Share\ShowPoemShareCardController;
use Illuminate\Support\Facades\Route;

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
    Route::get('/poems/{poem}/translation-gigs', ListPoemTranslationGigsController::class)->name('api.v1.poems.translation-gigs.index');
    Route::post('/poems/{poem}/translation-gigs', StorePoemTranslationGigController::class)->name('api.v1.poems.translation-gigs.store');
    Route::patch('/poems/{poem}/translation-gigs/{gig}', UpdatePoemTranslationGigController::class)->name('api.v1.poems.translation-gigs.update');
    Route::delete('/poems/{poem}/translation-gigs/{gig}', DeletePoemTranslationGigController::class)->name('api.v1.poems.translation-gigs.delete');
});

/* Poem routes for public */
Route::middleware([
    'auth:sanctum',
    'account.verified',
])->group(function () {
    Route::get('/gigs/{gig}/negotiations', ListGigNegotiationsController::class)->name('api.v1.gigs.negotiations.index');
    Route::post('/gigs/{gig}/applications/{application}/negotiations', OpenGigNegotiationController::class)->name('api.v1.gigs.negotiations.store');
    Route::get('/gig-negotiations/{negotiation}', ShowGigNegotiationController::class)->name('api.v1.gig-negotiations.show');
    Route::post('/gig-negotiations/{negotiation}/events', StoreGigNegotiationEventController::class)->name('api.v1.gig-negotiations.events.store');
    Route::post('/gig-negotiations/{negotiation}/agree', AgreeGigNegotiationController::class)->name('api.v1.gig-negotiations.agree');
    Route::get('/translation-jobs/{job}', ShowTranslationJobController::class)->name('api.v1.translation-jobs.show');
    Route::patch('/translation-jobs/{job}', UpdateTranslationJobController::class)->name('api.v1.translation-jobs.update');
    Route::post('/translation-jobs/{job}/submit', SubmitTranslationJobController::class)->name('api.v1.translation-jobs.submit');
    Route::post('/translation-jobs/{job}/request-changes', RequestTranslationChangesController::class)->name('api.v1.translation-jobs.request-changes');
    Route::post('/translation-jobs/{job}/approve', ApproveTranslationJobController::class)->name('api.v1.translation-jobs.approve');
    Route::get('/translation-jobs/{job}/comments', ListTranslationCommentsController::class)->name('api.v1.translation-jobs.comments.index');
    Route::post('/translation-jobs/{job}/comments', StoreTranslationCommentController::class)->name('api.v1.translation-jobs.comments.store');
    Route::patch('/translation-jobs/{job}/comments/{comment}', UpdateTranslationCommentController::class)->name('api.v1.translation-jobs.comments.update');
    Route::post('/translation-jobs/{job}/comments/{comment}/resolve', ResolveTranslationCommentController::class)->name('api.v1.translation-jobs.comments.resolve');
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
Route::get('/featured-poems', ListFeaturedPoemsController::class)->name('featured-poems.index');
Route::get('/poems/{slug}/share-card', ShowPoemShareCardController::class)->name('poems.share-card');
Route::get('/poems/{slug}', ShowPoemController::class)->name('poems.show');
Route::get('/poem-categories', ListPoemCategoriesController::class)->name('poem-categories.index');
Route::get('/poem-types', ListPoemTypesController::class)->name('poem-types.index');
