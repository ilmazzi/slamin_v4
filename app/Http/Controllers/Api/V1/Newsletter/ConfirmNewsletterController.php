<?php

namespace App\Http\Controllers\Api\V1\Newsletter;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Newsletter\NewsletterTokenRequest;
use App\Support\Responses\ApiResponse;
use Domain\Newsletter\Actions\ConfirmNewsletterSubscriptionAction;
use Illuminate\Http\JsonResponse;

class ConfirmNewsletterController extends Controller
{
    public function __invoke(
        NewsletterTokenRequest $request,
        ConfirmNewsletterSubscriptionAction $action,
    ): JsonResponse {
        if (! $action->execute($request->string('token')->toString())) {
            return ApiResponse::error((string) __('newsletter.confirm_error'), status: 422);
        }

        return ApiResponse::success();
    }
}
