<?php

namespace Domain\Report\Enums;

enum ReportStatus: string
{
    case Open = 'open';
    case InReview = 'in_review';
    case Resolved = 'resolved';
    case Dismissed = 'dismissed';
}
