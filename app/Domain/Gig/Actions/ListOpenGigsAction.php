<?php

namespace Domain\Gig\Actions;

use Domain\Event\Models\Event;
use Domain\Gig\DTOs\ListOpenGigsData;
use Domain\Gig\Enums\GigStatus;
use Domain\Gig\Queries\GigCatalogQuery;
use Domain\Poetry\Models\Poem;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class ListOpenGigsAction
{
    public function __construct(
        private readonly GigCatalogQuery $catalogQuery,
    ) {}

    public function execute(ListOpenGigsData $data, ?int $viewerId): LengthAwarePaginator
    {
        $query = $this->catalogQuery->query($viewerId)
            ->where('gigs.status', GigStatus::Open->value)
            ->withPublicParent();

        if ($data->type !== null) {
            $query->where('gigs.type', $data->type->value);
        }

        if ($data->search !== null) {
            $term = '%'.addcslashes(mb_strtolower($data->search), '%_\\').'%';

            $query->where(function (Builder $builder) use ($term): void {
                $builder
                    ->whereRaw('LOWER(gigs.description) LIKE ?', [$term])
                    ->orWhereHasMorph('giggable', [Event::class, Poem::class], function (Builder $parent) use ($term): void {
                        $parent->whereRaw('LOWER(title) LIKE ?', [$term]);
                    });
            });
        }

        match ($data->sort) {
            'oldest' => $query->orderBy('gigs.opened_at')->orderBy('gigs.id'),
            'deadline' => $query->orderByRaw('gigs.deadline IS NULL')->orderBy('gigs.deadline')->orderByDesc('gigs.id'),
            default => $query->orderByDesc('gigs.opened_at')->orderByDesc('gigs.id'),
        };

        return $query->paginate($data->perPage);
    }
}
