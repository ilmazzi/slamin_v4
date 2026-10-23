<?php

namespace Domain\Forum\Enums;

enum ForumPostStatus: string
{
    case Published = 'published';
    case Hidden = 'hidden';
}
