<?php

namespace Domain\Gig\Enums;

enum GigNegotiationStatus: string
{
    case Open = 'open';
    case Agreed = 'agreed';
    case Rejected = 'rejected';
    case Withdrawn = 'withdrawn';
    case Expired = 'expired';
}
