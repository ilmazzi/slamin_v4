<?php

namespace Domain\Media\Actions;

use Domain\Media\DTOs\UpdateMediaFeaturePackData;
use Domain\Media\Models\MediaFeaturePack;

class UpdateMediaFeaturePackAction
{
    public function execute(MediaFeaturePack $featurePack, UpdateMediaFeaturePackData $data): MediaFeaturePack
    {
        if ($data->name !== null) {
            $featurePack->name = $data->name;
        }

        if ($data->description !== null) {
            $featurePack->description = $data->description;
        }

        if ($data->config !== null) {
            $featurePack->config = $data->config;
        }

        if ($data->isActive !== null) {
            $featurePack->is_active = $data->isActive;
        }

        if ($data->priceAmount !== null) {
            $featurePack->price_amount = $data->priceAmount;
        }

        if ($data->currency !== null) {
            $featurePack->currency = $data->currency;
        }

        $featurePack->save();

        return $featurePack->fresh();
    }
}
