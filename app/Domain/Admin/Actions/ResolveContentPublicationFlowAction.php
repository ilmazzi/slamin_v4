<?php

namespace Domain\Admin\Actions;

use Domain\Admin\Models\ContentPublicationSetting;
use Domain\Shared\Enums\ContentType;
use Domain\Shared\Enums\PublicationFlow;

class ResolveContentPublicationFlowAction
{
    public function execute(ContentType $contentType): PublicationFlow
    {
        $setting = ContentPublicationSetting::query()
            ->where('content_type', $contentType->value)
            ->where('is_active', true)
            ->first();

        return $setting?->publication_flow ?? PublicationFlow::AutoPublish;
    }
}
