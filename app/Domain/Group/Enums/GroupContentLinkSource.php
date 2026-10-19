<?php

namespace Domain\Group\Enums;

enum GroupContentLinkSource: string
{
    case Direct = 'direct';
    case ChildForward = 'child_forward';
    case Staff = 'staff';
}
