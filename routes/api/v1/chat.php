<?php

use App\Http\Controllers\Api\V1\Chat\AddChatThreadMemberController;
use App\Http\Controllers\Api\V1\Chat\DeleteChatMessageReactionController;
use App\Http\Controllers\Api\V1\Chat\ListChatMessagesController;
use App\Http\Controllers\Api\V1\Chat\ListChatThreadsController;
use App\Http\Controllers\Api\V1\Chat\MarkChatThreadReadController;
use App\Http\Controllers\Api\V1\Chat\ShowChatThreadController;
use App\Http\Controllers\Api\V1\Chat\StoreChatMessageController;
use App\Http\Controllers\Api\V1\Chat\StoreChatMessageReactionController;
use App\Http\Controllers\Api\V1\Chat\StoreDirectChatThreadController;
use App\Http\Controllers\Api\V1\Chat\StoreGroupChatThreadController;
use App\Http\Controllers\Api\V1\Chat\RemoveChatThreadMemberController;
use Illuminate\Support\Facades\Route;

Route::middleware([
    'auth:sanctum',
    'account.verified',
])->group(function () {
    Route::get('/chat/threads', ListChatThreadsController::class)->name('chat.threads.index');
    Route::post('/chat/direct-threads', StoreDirectChatThreadController::class)->name('chat.direct-threads.store');
    Route::post('/chat/group-threads', StoreGroupChatThreadController::class)->name('chat.group-threads.store');
    Route::get('/chat/threads/{thread}', ShowChatThreadController::class)->name('chat.threads.show');
    Route::post('/chat/threads/{thread}/members', AddChatThreadMemberController::class)->name('chat.threads.members.store');
    Route::delete('/chat/threads/{thread}/members/{user}', RemoveChatThreadMemberController::class)->name('chat.threads.members.delete');
    Route::get('/chat/threads/{thread}/messages', ListChatMessagesController::class)->name('chat.threads.messages.index');
    Route::post('/chat/threads/{thread}/messages', StoreChatMessageController::class)->name('chat.threads.messages.store');
    Route::post('/chat/messages/{message}/reactions', StoreChatMessageReactionController::class)->name('chat.messages.reactions.store');
    Route::delete('/chat/messages/{message}/reactions', DeleteChatMessageReactionController::class)->name('chat.messages.reactions.delete');
    Route::post('/chat/threads/{thread}/read', MarkChatThreadReadController::class)->name('chat.threads.read');
});
