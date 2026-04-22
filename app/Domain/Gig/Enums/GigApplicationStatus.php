<?php

namespace Domain\Gig\Enums;

enum GigApplicationStatus: string
{
    case Pending = 'pending';
    case Accepted = 'accepted';
    case Rejected = 'rejected';
    case Withdrawn = 'withdrawn';
    case Confirmed = 'confirmed';
}
