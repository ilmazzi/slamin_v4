<?php

namespace Domain\Media\Actions;

use Domain\Media\Enums\MediaCheckoutOrderStatus;
use Domain\Media\Models\MediaCheckoutOrder;
use Domain\Media\Services\StripeService;
use Illuminate\Validation\ValidationException;

class CreateStripeCheckoutSessionAction
{
    public function __construct(
        private readonly StripeService $stripeService,
    ) {
    }

    /**
     * @return array{checkout_session_id:string|null,checkout_url:string|null}
     */
    public function execute(MediaCheckoutOrder $order): array
    {
        if ($order->status !== MediaCheckoutOrderStatus::Pending) {
            throw ValidationException::withMessages([
                'order' => [__('media.validation.checkout_order_not_pending')],
            ]);
        }

        $response = $this->stripeService->createCheckoutSession($order->loadMissing('featurePack', 'user'));

        $order->provider = 'stripe';
        $order->provider_checkout_session_id = $response['id'] ?? null;
        $order->payload = array_merge($order->payload ?? [], [
            'stripe_checkout_url' => $response['url'] ?? null,
        ]);
        $order->save();

        return [
            'checkout_session_id' => $response['id'] ?? null,
            'checkout_url' => $response['url'] ?? null,
        ];
    }
}
