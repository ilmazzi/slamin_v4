<?php

namespace Domain\Group\Enums;

enum GroupContentType: string
{
    case Announcement = 'announcement';
    case Event = 'event';
    case Gig = 'gig';
    case Poem = 'poem';
    case Article = 'article';
}
