<?php

namespace Domain\Media\Actions;

use Domain\Media\Enums\MediaCheckoutOrderStatus;
use Domain\Media\Models\MediaCheckoutOrder;

class MarkMediaCheckoutOrderCancelledAction
{
    public function execute(MediaCheckoutOrder $order, string $status): MediaCheckoutOrder
    {
        if ($order->status === MediaCheckoutOrderStatus::Paid) {
            return $order;
        }

        $order->status = MediaCheckoutOrderStatus::from($status);
        $order->cancelled_at = now();
        $order->save();

        return $order;
    }
}
