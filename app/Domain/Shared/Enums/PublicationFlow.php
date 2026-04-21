<?php

namespace Domain\Shared\Enums;

enum PublicationFlow: string
{
    case AutoPublish = 'auto_publish';
    case ManualReview = 'manual_review';
}
