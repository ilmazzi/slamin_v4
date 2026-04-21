<?php

namespace Domain\Event\Enums;

enum EventDeliveryMode: string
{
    case InPerson = 'in_person';
    case Online = 'online';
}
