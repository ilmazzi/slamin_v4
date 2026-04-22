<?php

namespace App\Http\Controllers\Api\V1\PoetrySlam;

use App\Http\Controllers\Controller;
use App\Support\Responses\ApiResponse;
use Domain\Event\Models\Event;
use Domain\PoetrySlam\Actions\DeletePoetrySlamEntryAction;
use Domain\PoetrySlam\Models\PoetrySlamEntry;
use Illuminate\Http\JsonResponse;

class DeletePoetrySlamEntryController extends Controller
{
    public function __construct(
        private readonly DeletePoetrySlamEntryAction $action,
    ) {
    }

    public function __invoke(Event $event, PoetrySlamEntry $entry): JsonResponse
    {
        $this->authorize('update', $event);

        $event->loadMissing('poetrySlam');
        $entry->loadMissing('poetrySlam');

        if ($event->poetrySlam === null || $entry->poetry_slam_id !== $event->poetrySlam->id) {
            return ApiResponse::error(
                message: __('poetry_slam.entry_not_found'),
                status: 404,
            );
        }

        $this->action->execute($entry, request()->user()->id);

        return ApiResponse::success([
            'deleted' => true,
        ]);
    }
}
