<?php

namespace Domain\Share\Support;

final class ShareCardSafeArea
{
    /** @return array{left:int,top:int,right:int,bottom:int} */
    public static function for(ShareCardFormat $format): array
    {
        return match ($format) {
            ShareCardFormat::Story => ['left' => 120, 'top' => 250, 'right' => 120, 'bottom' => 250],
            ShareCardFormat::Portrait => ['left' => 96, 'top' => 110, 'right' => 96, 'bottom' => 110],
            ShareCardFormat::Square => ['left' => 88, 'top' => 88, 'right' => 88, 'bottom' => 88],
            ShareCardFormat::Landscape => ['left' => 64, 'top' => 56, 'right' => 64, 'bottom' => 56],
        };
    }
}
