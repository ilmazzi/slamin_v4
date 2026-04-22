<?php

namespace App\Http\Controllers\Api\V1\Media;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Media\GrantUserMediaEntitlementRequest;
use App\Http\Resources\Api\V1\Media\UserMediaEntitlementResource;
use App\Support\Responses\ApiResponse;
use Domain\Media\Actions\GrantUserMediaEntitlementAction;
use Domain\Media\DTOs\GrantUserMediaEntitlementData;
use Domain\User\Models\User;
use Illuminate\Http\JsonResponse;

class GrantUserMediaEntitlementController extends Controller
{
    public function __construct(
        private readonly GrantUserMediaEntitlementAction $action,
    ) {
    }

    public function __invoke(GrantUserMediaEntitlementRequest $request, User $user): JsonResponse
    {
        $entitlement = $this->action->execute(
            $user,
            $request->user()->id,
            GrantUserMediaEntitlementData::fromRequest($request),
        );

        return ApiResponse::success([
            'entitlement' => new UserMediaEntitlementResource($entitlement),
        ], status: 201);
    }
}
