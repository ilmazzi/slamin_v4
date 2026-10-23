<?php

namespace App\Http\Controllers\Api\V1\Forum;

use App\Http\Controllers\Controller;
use App\Support\Responses\ApiResponse;
use Domain\Forum\Actions\AdminDeleteForumCategoryAction;
use Domain\Forum\Models\ForumCategory;
use Illuminate\Http\JsonResponse;

class AdminDeleteForumCategoryController extends Controller
{
    public function __construct(
        private readonly AdminDeleteForumCategoryAction $action,
    ) {}

    public function __invoke(ForumCategory $forumCategory): JsonResponse
    {
        $deleted = $this->action->execute($forumCategory, request()->user()->id);

        if (! $deleted) {
            return ApiResponse::error(
                __('forum.errors.category_in_use'),
                status: 422,
            );
        }

        return ApiResponse::success([
            'deleted' => true,
        ]);
    }
}
