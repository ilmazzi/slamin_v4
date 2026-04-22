<?php

namespace Domain\PoetrySlam\Enums;

enum PoetrySlamStatus: string
{
    case Draft = 'draft';
    case Open = 'open';
    case Closed = 'closed';
    case Finalized = 'finalized';
}