<?php

namespace App\Http\Controllers\Api\V1\Event;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Event\EventParticipantInviteResource;
use App\Support\Responses\ApiResponse;
use Domain\Event\Actions\ListMyParticipantInvitesAction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ListMyParticipantInvitesController extends Controller
{
    public function __construct(
        private readonly ListMyParticipantInvitesAction $action,
    ) {
    }

    public function __invoke(Request $request): JsonResponse
    {
        return ApiResponse::success([
            'invites' => EventParticipantInviteResource::collection(
                $this->action->execute($request->user()->id)
            ),
        ]);
    }
}
