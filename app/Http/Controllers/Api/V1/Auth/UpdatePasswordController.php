<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Auth\UpdatePasswordRequest;
use App\Support\Responses\ApiResponse;
use Domain\Auth\Actions\UpdatePasswordAction;
use Domain\Auth\DTOs\UpdatePasswordData;
use Illuminate\Http\JsonResponse;

class UpdatePasswordController extends Controller
{
    public function __invoke(
        UpdatePasswordRequest $request,
        UpdatePasswordAction $action,
    ): JsonResponse {
        $user = $request->user();

        $action->execute(
            $user,
            UpdatePasswordData::fromRequest($request),
            $user->currentAccessToken()?->id,
        );

        return ApiResponse::success([
            'message' => __('auth.password_updated'),
        ]);
    }
}
