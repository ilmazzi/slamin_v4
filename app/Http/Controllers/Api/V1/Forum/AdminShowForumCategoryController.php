<?php

namespace App\Http\Controllers\Api\V1\Forum;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Forum\AdminForumCategoryResource;
use App\Support\Responses\ApiResponse;
use Domain\Forum\Models\ForumCategory;
use Illuminate\Http\JsonResponse;

class AdminShowForumCategoryController extends Controller
{
    public function __invoke(ForumCategory $forumCategory): JsonResponse
    {
        return ApiResponse::success([
            'category' => new AdminForumCategoryResource($forumCategory->load('translations')->loadCount('posts')),
        ]);
    }
}
