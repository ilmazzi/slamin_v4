<?php

namespace App\Http\Controllers\Api\V1\Event;

use App\Http\Controllers\Controller;
use App\Support\Responses\ApiResponse;
use Domain\Event\Actions\DeleteEventAction;
use Domain\Event\Models\Event;
use Illuminate\Http\JsonResponse;

class DeleteEventController extends Controller
{
    public function __construct(
        private readonly DeleteEventAction $action,
    ) {
    }

    public function __invoke(Event $event): JsonResponse
    {
        $this->authorize('delete', $event);
        $this->action->execute($event, request()->user()->id);

        return ApiResponse::success([
            'deleted' => true,
        ]);
    }
}
