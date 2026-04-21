<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Auth\VerifyEmailRequest;
use App\Support\Responses\ApiResponse;
use Domain\Auth\Actions\VerifyEmailAction;
use Domain\Auth\DTOs\VerifyEmailData;
use Illuminate\Http\JsonResponse;

class VerifyEmailController extends Controller
{
    public function __invoke(
        VerifyEmailRequest $request,
        VerifyEmailAction $action
    ): JsonResponse {
        $data = VerifyEmailData::fromRequest($request);

        $action->execute($data);

        return ApiResponse::success([
            'message' => __('auth.email_verified'),
        ]);
    }
}
