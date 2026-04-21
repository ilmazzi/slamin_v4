<?php

namespace App\Http\Controllers\Api\V1\Event;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Event\AdminEventCategoryResource;
use App\Support\Responses\ApiResponse;
use Domain\Event\Models\EventCategory;
use Illuminate\Http\JsonResponse;

class AdminShowEventCategoryController extends Controller
{
    public function __invoke(EventCategory $eventCategory): JsonResponse
    {
        return ApiResponse::success([
            'category' => new AdminEventCategoryResource($eventCategory->load('translations')),
        ]);
    }
}
