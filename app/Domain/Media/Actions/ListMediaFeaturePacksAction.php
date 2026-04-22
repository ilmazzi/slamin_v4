<?php

namespace Domain\Media\Actions;

use Domain\Media\Models\MediaFeaturePack;
use Illuminate\Database\Eloquent\Collection;

class ListMediaFeaturePacksAction
{
    public function execute(): Collection
    {
        return MediaFeaturePack::query()
            ->latest('id')
            ->get();
    }
}
