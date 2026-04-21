<?php

namespace Domain\Event\Enums;

enum EventStatus: string
{
    case Draft = 'draft';
    case PendingReview = 'pending_review';
    case Rejected = 'rejected';
    case Published = 'published';
}
