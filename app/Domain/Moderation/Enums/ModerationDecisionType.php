<?php

namespace Domain\Moderation\Enums;

enum ModerationDecisionType: string
{
    case Approved = 'approved';
    case Rejected = 'rejected';
}
