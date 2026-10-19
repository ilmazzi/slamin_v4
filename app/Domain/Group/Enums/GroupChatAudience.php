<?php

namespace Domain\Group\Enums;

enum GroupChatAudience: string
{
    case Admin = 'admin';
    case Editor = 'editor';
    case Affiliates = 'affiliates';
    case Public = 'public';

    public function labelKey(): string
    {
        return 'group.ui.chat_audience_'.$this->value;
    }
}
