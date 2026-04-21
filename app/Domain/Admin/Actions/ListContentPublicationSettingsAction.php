<?php

namespace Domain\Admin\Actions;

use Domain\Admin\Models\ContentPublicationSetting;
use Illuminate\Support\Collection;

class ListContentPublicationSettingsAction
{
    public function execute(): Collection
    {
        return ContentPublicationSetting::query()
            ->orderBy('content_type')
            ->get();
    }
}
