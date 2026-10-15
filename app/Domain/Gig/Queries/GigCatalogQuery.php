<?php

namespace Domain\Gig\Queries;

use Domain\Event\Models\Event;
use Domain\Gig\Models\Gig;
use Domain\Gig\Models\GigApplication;
use Domain\Poetry\Models\Poem;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class GigCatalogQuery
{
    /**
     * @return Builder<Gig>
     */
    public function query(?int $viewerId, bool $withOwner = false): Builder
    {
        $eventRelations = ['venueDetail', 'occurrences'];
        $poemRelations = [];

        if ($withOwner) {
            $eventRelations[] = 'user.profile';
            $poemRelations[] = 'user.profile';
        }

        $query = Gig::query()
            ->select('gigs.*')
            ->with(['giggable' => function (MorphTo $morphTo) use ($eventRelations, $poemRelations): void {
                $morphTo->morphWith([
                    Event::class => $eventRelations,
                    Poem::class => $poemRelations,
                ]);
            }]);

        return $this->withViewerApplication($query, $viewerId);
    }

    /**
     * @param  Builder<Gig>  $query
     * @return Builder<Gig>
     */
    public function withViewerApplication(Builder $query, ?int $viewerId): Builder
    {
        if ($viewerId === null) {
            return $query;
        }

        $mine = fn (string $column) => GigApplication::query()
            ->select($column)
            ->whereColumn('gig_applications.gig_id', 'gigs.id')
            ->where('gig_applications.user_id', $viewerId)
            ->limit(1);

        return $query->addSelect([
            'my_application_id' => $mine('id'),
            'my_application_status' => $mine('status'),
        ]);
    }
}
