<?php

namespace Domain\Gig\Actions;

use Domain\Gig\Models\GigNegotiation;

class ShowGigNegotiationAction
{
    public function execute(GigNegotiation $negotiation): GigNegotiation
    {
        return $negotiation->loadMissing([
            'gig.giggable',
            'owner.profile',
            'translator.profile',
            'translationJob',
            'events' => fn ($query) => $query->orderBy('created_at')->orderBy('id'),
            'events.author.profile',
        ]);
    }
}
