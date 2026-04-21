<?php

namespace App\Http\Controllers\Api\V1\Event;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Event\RespondToEventInviteRequest;
use App\Http\Resources\Api\V1\Event\EventAudienceInviteResource;
use App\Support\Responses\ApiResponse;
use Domain\Event\Actions\RespondToAudienceInviteAction;
use Domain\Event\Models\EventAudienceInvite;
use Illuminate\Http\JsonResponse;

class RespondToAudienceInviteController extends Controller
{
    public function __construct(
        private readonly RespondToAudienceInviteAction $action,
    ) {
    }

    public function __invoke(RespondToEventInviteRequest $request, EventAudienceInvite $invite): JsonResponse
    {
        abort_unless($invite->user_id === $request->user()->id, 403);

        $invite = $this->action->execute(
            invite: $invite,
            userId: $request->user()->id,
            status: $request->validated('status'),
        );

        return ApiResponse::success([
            'invite' => new EventAudienceInviteResource($invite),
        ]);
    }
}
