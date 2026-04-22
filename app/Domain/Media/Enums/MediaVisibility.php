<?php

namespace Domain\Media\Enums;

enum MediaVisibility: string
{
    case Private = 'private';
    case Unlisted = 'unlisted';
    case Public = 'public';
}
