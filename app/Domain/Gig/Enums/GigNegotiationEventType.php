<?php

namespace Domain\Gig\Enums;

enum GigNegotiationEventType: string
{
    case OwnerOffer = 'owner_offer';
    case TranslatorOffer = 'translator_offer';
    case CounterOffer = 'counter_offer';
    case Note = 'note';
    case Agreement = 'agreement';
    case Rejection = 'rejection';
    case Withdrawal = 'withdrawal';
}
