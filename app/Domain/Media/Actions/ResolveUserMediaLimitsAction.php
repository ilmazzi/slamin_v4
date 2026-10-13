<?php

namespace Domain\Media\Actions;

use Domain\Media\Enums\MediaType;
use Domain\Media\Models\MediaItem;
use Domain\Media\Models\UserMediaEntitlement;
use Domain\Media\Services\PeerTubeService;

class ResolveUserMediaLimitsAction
{
    public function __construct(
        private readonly ResolveMediaSettingsAction $resolveMediaSettingsAction,
        private readonly PeerTubeService $peerTubeService,
    ) {}

    public function execute(int $userId): array
    {
        $settings = $this->resolveMediaSettingsAction->execute();
        $bonuses = $this->resolveEntitlementBonuses($userId);

        $photoCount = MediaItem::query()
            ->where('user_id', $userId)
            ->where('type', MediaType::Photo)
            ->count();

        $videoCount = MediaItem::query()
            ->where('user_id', $userId)
            ->where('type', MediaType::Video)
            ->count();

        return [
            'base_photo_limit' => (int) $settings['photo_limit'],
            'base_video_limit' => (int) $settings['video_limit'],
            'photo_limit_bonus' => $bonuses['photo_limit_bonus'],
            'video_limit_bonus' => $bonuses['video_limit_bonus'],
            'photo_limit' => (int) $settings['photo_limit'] + $bonuses['photo_limit_bonus'],
            'video_limit' => (int) $settings['video_limit'] + $bonuses['video_limit_bonus'],
            'current_photo_count' => $photoCount,
            'current_video_count' => $videoCount,
            'remaining_photo_uploads' => max(0, ((int) $settings['photo_limit'] + $bonuses['photo_limit_bonus']) - $photoCount),
            'remaining_video_uploads' => max(0, ((int) $settings['video_limit'] + $bonuses['video_limit_bonus']) - $videoCount),
            'photo_max_size_kb' => (int) $settings['photo_max_size_kb'],
            'video_max_size_kb' => (int) $settings['video_max_size_kb'],
            'photo_allowed_mimes' => array_values((array) $settings['photo_allowed_mimes']),
            'video_allowed_mimes' => array_values((array) $settings['video_allowed_mimes']),
            'peertube_configured' => $this->peerTubeService->isConfigured(),
        ];
    }

    /**
     * @return array{photo_limit_bonus:int,video_limit_bonus:int}
     */
    private function resolveEntitlementBonuses(int $userId): array
    {
        $entitlements = UserMediaEntitlement::query()
            ->with('featurePack')
            ->where('user_id', $userId)
            ->get()
            ->filter(fn (UserMediaEntitlement $entitlement) => $entitlement->isActive());

        $photoBonus = 0;
        $videoBonus = 0;

        foreach ($entitlements as $entitlement) {
            $config = array_merge(
                is_array($entitlement->featurePack?->config) ? $entitlement->featurePack->config : [],
                is_array($entitlement->config) ? $entitlement->config : [],
            );

            $photoBonus += (int) ($config['photo_limit_bonus'] ?? 0);
            $videoBonus += (int) ($config['video_limit_bonus'] ?? 0);
        }

        return [
            'photo_limit_bonus' => $photoBonus,
            'video_limit_bonus' => $videoBonus,
        ];
    }
}
