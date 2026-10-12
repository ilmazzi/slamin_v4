<?php

namespace App\Http\Controllers\Api\V1\Help;

use App\Http\Controllers\Controller;
use App\Support\Responses\ApiResponse;
use Domain\Help\Actions\AdminDeleteHelpCategoryAction;
use Domain\Help\Models\HelpCategory;
use Illuminate\Http\JsonResponse;

class AdminDeleteHelpCategoryController extends Controller
{
    public function __construct(
        private readonly AdminDeleteHelpCategoryAction $action,
    ) {}

    public function __invoke(HelpCategory $helpCategory): JsonResponse
    {
        if (! $this->action->execute($helpCategory, request()->user()->id)) {
            return ApiResponse::error(
                message: __('help.errors.category_in_use'),
                status: 422,
            );
        }

        return ApiResponse::success();
    }
}
