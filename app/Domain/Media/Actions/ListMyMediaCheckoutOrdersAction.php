<?php

namespace Domain\Media\Actions;

use Domain\Media\Models\MediaCheckoutOrder;
use Illuminate\Database\Eloquent\Collection;

class ListMyMediaCheckoutOrdersAction
{
    public function execute(int $userId): Collection
    {
        return MediaCheckoutOrder::query()
            ->with('featurePack')
            ->where('user_id', $userId)
            ->latest('id')
            ->get();
    }
}
