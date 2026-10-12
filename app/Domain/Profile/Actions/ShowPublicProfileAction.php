<?php

namespace Domain\Profile\Actions;

use Domain\Profile\Models\Profile;

class ShowPublicProfileAction
{
    public function execute(string $nickname): ?Profile
    {
        $safeNickname = strtolower(trim($nickname));
        if ($safeNickname === '') {
            return null;
        }

        return Profile::query()
            ->with('user')
            ->whereRaw('LOWER(nickname) = ?', [$safeNickname])
            ->first();
    }
}
