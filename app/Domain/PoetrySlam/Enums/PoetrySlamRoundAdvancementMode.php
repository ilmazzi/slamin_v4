<?php

namespace Domain\PoetrySlam\Enums;

enum PoetrySlamRoundAdvancementMode: string
{
    case AllEntries = 'all_entries';
    case TopN = 'top_n';
    case TopPercentage = 'top_percentage';
    case ManualSelection = 'manual_selection';
}
