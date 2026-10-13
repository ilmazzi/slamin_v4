<?php

namespace App\Http\Controllers\Api\V1\Media;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Media\MediaSnapResource;
use App\Support\Responses\ApiResponse;
use Domain\Media\Actions\ListMediaSnapsAction;
use Domain\Media\Models\MediaItem;
use Domain\Media\Models\MediaSnap;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ListMediaSnapsController extends Controller
{
    public function __construct(
        private readonly ListMediaSnapsAction $action,
    ) {}

    public function __invoke(Request $request, MediaItem $media): JsonResponse
    {
        $this->authorizeForUser(
            auth('sanctum')->user() ?? $request->user(),
            'view',
            [MediaSnap::class, $media],
        );

        return ApiResponse::success(
            data: MediaSnapResource::collection($this->action->execute($media))
        );
    }
}
