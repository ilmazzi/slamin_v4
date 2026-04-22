<?php

use App\Http\Controllers\Api\V1\Group\ApproveGroupRequestController;
use App\Http\Controllers\Api\V1\Group\DeleteGroupAnnouncementController;
use App\Http\Controllers\Api\V1\Group\DeleteGroupController;
use App\Http\Controllers\Api\V1\Group\EnsureGroupChatThreadController;
use App\Http\Controllers\Api\V1\Group\LeaveGroupController;
use App\Http\Controllers\Api\V1\Group\ListGroupAnnouncementsController;
use App\Http\Controllers\Api\V1\Group\ListGroupEventsController;
use App\Http\Controllers\Api\V1\Group\ListGroupMembersController;
use App\Http\Controllers\Api\V1\Group\ListGroupsController;
use App\Http\Controllers\Api\V1\Group\ListMyGroupsController;
use App\Http\Controllers\Api\V1\Group\ListPendingGroupRequestsController;
use App\Http\Controllers\Api\V1\Group\RejectGroupRequestController;
use App\Http\Controllers\Api\V1\Group\RemoveGroupMemberController;
use App\Http\Controllers\Api\V1\Group\RequestToJoinGroupController;
use App\Http\Controllers\Api\V1\Group\ShowGroupController;
use App\Http\Controllers\Api\V1\Group\ShowMyGroupMembershipController;
use App\Http\Controllers\Api\V1\Group\StoreGroupAnnouncementController;
use App\Http\Controllers\Api\V1\Group\StoreGroupController;
use App\Http\Controllers\Api\V1\Group\UpdateGroupAnnouncementController;
use App\Http\Controllers\Api\V1\Group\UpdateGroupController;
use App\Http\Controllers\Api\V1\Group\UpdateGroupMemberRoleController;
use Illuminate\Support\Facades\Route;

Route::get('/groups', ListGroupsController::class)->name('groups.index');
Route::get('/groups/{slug}', ShowGroupController::class)->name('groups.show');

Route::middleware([
    'auth:sanctum',
    'account.verified',
])->group(function () {
    Route::get('/my/groups', ListMyGroupsController::class)->name('groups.my');
    Route::post('/groups', StoreGroupController::class)->name('groups.store');
    Route::patch('/groups/{group}', UpdateGroupController::class)->name('groups.update');
    Route::delete('/groups/{group}', DeleteGroupController::class)->name('groups.delete');

    Route::get('/groups/{group}/membership', ShowMyGroupMembershipController::class)->name('groups.membership.show');
    Route::post('/groups/{group}/join', RequestToJoinGroupController::class)->name('groups.join');
    Route::post('/groups/{group}/leave', LeaveGroupController::class)->name('groups.leave');
    Route::get('/groups/{group}/members', ListGroupMembersController::class)->name('groups.members.index');
    Route::get('/groups/{group}/requests', ListPendingGroupRequestsController::class)->name('groups.requests.index');
    Route::post('/groups/{group}/requests/{member}/approve', ApproveGroupRequestController::class)->name('groups.requests.approve');
    Route::post('/groups/{group}/requests/{member}/reject', RejectGroupRequestController::class)->name('groups.requests.reject');
    Route::patch('/groups/{group}/members/{member}/role', UpdateGroupMemberRoleController::class)->name('groups.members.role.update');
    Route::delete('/groups/{group}/members/{member}', RemoveGroupMemberController::class)->name('groups.members.delete');

    Route::get('/groups/{group}/announcements', ListGroupAnnouncementsController::class)->name('groups.announcements.index');
    Route::post('/groups/{group}/announcements', StoreGroupAnnouncementController::class)->name('groups.announcements.store');
    Route::patch('/groups/{group}/announcements/{announcement}', UpdateGroupAnnouncementController::class)->name('groups.announcements.update');
    Route::delete('/groups/{group}/announcements/{announcement}', DeleteGroupAnnouncementController::class)->name('groups.announcements.delete');

    Route::get('/groups/{group}/events', ListGroupEventsController::class)->name('groups.events.index');
    Route::post('/groups/{group}/chat-thread', EnsureGroupChatThreadController::class)->name('groups.chat-thread.store');
});
