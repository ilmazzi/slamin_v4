<?php

namespace Domain\Media\Actions;

use Domain\Media\Models\MediaCheckoutOrder;
use Domain\Media\Services\StripeService;
use RuntimeException;

class HandleStripeWebhookAction
{
    public function __construct(
        private readonly StripeService $stripeService,
        private readonly CompleteMediaCheckoutOrderAction $completeAction,
        private readonly MarkMediaCheckoutOrderCancelledAction $markCancelledAction,
    ) {
    }

    /**
     * @param array<string,mixed> $event
     */
    public function execute(array $event): void
    {
        $type = (string) ($event['type'] ?? '');
        $object = $event['data']['object'] ?? null;

        if (! is_array($object)) {
            return;
        }

        $orderId = $object['metadata']['media_checkout_order_id'] ?? $object['client_reference_id'] ?? null;
        if (! $orderId) {
            return;
        }

        /** @var MediaCheckoutOrder|null $order */
        $order = MediaCheckoutOrder::query()->find($orderId);
        if (! $order) {
            return;
        }

        if ($type === 'checkout.session.completed' && ($object['payment_status'] ?? null) === 'paid') {
            $this->completeAction->execute($order, [
                'event_id' => $event['id'] ?? null,
                'checkout_session_id' => $object['id'] ?? null,
                'payment_intent_id' => $object['payment_intent'] ?? null,
            ]);
            return;
        }

        if ($type === 'checkout.session.expired') {
            $this->markCancelledAction->execute($order, 'expired');
            return;
        }

        if ($type === 'checkout.session.async_payment_failed') {
            $this->markCancelledAction->execute($order, 'failed');
        }
    }

    public function verifySignature(string $payload, ?string $signatureHeader): bool
    {
        return $this->stripeService->verifyWebhookSignature($payload, $signatureHeader);
    }

    /**
     * @return array<string,mixed>
     */
    public function parsePayload(string $payload): array
    {
        $decoded = json_decode($payload, true);

        if (! is_array($decoded)) {
            throw new RuntimeException('Invalid Stripe payload.');
        }

        return $decoded;
    }
}
