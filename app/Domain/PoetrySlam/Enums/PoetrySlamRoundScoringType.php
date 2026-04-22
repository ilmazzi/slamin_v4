<?php

namespace Domain\PoetrySlam\Enums;

enum PoetrySlamRoundScoringType: string
{
    case TrimmedSum = 'trimmed_sum';
    case Sum = 'sum';
    case Average = 'average';
    case Best = 'best';
}
