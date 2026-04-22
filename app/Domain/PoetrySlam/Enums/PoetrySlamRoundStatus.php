<?php

namespace Domain\PoetrySlam\Enums;

enum PoetrySlamRoundStatus: string
{
    case Draft = 'draft';
    case Active = 'active';
    case Completed = 'completed';
}
