<?php

namespace Domain\Gig\Actions;

use Domain\Gig\Enums\GigStatus;
use Domain\Gig\Models\Gig;
use Illuminate\Database\Eloquent\Collection;

class ListOpenGigsAction
{
    public function execute(): Collection
    {
        return Gig::query()
            ->with('giggable')
            ->where('status', GigStatus::Open->value)
            ->orderByDesc('opened_at')
            ->orderByDesc('created_at')
            ->get();
    }
}
