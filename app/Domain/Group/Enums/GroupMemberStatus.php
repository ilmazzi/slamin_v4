<?php

namespace Domain\Group\Enums;

enum GroupMemberStatus: string
{
    case Pending = 'pending';
    case Active = 'active';
    case Rejected = 'rejected';
    case Left = 'left';
    case Removed = 'removed';
}
