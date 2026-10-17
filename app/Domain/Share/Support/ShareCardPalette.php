<?php

namespace Domain\Share\Support;

final class ShareCardPalette
{
    /** @return array{int,int,int} */
    public static function for(string $contentType): array
    {
        return match ($contentType) {
            'event' => [132, 218, 180],
            'poem' => [165, 83, 221],
            'article' => [101, 116, 222],
            'gig' => [253, 174, 95],
            'media' => [0, 0, 0],
            default => [17, 17, 17],
        };
    }
}
