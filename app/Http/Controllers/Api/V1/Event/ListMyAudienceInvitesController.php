<?php

namespace App\Http\Controllers\Api\V1\Event;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Event\EventAudienceInviteResource;
use App\Support\Responses\ApiResponse;
use Domain\Event\Actions\ListMyAudienceInvitesAction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ListMyAudienceInvitesController extends Controller
{
    public function __construct(
        private readonly ListMyAudienceInvitesAction $action,
    ) {
    }

    public function __invoke(Request $request): JsonResponse
    {
        return ApiResponse::success([
            'invites' => EventAudienceInviteResource::collection(
                $this->action->execute($request->user()->id)
            ),
        ]);
    }
}
