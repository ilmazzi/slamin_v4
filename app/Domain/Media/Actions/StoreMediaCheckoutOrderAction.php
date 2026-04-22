<?php

namespace Domain\Media\Actions;

use Domain\Media\DTOs\StoreMediaCheckoutOrderData;
use Domain\Media\Enums\MediaCheckoutOrderStatus;
use Domain\Media\Models\MediaCheckoutOrder;
use Domain\Media\Models\MediaFeaturePack;
use Illuminate\Validation\ValidationException;

class StoreMediaCheckoutOrderAction
{
    public function execute(int $userId, StoreMediaCheckoutOrderData $data): MediaCheckoutOrder
    {
        $featurePack = MediaFeaturePack::query()->findOrFail($data->featurePackId);

        if (! $featurePack->is_active) {
            throw ValidationException::withMessages([
                'media_feature_pack_id' => [__('media.validation.feature_pack_not_available')],
            ]);
        }

        $order = MediaCheckoutOrder::query()->create([
            'user_id' => $userId,
            'media_feature_pack_id' => $featurePack->id,
            'status' => MediaCheckoutOrderStatus::Pending,
            'provider' => $data->provider,
            'amount' => (int) ($featurePack->price_amount ?? 0),
            'currency' => $featurePack->currency ?? 'EUR',
            'feature_code' => $featurePack->code,
            'feature_name' => $featurePack->name,
            'feature_config' => $featurePack->config ?? [],
            'payload' => $data->payload,
        ]);

        audit()->log(
            event: 'media.checkout_order_created',
            userId: $userId,
            auditable: $order,
            newValues: [
                'user_id' => $order->user_id,
                'media_feature_pack_id' => $order->media_feature_pack_id,
                'status' => $order->status?->value,
                'amount' => $order->amount,
                'currency' => $order->currency,
            ],
            metadata: [
                'source' => 'media_checkout_order_store',
            ],
        );

        return $order->load('featurePack');
    }
}
