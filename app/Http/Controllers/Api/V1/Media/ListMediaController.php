<?php

namespace App\Http\Controllers\Api\V1\Media;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Media\ListMediaRequest;
use App\Http\Resources\Api\V1\Media\MediaItemResource;
use App\Support\Responses\ApiResponse;
use Domain\Media\Actions\ListMediaAction;
use Illuminate\Http\JsonResponse;

class ListMediaController extends Controller
{
    public function __construct(
        private readonly ListMediaAction $action,
    ) {
    }

    public function __invoke(ListMediaRequest $request): JsonResponse
    {
        $media = $this->action->execute($request->user()?->id, (int) $request->validated('per_page', 15));

        return ApiResponse::success(
            data: MediaItemResource::collection($media->items()),
            meta: [
                'current_page' => $media->currentPage(),
                'last_page' => $media->lastPage(),
                'per_page' => $media->perPage(),
                'total' => $media->total(),
            ],
        );
    }
}
