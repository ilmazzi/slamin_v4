<?php

namespace App\Http\Controllers\Api\V1\Media;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Media\MediaItemResource;
use App\Support\Responses\ApiResponse;
use Domain\Media\Actions\SyncPeerTubeVideoStatusAction;
use Domain\Media\Models\MediaItem;
use Illuminate\Http\JsonResponse;

class SyncPeerTubeVideoStatusController extends Controller
{
    public function __construct(
        private readonly SyncPeerTubeVideoStatusAction $action,
    ) {}

    public function __invoke(MediaItem $media): JsonResponse
    {
        $this->authorizeForUser(auth('sanctum')->user(), 'view', $media);

        $media = $this->action->execute($media);

        return ApiResponse::success([
            'media' => new MediaItemResource($media),
        ]);
    }
}
