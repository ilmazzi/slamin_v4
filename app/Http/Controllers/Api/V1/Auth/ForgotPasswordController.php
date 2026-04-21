<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Auth\ForgotPasswordRequest;
use App\Support\Responses\ApiResponse;
use Domain\Auth\Actions\SendPasswordResetLinkAction;
use Domain\Auth\DTOs\SendPasswordResetData;
use Illuminate\Http\JsonResponse;

class ForgotPasswordController extends Controller
{
    public function __invoke(
        ForgotPasswordRequest $request,
        SendPasswordResetLinkAction $action
    ): JsonResponse {
        $data = SendPasswordResetData::fromRequest($request);

        $action->execute($data);

        return ApiResponse::success([
            'message' => __('auth.forgot_password_sent'),
        ]);
    }
}
