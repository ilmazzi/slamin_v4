<?php

namespace App\Http\Controllers\Api\V1\Poetry;

use App\Http\Controllers\Controller;
use App\Support\Responses\ApiResponse;
use Domain\Poetry\Actions\AdminDeletePoemCategoryAction;
use Domain\Poetry\Models\PoemCategory;
use Illuminate\Http\JsonResponse;

class AdminDeletePoemCategoryController extends Controller
{
    public function __construct(
        private readonly AdminDeletePoemCategoryAction $action,
    ) {
    }

    public function __invoke(PoemCategory $poemCategory): JsonResponse
    {
        if (! $this->action->execute($poemCategory, request()->user()->id)) {
            return ApiResponse::error(
                message: 'Cannot delete a category that is already used by one or more poems.',
                status: 422
            );
        }

        return ApiResponse::success();
    }
}
