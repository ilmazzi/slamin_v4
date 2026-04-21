<?php

namespace Domain\Poetry\Enums;

enum PoemStatus: string
{
    case Draft = 'draft';
    case PendingReview = 'pending_review';
    case Rejected = 'rejected';
    case Published = 'published';
}
