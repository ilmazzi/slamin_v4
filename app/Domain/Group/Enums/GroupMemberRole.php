<?php

namespace Domain\Group\Enums;

enum GroupMemberRole: string
{
    case Owner = 'owner';
    case Admin = 'admin';
    case Moderator = 'moderator';
    case Member = 'member';
}
