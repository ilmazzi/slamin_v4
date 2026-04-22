<?php

namespace App\Http\Controllers\Api\V1\Media;

use App\Http\Controllers\Controller;
use App\Support\Responses\ApiResponse;
use Domain\Media\Actions\RevokeUserMediaEntitlementAction;
use Domain\Media\Models\UserMediaEntitlement;
use Domain\User\Models\User;
use Illuminate\Http\JsonResponse;

class RevokeUserMediaEntitlementController extends Controller
{
    public function __construct(
        private readonly RevokeUserMediaEntitlementAction $action,
    ) {
    }

    public function __invoke(User $user, UserMediaEntitlement $entitlement): JsonResponse
    {
        abort_unless($entitlement->user_id === $user->id, 404);

        $this->action->execute($entitlement, request()->user()->id);

        return ApiResponse::success([
            'deleted' => true,
        ]);
    }
}
