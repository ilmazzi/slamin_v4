<?php

namespace Domain\Media\Actions;

use Domain\Media\Enums\MediaCheckoutOrderStatus;
use Domain\Media\Models\MediaCheckoutOrder;
use Domain\Media\Models\UserMediaEntitlement;
use Illuminate\Support\Facades\DB;

class CompleteMediaCheckoutOrderAction
{
    public function execute(MediaCheckoutOrder $order, array $providerData = []): MediaCheckoutOrder
    {
        return DB::transaction(function () use ($order, $providerData) {
            $order->refresh();

            if ($order->status === MediaCheckoutOrderStatus::Paid) {
                return $order->load(['featurePack']);
            }

            $order->status = MediaCheckoutOrderStatus::Paid;
            $order->provider = 'stripe';
            $order->provider_checkout_session_id = $providerData['checkout_session_id'] ?? $order->provider_checkout_session_id;
            $order->provider_payment_intent_id = $providerData['payment_intent_id'] ?? $order->provider_payment_intent_id;
            $order->completed_at = now();
            $order->payload = array_merge($order->payload ?? [], [
                'stripe_webhook_event_id' => $providerData['event_id'] ?? null,
            ]);
            $order->save();

            UserMediaEntitlement::query()->create([
                'user_id' => $order->user_id,
                'media_feature_pack_id' => $order->media_feature_pack_id,
                'granted_by_user_id' => null,
                'source' => 'stripe_checkout',
                'config' => array_merge($order->feature_config ?? [], [
                    'checkout_order_id' => $order->id,
                ]),
                'starts_at' => now(),
            ]);

            audit()->log(
                event: 'media.checkout_order_completed',
                userId: $order->user_id,
                auditable: $order,
                newValues: [
                    'status' => $order->status?->value,
                    'provider_checkout_session_id' => $order->provider_checkout_session_id,
                    'provider_payment_intent_id' => $order->provider_payment_intent_id,
                ],
                metadata: [
                    'source' => 'media_checkout_order_complete',
                ],
            );

            return $order->load(['featurePack']);
        });
    }
}
