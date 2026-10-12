<?php

namespace Domain\Report\Actions;

use Domain\Article\Models\Article;
use Domain\Comment\Models\Comment;
use Domain\Event\Models\Event;
use Domain\Media\Models\MediaItem;
use Domain\Poetry\Models\Poem;
use Domain\Report\Models\Report;

class ShowReportAction
{
    public function execute(Report $report): Report
    {
        return $report->load([
            'reporter.profile',
            'reviewedBy.profile',
            'reportable' => function ($morphTo) {
                $morphTo->morphWith([
                    Poem::class => [],
                    Article::class => [],
                    Comment::class => ['user.profile'],
                    Event::class => [],
                    MediaItem::class => [],
                ]);
            },
        ]);
    }
}
