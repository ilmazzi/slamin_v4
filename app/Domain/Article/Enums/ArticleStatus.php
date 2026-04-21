<?php

namespace Domain\Article\Enums;

enum ArticleStatus: string
{
    case Draft = 'draft';
    case PendingReview = 'pending_review';
    case Rejected = 'rejected';
    case Published = 'published';
}
