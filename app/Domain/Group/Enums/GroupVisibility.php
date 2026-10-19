<?php

namespace Domain\Group\Enums;

enum GroupVisibility: string
{
    case Public = 'public';
    case Private = 'private';
}
