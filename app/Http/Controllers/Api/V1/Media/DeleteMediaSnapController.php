<?php

namespace App\Http\Controllers\Api\V1\Media;

use App\Http\Controllers\Controller;
use App\Support\Responses\ApiResponse;
use Domain\Media\Actions\DeleteMediaSnapAction;
use Domain\Media\Models\MediaItem;
use Domain\Media\Models\MediaSnap;
use Illuminate\Http\JsonResponse;

class DeleteMediaSnapController extends Controller
{
    public function __construct(
        private readonly DeleteMediaSnapAction $action,
    ) {
    }

    public function __invoke(MediaItem $media, MediaSnap $snap): JsonResponse
    {
        abort_unless($snap->media_item_id === $media->id, 404);
        $this->authorize('delete', $snap);

        $this->action->execute($snap);

        return ApiResponse::success([
            'deleted' => true,
        ]);
    }
}
