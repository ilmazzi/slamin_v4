<?php

namespace Domain\Gig\Enums;

enum TranslationCommentStatus: string
{
    case Open = 'open';
    case Resolved = 'resolved';
    case Archived = 'archived';
}
