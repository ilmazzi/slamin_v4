<?php

namespace App\Http\Controllers\Api\V1\Media;

use App\Http\Controllers\Controller;
use App\Support\Responses\ApiResponse;
use Domain\Media\Actions\DeleteMediaItemAction;
use Domain\Media\Models\MediaItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DeleteMediaItemController extends Controller
{
    public function __construct(
        private readonly DeleteMediaItemAction $action,
    ) {
    }

    public function __invoke(Request $request, MediaItem $media): JsonResponse
    {
        $this->authorize('delete', $media);

        $this->action->execute($media, $request->user()->id);

        return ApiResponse::success([
            'deleted' => true,
        ]);
    }
}
