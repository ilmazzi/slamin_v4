<?php

namespace Domain\Gig\Actions;

use Domain\Gig\Models\GigNegotiation;

class ShowGigNegotiationAction
{
    public function execute(GigNegotiation $negotiation): GigNegotiation
    {
        return $negotiation->loadMissing([
            'gig.giggable',
            'application.user.profile',
            'owner.profile',
            'translator.profile',
            'events.author.profile',
        ]);
    }
}
