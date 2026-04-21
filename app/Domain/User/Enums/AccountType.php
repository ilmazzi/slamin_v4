<?php

namespace Domain\User\Enums;

enum AccountType: string
{
    case Audience = 'audience';
    case PoetOrganizer = 'poet_organizer';
}
