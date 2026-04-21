<?php

namespace Domain\Shared\Enums;

enum ContentType: string
{
    case Poem = 'poem';
    case Article = 'article';
    case Event = 'event';
}
