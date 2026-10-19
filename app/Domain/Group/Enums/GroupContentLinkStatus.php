<?php

namespace Domain\Group\Enums;

enum GroupContentLinkStatus: string
{
    case Pending = 'pending';
    case Accepted = 'accepted';
    case Rejected = 'rejected';
    case Removed = 'removed';
}
