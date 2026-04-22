<?php

namespace Domain\PoetrySlam\Enums;

enum PoetrySlamEntrySource: string
{
    case EventParticipant = 'event_participant';
    case SiteUser = 'site_user';
    case External = 'external';
}