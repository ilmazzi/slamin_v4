<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Support\Responses\ApiResponse;
use Domain\Auth\Actions\SendEmailVerificationAction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SendEmailVerificationController extends Controller
{
    public function __invoke(
        Request $request,
        SendEmailVerificationAction $action
    ): JsonResponse {
        $action->execute($request->user());

        return ApiResponse::success([
            'message' => __('auth.verification_link_sent'),
        ]);
    }
}
