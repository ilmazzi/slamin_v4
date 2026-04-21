<?php

namespace App\Http\Controllers\Api\V1\Event;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Event\RespondToEventInviteRequest;
use App\Http\Resources\Api\V1\Event\EventParticipantInviteResource;
use App\Support\Responses\ApiResponse;
use Domain\Event\Actions\RespondToParticipantInviteAction;
use Domain\Event\Models\EventParticipantInvite;
use Illuminate\Http\JsonResponse;

class RespondToParticipantInviteController extends Controller
{
    public function __construct(
        private readonly RespondToParticipantInviteAction $action,
    ) {
    }

    public function __invoke(RespondToEventInviteRequest $request, EventParticipantInvite $invite): JsonResponse
    {
        abort_unless($invite->user_id === $request->user()->id, 403);

        $invite = $this->action->execute(
            invite: $invite,
            userId: $request->user()->id,
            status: $request->validated('status'),
        );

        return ApiResponse::success([
            'invite' => new EventParticipantInviteResource($invite),
        ]);
    }
}
