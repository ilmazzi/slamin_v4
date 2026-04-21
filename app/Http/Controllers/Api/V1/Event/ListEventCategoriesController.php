<?php

namespace App\Http\Controllers\Api\V1\Event;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Event\EventCategoryResource;
use App\Support\Responses\ApiResponse;
use Domain\Event\Actions\ListEventCategoriesAction;
use Illuminate\Http\JsonResponse;

class ListEventCategoriesController extends Controller
{
    public function __construct(
        private readonly ListEventCategoriesAction $action,
    ) {
    }

    public function __invoke(): JsonResponse
    {
        return ApiResponse::success([
            'categories' => EventCategoryResource::collection($this->action->execute()),
        ]);
    }
}
