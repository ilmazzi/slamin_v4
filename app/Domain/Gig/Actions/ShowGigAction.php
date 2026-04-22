<?php

namespace Domain\Gig\Actions;

use Domain\Gig\Models\Gig;

class ShowGigAction
{
    public function execute(Gig $gig): Gig
    {
        return $gig->loadMissing([
            'giggable',
            'applications.user.profile',
        ]);
    }
}
