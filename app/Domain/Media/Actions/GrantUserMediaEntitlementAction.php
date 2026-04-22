<?php

namespace Domain\Media\Actions;

use Domain\Media\DTOs\GrantUserMediaEntitlementData;
use Domain\Media\Models\MediaFeaturePack;
use Domain\Media\Models\UserMediaEntitlement;
use Domain\User\Models\User;

class GrantUserMediaEntitlementAction
{
    public function execute(User $user, int $grantedByUserId, GrantUserMediaEntitlementData $data): UserMediaEntitlement
    {
        $featurePack = $data->featurePackId
            ? MediaFeaturePack::query()->findOrFail($data->featurePackId)
            : null;

        $entitlement = UserMediaEntitlement::query()->create([
            'user_id' => $user->id,
            'media_feature_pack_id' => $featurePack?->id,
            'granted_by_user_id' => $grantedByUserId,
            'source' => $data->source,
            'config' => $data->config,
            'starts_at' => $data->startsAt,
            'ends_at' => $data->endsAt,
        ]);

        audit()->log(
            event: 'media.entitlement_granted',
            userId: $grantedByUserId,
            auditable: $entitlement,
            newValues: [
                'user_id' => $entitlement->user_id,
                'media_feature_pack_id' => $entitlement->media_feature_pack_id,
                'source' => $entitlement->source,
                'config' => $entitlement->config,
            ],
            metadata: [
                'source' => 'media_entitlement_grant',
            ],
        );

        return $entitlement->load(['featurePack', 'grantedBy.profile']);
    }
}
