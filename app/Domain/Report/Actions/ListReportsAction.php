<?php

namespace Domain\Report\Actions;

use Domain\Article\Models\Article;
use Domain\Comment\Models\Comment;
use Domain\Event\Models\Event;
use Domain\Media\Models\MediaItem;
use Domain\Poetry\Models\Poem;
use Domain\Report\DTOs\ListReportsData;
use Domain\Report\Models\Report;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ListReportsAction
{
    public function execute(ListReportsData $data): LengthAwarePaginator
    {
        $query = Report::query()
            ->with([
                'reporter.profile',
                'reviewedBy.profile',
                'reportable' => function ($morphTo) {
                    $morphTo->morphWith([
                        Poem::class => [],
                        Article::class => [],
                        Comment::class => [],
                        Event::class => [],
                        MediaItem::class => [],
                    ]);
                },
            ])
            ->orderByRaw(
                'case when status = ? then 0 when status = ? then 1 else 2 end',
                ['open', 'in_review']
            )
            ->orderByDesc('created_at');

        if ($data->status) {
            $query->where('status', $data->status);
        }

        if ($data->reportableType) {
            $morphClass = match ($data->reportableType) {
                'poem' => (new Poem)->getMorphClass(),
                'article' => (new Article)->getMorphClass(),
                'comment' => (new Comment)->getMorphClass(),
                'event' => (new Event)->getMorphClass(),
                'media' => (new MediaItem)->getMorphClass(),
            };

            $query->where('reportable_type', $morphClass);
        }

        return $query->paginate($data->perPage);
    }
}
