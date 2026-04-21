<?php

namespace Domain\Event\Enums;

enum EventScheduleMode: string
{
    case Fixed = 'fixed';
    case Recurring = 'recurring';
    case Availability = 'availability';
}
