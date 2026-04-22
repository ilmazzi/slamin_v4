<?php

namespace App\Http\Controllers\Api\V1\Media;

use App\Http\Controllers\Controller;
use App\Support\Responses\ApiResponse;
use Domain\Media\Actions\HandleStripeWebhookAction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HandleStripeWebhookController extends Controller
{
    public function __construct(
        private readonly HandleStripeWebhookAction $action,
    ) {
    }

    public function __invoke(Request $request): JsonResponse
    {
        $payload = $request->getContent();
        $signature = $request->header('Stripe-Signature');

        if (! $this->action->verifySignature($payload, $signature)) {
            return ApiResponse::error('Invalid Stripe signature.', status: 400);
        }

        $event = $this->action->parsePayload($payload);
        $this->action->execute($event);

        return ApiResponse::success([
            'received' => true,
        ]);
    }
}
