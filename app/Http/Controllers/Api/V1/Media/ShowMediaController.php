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
    ) {}

    public function __invoke(Request $request, MediaItem $media): JsonResponse
    {
        $user = auth('sanctum')->user() ?? $request->user();

        $this->authorizeForUser($user, 'view', $media);

        $authUserId = $user?->id;

        $media = $this->action->execute($media, $authUserId);

        if ($media->isPubliclyVisible() && $media->user_id !== $authUserId) {
            $media->increment('views_count');
        }

        return ApiResponse::success([
            'media' => new MediaItemResource($media),
        ]);
    }
}
