<?php

namespace App\Http\Controllers\Api\V1\Newsletter;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Newsletter\NewsletterTokenRequest;
use App\Support\Responses\ApiResponse;
use Domain\Newsletter\Actions\UnsubscribeFromNewsletterAction;
use Illuminate\Http\JsonResponse;

class UnsubscribeNewsletterController extends Controller
{
    public function __invoke(
        NewsletterTokenRequest $request,
        UnsubscribeFromNewsletterAction $action,
    ): JsonResponse {
        if (! $action->execute($request->string('token')->toString())) {
            return ApiResponse::error((string) __('newsletter.unsubscribe_error'), status: 422);
        }

        return ApiResponse::success();
    }
}
