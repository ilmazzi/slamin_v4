<?php

namespace App\Http\Controllers\Api\V1\Newsletter;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Newsletter\SubscribeNewsletterRequest;
use App\Support\Responses\ApiResponse;
use Domain\Newsletter\Actions\SubscribeToNewsletterAction;
use Illuminate\Http\JsonResponse;

class SubscribeNewsletterController extends Controller
{
    public function __invoke(
        SubscribeNewsletterRequest $request,
        SubscribeToNewsletterAction $action,
    ): JsonResponse {
        $data = $request->validated();
        $action->execute($data['email'], $data['locale'] ?? app()->getLocale());

        return ApiResponse::success();
    }
}
