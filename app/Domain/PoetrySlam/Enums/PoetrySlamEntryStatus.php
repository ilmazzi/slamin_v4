<?php

namespace Domain\PoetrySlam\Enums;

enum PoetrySlamEntryStatus: string
{
    case Registered = 'registered';
    case Confirmed = 'confirmed';
    case CheckedIn = 'checked_in';
    case Performed = 'performed';
    case Eliminated = 'eliminated';
    case Winner = 'winner';
}