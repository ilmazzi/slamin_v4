<?php

namespace App\Http\Controllers\Api\V1\Chat;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Chat\ChatThreadResource;
use App\Support\Responses\ApiResponse;
use Domain\Chat\Actions\ListChatThreadsAction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ListChatThreadsController extends Controller
{
    public function __construct(
        private readonly ListChatThreadsAction $action,
    ) {
    }

    public function __invoke(Request $request): JsonResponse
    {
        $threads = $this->action->execute($request->user()->id);

        return ApiResponse::success([
            'threads' => ChatThreadResource::collection($threads),
        ]);
    }
}
