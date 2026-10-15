<?php

namespace Domain\Gig\Actions;

use Domain\Gig\Enums\GigApplicationStatus;
use Domain\Gig\Models\Gig;
use Domain\Gig\Queries\GigCatalogQuery;
use Illuminate\Database\Eloquent\Builder;

class ShowGigAction
{
    public function __construct(
        private readonly GigCatalogQuery $catalogQuery,
    ) {}

    public function execute(Gig $gig, int $viewerId): ?Gig
    {
        $loaded = $this->catalogQuery
            ->query($viewerId, withOwner: true)
            ->whereKey($gig->getKey())
            ->first();

        if ($loaded === null || ! $loaded->isVisibleTo($viewerId)) {
            return null;
        }

        if ($loaded->isManagedBy($viewerId)) {
            $loaded->loadCount([
                'applications',
                'applications as pending_applications_count' => function (Builder $query): void {
                    $query->where('status', GigApplicationStatus::Pending->value);
                },
            ]);
        }

        return $loaded;
    }
}
