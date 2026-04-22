<?php

namespace App\Http\Controllers\Api\V1\Media;

use App\Http\Controllers\Controller;
use App\Support\Responses\ApiResponse;
use Domain\Media\Actions\CreateStripeCheckoutSessionAction;
use Domain\Media\Models\MediaCheckoutOrder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CreateStripeCheckoutSessionController extends Controller
{
    public function __construct(
        private readonly CreateStripeCheckoutSessionAction $action,
    ) {
    }

    public function __invoke(Request $request, MediaCheckoutOrder $order): JsonResponse
    {
        abort_unless($order->user_id === $request->user()->id, 404);

        $session = $this->action->execute($order->loadMissing('featurePack', 'user'));

        return ApiResponse::success([
            'checkout_session_id' => $session['checkout_session_id'],
            'checkout_url' => $session['checkout_url'],
        ], status: 201);
    }
}
