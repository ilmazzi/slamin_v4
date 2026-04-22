<?php

namespace Domain\PoetrySlam\Enums;

enum PoetrySlamRoundAdvancementBasedOn: string
{
    case RoundResult = 'round_result';
    case CumulativeResult = 'cumulative_result';
}
