<?php

namespace Domain\Gig\Enums;

enum GigAnnouncementStatus: string
{
    case Draft = 'draft';
    case Published = 'published';
    case Archived = 'archived';
}
