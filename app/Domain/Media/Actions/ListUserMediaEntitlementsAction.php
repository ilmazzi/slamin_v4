<?php

namespace Domain\Media\Actions;

use Domain\Media\Models\UserMediaEntitlement;
use Domain\User\Models\User;
use Illuminate\Database\Eloquent\Collection;

class ListUserMediaEntitlementsAction
{
    public function execute(User $user): Collection
    {
        return UserMediaEntitlement::query()
            ->with(['featurePack', 'grantedBy.profile'])
            ->where('user_id', $user->id)
            ->latest('id')
            ->get();
    }
}
