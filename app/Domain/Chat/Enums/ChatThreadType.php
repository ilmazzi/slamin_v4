<?php

namespace Domain\Chat\Enums;

enum ChatThreadType: string
{
    case Direct = 'direct';
    case Group = 'group';
}
