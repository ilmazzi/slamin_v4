<?php

namespace Domain\Media\Actions;

use Domain\Media\DTOs\StoreMediaFeaturePackData;
use Domain\Media\Models\MediaFeaturePack;

class StoreMediaFeaturePackAction
{
    public function execute(StoreMediaFeaturePackData $data): MediaFeaturePack
    {
        return MediaFeaturePack::query()->create([
            'code' => $data->code,
            'name' => $data->name,
            'description' => $data->description,
            'config' => $data->config,
            'is_active' => $data->isActive,
            'price_amount' => $data->priceAmount,
            'currency' => $data->currency,
        ]);
    }
}
