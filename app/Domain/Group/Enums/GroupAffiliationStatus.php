<?php

namespace Domain\Group\Enums;

enum GroupAffiliationStatus: string
{
    case Pending = 'pending';
    case Accepted = 'accepted';
    case Rejected = 'rejected';
    case Revoked = 'revoked';
}
