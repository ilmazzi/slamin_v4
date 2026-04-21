<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Auth\ResetPasswordRequest;
use App\Support\Responses\ApiResponse;
use Domain\Auth\Actions\ResetPasswordAction;
use Domain\Auth\DTOs\ResetPasswordData;
use Illuminate\Http\JsonResponse;

class ResetPasswordController extends Controller
{
    public function __invoke(
        ResetPasswordRequest $request,
        ResetPasswordAction $action
    ): JsonResponse {
        $data = ResetPasswordData::fromRequest($request);

        $action->execute($data);

        return ApiResponse::success([
            'message' => __('auth.password_reset_success'),
        ]);
    }
}
