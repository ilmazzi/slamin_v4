<?php

namespace Domain\PoetrySlam\Enums;

enum PoetrySlamRoundAdvancementDecisionStatus: string
{
    case Pending = 'pending';
    case Advanced = 'advanced';
    case Eliminated = 'eliminated';
}
