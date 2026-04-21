<?php

namespace Domain\Admin\Actions;

use Domain\Admin\Models\ContentPublicationSetting;

class UpdateContentPublicationSettingAction
{
    public function execute(ContentPublicationSetting $setting, array $data, int $userId): ContentPublicationSetting
    {
        $oldValues = [
            'content_type' => $setting->content_type?->value,
            'publication_flow' => $setting->publication_flow?->value,
            'is_active' => $setting->is_active,
        ];

        $setting->update([
            'publication_flow' => $data['publication_flow'],
            'is_active' => $data['is_active'] ?? true,
        ]);

        audit()->log(
            event: 'content_publication_setting.updated',
            userId: $userId,
            auditable: $setting,
            oldValues: $oldValues,
            newValues: [
                'content_type' => $setting->content_type?->value,
                'publication_flow' => $setting->publication_flow?->value,
                'is_active' => $setting->is_active,
            ],
            metadata: [
                'source' => 'admin_content_publication_setting_update',
            ],
        );

        return $setting->fresh();
    }
}
