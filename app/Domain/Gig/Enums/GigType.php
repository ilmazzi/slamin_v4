<?php

namespace Domain\Gig\Enums;

enum GigType: string
{
    case PoetArtist = 'poet_artist';
    case McHost = 'mc_host';
    case TechnicalSupport = 'technical_support';
    case Volunteer = 'volunteer';
    case Translator = 'translator';
}
