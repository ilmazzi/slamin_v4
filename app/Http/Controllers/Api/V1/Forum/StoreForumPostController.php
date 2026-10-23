<?php

namespace App\Http\Controllers\Api\V1\Forum;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Forum\StoreForumPostRequest;
use App\Http\Resources\Api\V1\Forum\ForumPostResource;
use App\Support\Responses\ApiResponse;
use Domain\Forum\Actions\StoreForumPostAction;
use Illuminate\Http\JsonResponse;

class StoreForumPostController extends Controller
{
    public function __construct(
        private readonly StoreForumPostAction $action,
    ) {}

    public function __invoke(StoreForumPostRequest $request): JsonResponse
    {
        $this->authorize('create', \Domain\Forum\Models\ForumPost::class);

        $post = $this->action->execute($request->validated(), $request->user()->id);

        return ApiResponse::success([
            'post' => new ForumPostResource($post),
        ], status: 201);
    }
}
