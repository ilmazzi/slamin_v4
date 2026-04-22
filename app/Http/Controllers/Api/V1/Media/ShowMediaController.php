<?php

namespace App\Http\Controllers\Api\V1\Media;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Media\MediaItemResource;
use App\Support\Responses\ApiResponse;
use Domain\Media\Actions\ShowMediaAction;
use Domain\Media\Models\MediaItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ShowMediaController extends Controller
{
    public function __construct(
        private readonly ShowMediaAction $action,
    ) {
    }

    public function __invoke(Request $request, MediaItem $media): JsonResponse
    {
        $this->authorize('view', $media);

        $media = $this->action->execute($media, $request->user()?->id);

        return ApiResponse::success([
            'media' => new MediaItemResource($media),
        ]);
    }
}
