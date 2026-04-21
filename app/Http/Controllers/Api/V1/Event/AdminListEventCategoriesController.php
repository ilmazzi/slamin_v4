<?php

namespace App\Http\Controllers\Api\V1\Event;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Event\AdminEventCategoryResource;
use App\Support\Responses\ApiResponse;
use Domain\Event\Actions\AdminListEventCategoriesAction;
use Illuminate\Http\JsonResponse;

class AdminListEventCategoriesController extends Controller
{
    public function __construct(
        private readonly AdminListEventCategoriesAction $action,
    ) {
    }

    public function __invoke(): JsonResponse
    {
        return ApiResponse::success([
            'categories' => AdminEventCategoryResource::collection($this->action->execute()),
        ]);
    }
}
