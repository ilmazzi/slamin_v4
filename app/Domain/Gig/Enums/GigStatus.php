<?php

namespace Domain\Gig\Enums;

enum GigStatus: string
{
    case Draft = 'draft';
    case Open = 'open';
    case Filled = 'filled';
    case Closed = 'closed';
    case Cancelled = 'cancelled';
}
