<?php

namespace App\Http\Controllers\Api\V1\Event;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Event\EventParticipantInviteResource;
use App\Support\Responses\ApiResponse;
use Domain\Event\Queries\ListMyParticipantInvitesQuery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ListMyParticipantInvitesController extends Controller
{
    public function __construct(
        private readonly ListMyParticipantInvitesQuery $listMyParticipantInvitesQuery,
    ) {
    }

    public function __invoke(Request $request): JsonResponse
    {
        return ApiResponse::success([
            'invites' => EventParticipantInviteResource::collection(
                $this->listMyParticipantInvitesQuery->execute($request->user()->id)
            ),
        ]);
    }
}
