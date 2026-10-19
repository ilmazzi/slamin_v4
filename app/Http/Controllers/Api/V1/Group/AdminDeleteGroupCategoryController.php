<?php

namespace App\Http\Controllers\Api\V1\Group;

use App\Http\Controllers\Controller;
use App\Support\Responses\ApiResponse;
use Domain\Group\Actions\AdminDeleteGroupCategoryAction;
use Domain\Group\Models\GroupCategory;
use Illuminate\Http\JsonResponse;

class AdminDeleteGroupCategoryController extends Controller
{
    public function __construct(
        private readonly AdminDeleteGroupCategoryAction $action,
    ) {}

    public function __invoke(GroupCategory $groupCategory): JsonResponse
    {
        $deleted = $this->action->execute($groupCategory, request()->user()->id);

        if (! $deleted) {
            return ApiResponse::error(
                __('group.errors.category_in_use'),
                status: 422,
            );
        }

        return ApiResponse::success([
            'deleted' => true,
        ]);
    }
}
