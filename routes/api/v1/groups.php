<?php

use App\Http\Controllers\Api\V1\Group\AdminDeleteGroupCategoryController;
use App\Http\Controllers\Api\V1\Group\AdminListGroupCategoriesController;
use App\Http\Controllers\Api\V1\Group\AdminShowGroupCategoryController;
use App\Http\Controllers\Api\V1\Group\AdminStoreGroupCategoryController;
use App\Http\Controllers\Api\V1\Group\AdminUpdateGroupCategoryController;
use App\Http\Controllers\Api\V1\Group\ApproveGroupRequestController;
use App\Http\Controllers\Api\V1\Group\DeleteGroupAnnouncementController;
use App\Http\Controllers\Api\V1\Group\DeleteGroupController;
use App\Http\Controllers\Api\V1\Group\UpdateGroupChatThreadsController;
use App\Http\Controllers\Api\V1\Group\FollowGroupController;
use App\Http\Controllers\Api\V1\Group\InviteGroupMemberController;
use App\Http\Controllers\Api\V1\Group\LeaveGroupController;
use App\Http\Controllers\Api\V1\Group\ListGroupAnnouncementsController;
use App\Http\Controllers\Api\V1\Group\ListGroupCategoriesController;
use App\Http\Controllers\Api\V1\Group\ListGroupEventsController;
use App\Http\Controllers\Api\V1\Group\ListGroupPublicationsController;
use App\Http\Controllers\Api\V1\Group\ListGroupMembersController;
use App\Http\Controllers\Api\V1\Group\ListGroupsController;
use App\Http\Controllers\Api\V1\Group\ListMyGroupsController;
use App\Http\Controllers\Api\V1\Group\ListPendingGroupRequestsController;
use App\Http\Controllers\Api\V1\Group\RejectGroupRequestController;
use App\Http\Controllers\Api\V1\Group\RemoveGroupMemberController;
use App\Http\Controllers\Api\V1\Group\RequestGroupRoleController;
use App\Http\Controllers\Api\V1\Group\RequestToJoinGroupController;
use App\Http\Controllers\Api\V1\Group\AcceptGroupContentLinkController;
use App\Http\Controllers\Api\V1\Group\ListGroupContentLinksController;
use App\Http\Controllers\Api\V1\Group\RejectGroupContentLinkController;
use App\Http\Controllers\Api\V1\Group\RemoveGroupContentLinkController;
use App\Http\Controllers\Api\V1\Group\SearchGroupInviteUsersController;
use App\Http\Controllers\Api\V1\Group\AcceptGroupAffiliationController;
use App\Http\Controllers\Api\V1\Group\ListGroupAffiliationsController;
use App\Http\Controllers\Api\V1\Group\RejectGroupAffiliationController;
use App\Http\Controllers\Api\V1\Group\SearchAffiliableGroupsController;
use App\Http\Controllers\Api\V1\Group\SearchProposeableGroupsController;
use App\Http\Controllers\Api\V1\Group\StoreGroupAffiliationController;
use App\Http\Controllers\Api\V1\Group\ShowGroupController;
use App\Http\Controllers\Api\V1\Group\ShowMyGroupMembershipController;
use App\Http\Controllers\Api\V1\Group\StoreGroupAnnouncementController;
use App\Http\Controllers\Api\V1\Group\StoreGroupController;
use App\Http\Controllers\Api\V1\Group\UpdateGroupAnnouncementController;
use App\Http\Controllers\Api\V1\Group\UpdateGroupController;
use App\Http\Controllers\Api\V1\Group\UpdateGroupMemberRoleController;
use Illuminate\Support\Facades\Route;

Route::middleware([
    'auth:sanctum',
    'account.verified',
    'role:admin',
])->prefix('admin')->group(function () {
    Route::get('/group-categories', AdminListGroupCategoriesController::class)->name('admin.group-categories.index');
    Route::post('/group-categories', AdminStoreGroupCategoryController::class)->name('admin.group-categories.store');
    Route::get('/group-categories/{groupCategory}', AdminShowGroupCategoryController::class)->name('admin.group-categories.show');
    Route::patch('/group-categories/{groupCategory}', AdminUpdateGroupCategoryController::class)->name('admin.group-categories.update');
    Route::delete('/group-categories/{groupCategory}', AdminDeleteGroupCategoryController::class)->name('admin.group-categories.delete');
});

Route::middleware([
    'auth:sanctum',
    'account.verified',
])->group(function () {
    Route::get('/group-categories', ListGroupCategoriesController::class)->name('group-categories.index');
    Route::get('/groups', ListGroupsController::class)->name('groups.index');
    Route::get('/groups/propose', SearchProposeableGroupsController::class)->name('groups.propose');
    Route::get('/groups/{slug}', ShowGroupController::class)->name('groups.show');
    Route::get('/my/groups', ListMyGroupsController::class)->name('groups.my');
    Route::post('/groups', StoreGroupController::class)->name('groups.store');
    Route::patch('/groups/{group}', UpdateGroupController::class)->name('groups.update');
    Route::delete('/groups/{group}', DeleteGroupController::class)->name('groups.delete');

    Route::get('/groups/{group}/membership', ShowMyGroupMembershipController::class)->name('groups.membership.show');
    Route::post('/groups/{group}/follow', FollowGroupController::class)->name('groups.follow');
    Route::post('/groups/{group}/join', RequestToJoinGroupController::class)->name('groups.join');
    Route::post('/groups/{group}/role-requests', RequestGroupRoleController::class)->name('groups.role-requests.store');
    Route::get('/groups/{group}/invite-users', SearchGroupInviteUsersController::class)->name('groups.invite-users');
    Route::post('/groups/{group}/invites', InviteGroupMemberController::class)->name('groups.invites.store');
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
    Route::get('/groups/{group}/publications', ListGroupPublicationsController::class)->name('groups.publications.index');
    Route::get('/groups/{group}/content-links', ListGroupContentLinksController::class)->name('groups.content-links.index');
    Route::post('/groups/{group}/content-links/{link}/accept', AcceptGroupContentLinkController::class)->name('groups.content-links.accept');
    Route::post('/groups/{group}/content-links/{link}/reject', RejectGroupContentLinkController::class)->name('groups.content-links.reject');
    Route::delete('/groups/{group}/content-links/{link}', RemoveGroupContentLinkController::class)->name('groups.content-links.delete');
    Route::get('/groups/{group}/affiliable', SearchAffiliableGroupsController::class)->name('groups.affiliable');
    Route::get('/groups/{group}/affiliations', ListGroupAffiliationsController::class)->name('groups.affiliations.index');
    Route::post('/groups/{group}/affiliations', StoreGroupAffiliationController::class)->name('groups.affiliations.store');
    Route::post('/groups/{group}/affiliations/{affiliation}/accept', AcceptGroupAffiliationController::class)->name('groups.affiliations.accept');
    Route::post('/groups/{group}/affiliations/{affiliation}/reject', RejectGroupAffiliationController::class)->name('groups.affiliations.reject');
    Route::patch('/groups/{group}/chat-threads', UpdateGroupChatThreadsController::class)->name('groups.chat-threads.update');
});
