<?php

namespace Domain\Media\Actions;

use Domain\Media\Models\UserMediaEntitlement;

class RevokeUserMediaEntitlementAction
{
    public function execute(UserMediaEntitlement $entitlement, int $revokedByUserId): void
    {
        audit()->log(
            event: 'media.entitlement_revoked',
            userId: $revokedByUserId,
            auditable: $entitlement,
            oldValues: [
                'user_id' => $entitlement->user_id,
                'media_feature_pack_id' => $entitlement->media_feature_pack_id,
                'source' => $entitlement->source,
                'config' => $entitlement->config,
            ],
            metadata: [
                'source' => 'media_entitlement_revoke',
            ],
        );

        $entitlement->delete();
    }
}
