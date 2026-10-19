<?php

namespace Domain\Group\Enums;

enum GroupMemberRole: string
{
    case Admin = 'admin';
    case Editor = 'editor';
    case Follower = 'follower';

    public function rank(): int
    {
        return match ($this) {
            self::Admin => 3,
            self::Editor => 2,
            self::Follower => 1,
        };
    }

    public function isStaff(): bool
    {
        return $this === self::Admin || $this === self::Editor;
    }
}
