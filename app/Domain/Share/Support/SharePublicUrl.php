<?php

namespace Domain\Share\Support;

final class SharePublicUrl
{
    public static function poem(string $slug): string
    {
        return self::for('poems', $slug);
    }

    public static function event(string $slug): string
    {
        return self::for('events', $slug);
    }

    public static function for(string $plural, string $slug): string
    {
        return rtrim((string) config('app.frontend_url'), '/').'/'.$plural.'/'.$slug;
    }
}
