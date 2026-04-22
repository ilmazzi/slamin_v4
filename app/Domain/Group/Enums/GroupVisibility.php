<?php

namespace Domain\Group\Enums;

enum GroupVisibility: string
{
    case PublicOpen = 'public_open';
    case PrivateApproval = 'private_approval';
}
