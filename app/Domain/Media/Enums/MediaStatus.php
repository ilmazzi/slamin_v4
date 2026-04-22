<?php

namespace Domain\Media\Enums;

enum MediaStatus: string
{
    case Draft = 'draft';
    case Processing = 'processing';
    case Ready = 'ready';
    case Failed = 'failed';
    case Archived = 'archived';
}
