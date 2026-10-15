<?php

namespace Domain\Gig\Actions;

use Domain\Gig\Enums\GigApplicationStatus;
use Domain\Gig\Enums\GigStatus;
use Domain\Gig\Queries\GigCatalogQuery;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class ListManagedGigsAction
{
    public function __construct(
        private readonly GigCatalogQuery $catalogQuery,
    ) {}

    public function execute(int $userId, ?GigStatus $status, int $perPage): LengthAwarePaginator
    {
        return $this->catalogQuery->query(null)
            ->managedBy($userId)
            ->when($status !== null, fn (Builder $query) => $query->where('gigs.status', $status->value))
            ->withCount([
                'applications',
                'applications as pending_applications_count' => function (Builder $query): void {
                    $query->where('status', GigApplicationStatus::Pending->value);
                },
            ])
            ->orderByDesc('gigs.created_at')
            ->orderByDesc('gigs.id')
            ->paginate($perPage);
    }
}
