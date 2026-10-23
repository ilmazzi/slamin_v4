<?php

namespace App\Http\Controllers\Api\V1\Forum;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Forum\UpdateForumCategoryRequest;
use App\Http\Resources\Api\V1\Forum\AdminForumCategoryResource;
use App\Support\Responses\ApiResponse;
use Domain\Forum\Actions\AdminUpdateForumCategoryAction;
use Domain\Forum\Models\ForumCategory;
use Illuminate\Http\JsonResponse;

class AdminUpdateForumCategoryController extends Controller
{
    public function __construct(
        private readonly AdminUpdateForumCategoryAction $action,
    ) {}

    public function __invoke(UpdateForumCategoryRequest $request, ForumCategory $forumCategory): JsonResponse
    {
        $category = $this->action->execute($forumCategory, $request->validated(), $request->user()->id);

        return ApiResponse::success([
            'category' => new AdminForumCategoryResource($category->loadCount('posts')),
        ]);
    }
}
