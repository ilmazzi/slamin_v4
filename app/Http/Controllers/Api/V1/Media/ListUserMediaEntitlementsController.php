<?php

namespace App\Http\Controllers\Api\V1\Media;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Media\UserMediaEntitlementResource;
use App\Support\Responses\ApiResponse;
use Domain\Media\Actions\ListUserMediaEntitlementsAction;
use Domain\User\Models\User;
use Illuminate\Http\JsonResponse;

class ListUserMediaEntitlementsController extends Controller
{
    public function __construct(
        private readonly ListUserMediaEntitlementsAction $action,
    ) {
    }

    public function __invoke(User $user): JsonResponse
    {
        return ApiResponse::success(
            data: UserMediaEntitlementResource::collection($this->action->execute($user))
        );
    }
}
